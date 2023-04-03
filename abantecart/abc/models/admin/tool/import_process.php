<?php

/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2023 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\models\admin;

use abc\core\ABC;
use abc\core\engine\Model;
use abc\core\engine\Registry;
use abc\core\lib\AException;
use abc\core\lib\AFile;
use abc\core\lib\ALog;
use abc\core\lib\AResourceManager;
use abc\core\lib\ATaskManager;
use abc\models\catalog\Category;
use abc\models\catalog\CategoryDescription;
use abc\models\catalog\Manufacturer;
use abc\models\catalog\Product;
use abc\models\catalog\ProductOptionValue;
use abc\models\catalog\ProductOption;
use abc\models\locale\WeightClassDescription;
use abc\modules\events\ABaseEvent;
use Exception;
use H;
use Illuminate\Database\Query\JoinClause;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

class ModelToolImportProcess extends Model
{
    public $errors = [];
    public $data = [];
    protected $eta = [];
    protected $task_id;
    /**
     * @var ALog
     */
    protected $imp_log = null;

    /**
     * @param string $task_name
     * @param array $data
     *
     * @return array|bool
     * @throws Exception
     */
    public function createTask($task_name, $data = [])
    {
        if (!$task_name) {
            $this->errors[] = 'Can not to create task. Empty task name has been given.';
        }

        if (!$data['file'] && !$data['products_fields']) {
            $this->errors[] = 'Missing required data to build a task.';
        }
        //get file details
        $total_rows_count = -1;
        ini_set("auto_detect_line_endings", true);
        $handle = fopen($data['file'], "r");
        if (is_resource($handle)) {
            while (fgetcsv($handle, 0, $data['delimiter']) !== false) {
                $total_rows_count++;
            }
            unset($line);
            fclose($handle);
        } else {
            $this->errors[] = 'No import feed file available!';
            return false;
        }

        //task controller processing task steps
        $task_controller = 'task/tool/import_process/processRows';

        //numbers of rows per task step
        $divider = 2;
        //timeout in seconds for one row
        $time_per_send = 15;

        $tm = new ATaskManager();
        //create new task
        $task_id = $tm->addTask(
            [
                'name'               => $task_name,
                'starter'            => 1, //admin-side is starter
                'created_by'         => Registry::user()->getId(), //get starter id
                'status'             => $tm::STATUS_READY,
                'start_time'         => date(
                    'Y-m-d H:i:s',
                    mktime(0, 0, 0, date('m'), (int)date('d') + 1, date('Y'))
                ),
                'last_time_run'      => '0000-00-00 00:00:00',
                'progress'           => '0',
                'last_result'        => '1',
                'run_interval'       => '0',
                'max_execution_time' => ($total_rows_count * $time_per_send * 2),
            ]
        );
        if (!$task_id) {
            $this->errors = array_merge($this->errors, $tm->errors);
            return false;
        }

        $tm->updateTaskDetails($task_id,
            [
                'created_by' => Registry::user()->getId(),
                'settings'   => [
                    'import_data'      => $data,
                    'total_rows_count' => $total_rows_count,
                    'success_count'    => 0,
                    'failed_count'     => 0,
                ],
            ]
        );

        $sort_order = 1;
        $k = 0;
        while ($k < $total_rows_count) {
            //create task step
            $stop = $k + $divider;
            $stop = min($stop, $total_rows_count);
            $step_id = $tm->addStep(
                [
                    'task_id'            => $task_id,
                    'sort_order'         => $sort_order,
                    'status'             => 1,
                    'last_time_run'      => '0000-00-00 00:00:00',
                    'last_result'        => '0',
                    'max_execution_time' => ($divider * $time_per_send * 2),
                    'controller'         => $task_controller,
                    'settings'           => [
                        'start' => $k,
                        'stop'  => $stop,
                    ],
                ]
            );

            if (!$step_id) {
                $this->errors = array_merge($this->errors, $tm->errors);
                return false;
            } else {
                // get eta in seconds
                $this->eta[$step_id] = ($divider * $time_per_send * 2);
            }

            $sort_order++;
            $k += $divider + 1;
        }

        $task_details = $tm->getTaskById($task_id);

        if ($task_details) {
            foreach ($this->eta as $step_id => $eta) {
                $task_details['steps'][$step_id]['eta'] = $eta;
                //remove settings from output json array. We will take it from database on execution.
                $task_details['steps'][$step_id]['settings'] = [];
            }
            $this->task_id = $task_id;
            return $task_details;
        } else {
            $this->errors[] = 'Can not to get task details for execution';
            $this->errors = array_merge($this->errors, $tm->errors);
            return false;
        }
    }

    /**
     * @param $task_id
     * @param $data
     * @param $settings
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function process_products_record($task_id, $data, $settings)
    {
        $db = Registry::db();
        $db->beginTransaction();
        try {
            $this->task_id = $task_id;
            $language_id = $settings['language_id'] ?: Registry::language()->getContentLanguageID();
            $store_id = (int)$settings['store_id'] ?: (int)Registry::session()->data['current_store_id'];
            $log_classname = ABC::getFullClassName('ALog');
            if ($log_classname) {
                $this->imp_log = new $log_classname(
                    [
                        'app' => "products_import_" . $task_id . ".txt",
                    ]
                );
            }

            $this->data['product_data'] = $data;
            $this->data['settings'] = $settings;
            $this->errors = [];

            //allow change list from hooks
            $this->extensions->hk_InitData($this, __FUNCTION__);

            $result = false;
            if (empty($this->errors)) {
                $result = $this->addUpdateProduct(
                    $this->data['product_data'],
                    $this->data['settings'],
                    $language_id,
                    $store_id
                );
            } else {
                foreach ($this->errors as $error) {
                    $this->toLog($error);
                }
            }

            $db->commit();
            //allow run additional actions from hooks
            Registry::extensions()->hk_ProcessData($this, __FUNCTION__);
            return $result;
        } catch (Exception $e) {
            $db->rollback();
            $this->toLog($e->getMessage());
            return false;
        }
    }

    /**
     * @param $task_id
     * @param $data
     * @param $settings
     *
     * @return bool
     * @throws AException
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    public function process_categories_record($task_id, $data, $settings)
    {
        $this->task_id = $task_id;
        $language_id = $settings['language_id'] ?: Registry::language()->getContentLanguageID();
        $store_id = $settings['store_id'] ?: Registry::session()->data['current_store_id'];
        $log_classname = ABC::getFullClassName('ALog');
        if ($log_classname) {
            $this->imp_log = new $log_classname(['app' => "categories_import_" . $task_id . ".txt"]);
        }
        return $this->addUpdateCategory($data, $settings, $language_id, $store_id);
    }

    /**
     * @param $task_id
     * @param $data
     * @param $settings
     *
     * @return bool
     * @throws InvalidArgumentException
     * @throws AException
     * @throws ReflectionException
     */
    public function process_manufacturers_record($task_id, $data, $settings)
    {
        $this->task_id = $task_id;
        $language_id = $settings['language_id'] ?: Registry::language()->getContentLanguageID();
        $store_id = $settings['store_id'] ?: Registry::session()->data['current_store_id'];
        $log_classname = ABC::getFullClassName('ALog');
        if ($log_classname) {
            $this->imp_log = new $log_classname(
                [
                    'app' => "manufacturers_import_" . $task_id . ".txt",
                ]
            );
        }
        return $this->addUpdateManufacturer($data, $settings, $language_id, $store_id);
    }

    /**
     * @param $record
     * @param $settings
     * @param $language_id
     * @param $store_id
     *
     * @return bool
     * @throws AException
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    protected function addUpdateProduct($record, $settings, $language_id, $store_id)
    {
        $status = false;
        $record = array_map('trim', $record);

        //data mapping
        $data = $this->buildDataMap(
            $record,
            $settings['import_col'],
            $settings['products_fields'],
            $settings['split_col']
        );

        if (empty($data)) {
            $this->toLog("Error: Unable to build products import data map.");
            return false;
        }
        $product = $this->filterArray($data['products']);
        $product_desc = $this->filterArray($data['product_descriptions']);
        $manufacturers = $this->filterArray($data['manufacturers']);

        $product_data = $product;

        // import brand if needed
        $product_data['manufacturer_id'] = 0;
        if ($manufacturers['manufacturer']) {
            $product_data['manufacturer_id'] = $this->processManufacturer($manufacturers['manufacturer'], 0, $store_id);
        }

        //check if row is complete and uniform
        if (!$product_desc['name'] && !$product['sku'] && !$product['model']) {
            $this->toLog('Error: Record is not complete or missing required data. Skipping!');
            return false;
        }

        $this->toLog("Processing record for product " . $product_desc['name'] . " " . $product['sku'] . " " . $product['model']);

        //detect if we update or create new product based on update settings
        $new_product = true;
        $product_id = 0;
        if ($settings['update_col']) {
            $unique_field_index = key($settings['update_col']);
            if (is_numeric($unique_field_index)) {
                $unique_field = $settings['products_fields'][$unique_field_index];
                $lookup_value = $this->getValueFromDataMap(
                    $unique_field,
                    $record,
                    $settings['products_fields'],
                    $settings['import_col']
                );
                $product_id = $this->getProductByField($unique_field, $lookup_value, $language_id, $store_id);
                if ($product_id) {
                    //we have product, update
                    $new_product = false;
                }
            }
        }

        if ($new_product) {
            $product_data['product_description'] = array_merge($product_desc, ['language_id' => $language_id]);
            //apply default settings for new products only
            $default_arr = [
                'status'          => 1,
                'subtract'        => 1,
                'free_shipping'   => 0,
                'shipping'        => 1,
                'call_to_order'   => 0,
                'sort_order'      => 0,
                'weight_class_id' => 5,
                'length_class_id' => 3,
            ];
            foreach ($default_arr as $key => $val) {
                $product_data[$key] = $product_data[$key] ?? $val;
            }

            $productModel = Product::createProduct($product_data);
            $product_id = $productModel->product_id;
            if ($product_id) {
                $this->toLog("Created product '" . $product_desc['name'] . "' with ID " . $product_id);
                $status = true;
            } else {
                $this->toLog("Error: Failed to create product '" . $product_desc['name'] . "'.");
            }

        } else {
            //flat array for description (specific for update)
            $product_data['product_description'] = $product_desc;
            Product::setCurrentLanguageID($language_id);
            Product::updateProduct($product_id, $product_data);
            $this->toLog("Updated product '" . $product_desc['name'] . "' with ID " . $product_id . ".");
            $status = true;
        }

        // import category if needed
        $categories = [];
        if ($data['categories'] && $data['categories']['category']) {
            $categories = $this->processCategories($data['categories'], $language_id, $store_id);
        }

        $product_links = [
            'product_store' => [$store_id],
        ];

        $product_links['product_category'] = [];
        if (count($categories)) {
            $product_links['product_category'] = array_column($categories, 'category_id');
        }
        Product::updateProductLinks($product_id, $product_links);

        //process images
        $this->migrateImages($data['images'], 'products', $product_id, $product_desc['name'], $language_id);

        //process options
        $this->addUpdateOptions(
            $product_id,
            $data['product_options'],
            $product_data['weight_class_id']
        );

        $this->data['product_data']['product_id'] = $product_id;
        if ($status) {
            //call event
            H::event(__CLASS__ . '@' . __FUNCTION__, [new ABaseEvent($this->task_id, $product_id, $data, $record)]);
        }

        return $status;
    }

    /**
     * @param $record
     * @param $settings
     * @param $language_id
     * @param $store_id
     *
     * @return bool
     * @throws AException
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    protected function addUpdateCategory($record, $settings, $language_id, $store_id)
    {
        $record = array_map('trim', $record);
        //data mapping
        $data = $this->buildDataMap($record, $settings['import_col'], $settings['categories_fields'],
            $settings['split_col']);
        if (empty($data)) {
            return $this->toLog("Error: Unable to build categories import data map.");
        }

        $category = $this->filterArray($data['categories']);
        //check if we have split tree or an array
        $category_desc = $this->filterArray($data['category_descriptions']);
        $category_tree = $category_desc['name'];
        if (count($data['category_descriptions']['name']) > 1) {
            $category_tree = $data['category_descriptions']['name'];
        }
        //Get actual category name
        $category_desc['name'] = end($category_tree);

        $s_tree = implode(' -> ', $category_tree);
        $this->toLog("Processing record for category { $s_tree } .");
        //process all categories
        $categories = $this->processCategories(['category' => [$category_tree]], $language_id, $store_id);
        //we will have always one category
        $category_id = $categories[0]['category_id'];
        $parent_category_id = $categories[0]['parent_id'];

        $category_data = array_merge(
            $category,
            ['parent_id' => $parent_category_id],
            ['category_description' => [$language_id => $category_desc]],
            ['category_store' => [$store_id]]
        );

        if ($category_id) {
            //update category
            Category::editCategory(
                $category_id,
                $category_data
            );
            $this->toLog("Updated category " . $category_desc['name'] . " with ID " . $category_id);
        } else {
            $default_arr = [
                'status'     => 1,
                'sort_order' => 0,
            ];
            foreach ($default_arr as $key => $val) {
                $category[$key] = $category[$key] ?? $val;
            }

            $category_id = Category::addCategory($category_data);
            if ($category_id) {
                $this->toLog("Created category " . $category_desc['name'] . " with ID " . $category_id);
            } else {
                $this->toLog("Error: Failed to create category " . $category_desc['name'] . ".");
                return false;
            }
        }

        //process images
        $this->migrateImages($data['images'], 'categories', $category_id, $category_desc['name'], $language_id);

        H::event(
            __CLASS__ . '@' . __FUNCTION__,
            [new ABaseEvent($this->task_id, $category_id, $category_data, $record)]
        );

        return true;
    }

    /**
     * @param $record
     * @param $settings
     * @param $language_id
     * @param $store_id
     *
     * @return bool
     * @throws AException
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    protected function addUpdateManufacturer($record, $settings, $language_id, $store_id)
    {
        $status = false;
        $record = array_map('trim', $record);
        //data mapping
        $data = $this->buildDataMap(
            $record,
            $settings['import_col'],
            $settings['manufacturers_fields'],
            $settings['split_col']
        );

        if (empty($data)) {
            $this->toLog("Error: Unable to build manufacturers import data map.");
            return false;
        }

        $manufacturer = $this->filterArray($data['manufacturers']);

        $manufacturer_id = $this->processManufacturer(
            $manufacturer['name'],
            $manufacturer['sort_order'],
            $store_id
        );

        if ($manufacturer_id) {
            $status = true;
            //process images
            $this->migrateImages(
                $data['images'],
                'manufacturers',
                $manufacturer_id,
                $manufacturer['name'],
                $language_id
            );
        }

        if ($status) {
            //call event
            H::event(
                __CLASS__ . '@' . __FUNCTION__,
                [new ABaseEvent($this->task_id, $manufacturer_id, $manufacturer, $record)]
            );
        }
        return $status;
    }

    /**
     * @param int $productId
     * @param array $data
     * @param int $weightClassId
     *
     * @return bool
     * @throws ReflectionException
     * @throws AException
     * @throws InvalidArgumentException
     */
    protected function addUpdateOptions($productId, $data, $weightClassId)
    {
        if (!is_array($data) || empty($data)) {
            //no option details
            return false;
        }
        $db = Registry::db();

        $this->toLog("Creating product option for product ID " . $productId . ".");
        /** @var Product $product */
        $product = Product::with('options')->find($productId);
        if (!$product) {
            $this->toLog("Error: Product ID " . $productId . " not found in database.");
            return false;
        }

        //delete existing options and values.
        $product->options?->delete();

        //add new options for each option
        foreach ($data as $dataRow) {
            //skip empty arrays
            if (!is_array($dataRow) || !$dataRow['name'] || !is_string($dataRow['name'])) {
                continue;
            }

            //check is update needed
            $optionId = ProductOption::select('product_options.product_option_id')
                ->join(
                    'product_option_descriptions',
                    function ($join) use ($db, $dataRow) {
                        $join->on(
                            'product_option_descriptions.product_option_id',
                            '=',
                            'product_options.product_option_id'
                        )
                            ->whereRaw(
                                "LOWER(" . $db->table_name('product_option_descriptions') . ".name) = '"
                                . $db->escape(mb_strtolower($dataRow['name'])) . "'");
                    }
                )->where('product_options.product_id', '=', $productId)
                ->useCache('product')->first('product_option_id');

            $optionData = [
                'option_name'        => $dataRow['name'],
                'element_type'       => 'S',
                'status'             => $dataRow['status'] ?? 1,
                'regexp_pattern'     => "",
                'error_text'         => '',
                'option_placeholder' => '',
                'required'           => $dataRow['required'] ?? 0,
                'sort_order'         => $dataRow['sort_order'] ?? 0,
            ];
            if (!$optionId) {
                $optionId = $product->addProductOption($optionData);
                if ($optionId) {
                    $this->toLog("Created product option " . $dataRow['name'] . " with ID " . $optionId);
                } else {
                    $this->toLog("Error: Failed to create product option " . $dataRow['name'] . ".");
                    return false;
                }
            } else {
                try {
                    /** @var ProductOption $option */
                    $option = ProductOption::find($optionId);
                    $option->update($optionData);
                    $this->toLog("Created product option " . $dataRow['name'] . " with ID " . $optionId . ".");
                } catch (Exception $e) {
                    $this->toLog(
                        "Error: Failed to update product option " . $dataRow['name']
                        . " with ID " . $optionId . ".\n"
                        . $e->getMessage()
                    );
                    return false;
                }
            }

            //get all values of option
            $existValueIds = ProductOptionValue::where('product_option_id', '=', $optionId)
                ->where('product_id', '=', $productId)
                ->get()?->pluck('product_option_value_id')->toArray();

            //now load values. Pick the longest data array
            $optionValues = (array)$dataRow['product_option_values'];

            //find the largest key by count
            $counts = @array_map('count', $optionValues);
            $ids = [];
            if (max($counts) == 1) {
                //single option value case
                $ids[] = $this->saveOptionValue($productId, $weightClassId, $optionId, $optionValues);
            } else {
                for ($j = 0; $j < max($counts); $j++) {
                    //build flat associative array options value
                    $optionValueData = [];
                    foreach (array_keys($optionValues) as $key) {
                        $optionValueData[$key] = $optionValues[$key][$j];
                    }
                    $ids[] = $this->saveOptionValue($productId, $weightClassId, $optionId, $optionValueData);
                }
            }

            //remove values that absent in save record
            $diff_ids = array_diff($existValueIds, $ids);
            foreach ($diff_ids as $delete_id) {
                /** @var ProductOptionValue $pov */
                $pov = ProductOptionValue::find($delete_id);
                $pov?->delete();
            }
        }
        return true;
    }

    /**
     * @param $productId
     * @param $weightClassId
     * @param $optionId
     * @param $data
     *
     * @return bool|int|null
     * @throws ReflectionException
     * @throws AException
     * @throws InvalidArgumentException
     */
    protected function saveOptionValue($productId, $weightClassId, $optionId, $data)
    {
        if (empty($data) || !array_filter($data) || !$data['name']) {
            //skip empty data
            return false;
        }

        //update by sku first. if not - see name of option value
        $updateBy = [];
        if (isset($data['sku']) && trim($data['sku'])) {
            $updateBy['sku'] = $data['sku'];
        }
        if (trim($data['name'])) {
            $updateBy['name'] = $data['name'];
        }
        $result = null;
        foreach ($updateBy as $colName => $colValue) {
            $result = ProductOptionValue::select('product_option_values.*, product_option_value_descriptions.*')
                ->leftJoin(
                    'product_option_value_descriptions',
                    'product_option_value_descriptions.product_option_value_id',
                    '=',
                    'product_option_values.product_option_value_id'
                )
                ->where('product_option_values.product_id', '=', $productId)
                ->where('product_option_values.product_option_id', '=', $optionId)
                ->where($colName, '=', $colValue)
                ->first();
            if ($result) {
                break;
            }
        }

        $optionValueData = [
            'product_id'        => $productId,
            'product_option_id' => $optionId,
        ];
        if ($result) {
            $optKeys = $result->toArray();
            $update = true;
        } else {
            $optKeys = [
                'name'        => 'n/a',
                'sku'         => '',
                'quantity'    => 0,
                'sort_order'  => 0,
                'subtract'    => 0,
                'prefix'      => '$',
                'weight'      => 0,
                'weight_type' => 'lbs',
                'default'     => 0,
                'price'       => 0,
            ];
            $update = false;
        }
        foreach ($optKeys as $k => $v) {
            $optionValueData[$k] = $v;
            if (isset($data[$k])) {
                $optionValueData[$k] = $data[$k];
            }
        }
        //enable stock taking if quantity specified
        if ($optionValueData['quantity'] > 0) {
            $optionValueData['subtract'] = 1;
        }

        $weightDesc = WeightClassDescription::where('weight_class_id', '=', $weightClassId)->first();
        if ($weightDesc['unit']) {
            $optionValueData['weight_type'] = $weightDesc['unit'];
        }

        if ($update) {
            $optionValueId = $optKeys['product_option_value_id'];
            /** @var ProductOptionValue $optionValue */
            $optionValue = ProductOptionValue::with('description')->find($optionValueId);
            $optionValue->update($optionValueData);
            $optionValue->description->update($optionValueData);
        } else {
            $optionValueId = ProductOption::addProductOptionValueAndDescription($optionValueData);
        }

        if ($optionValueId && !empty($data['image'])) {
            //process images
            $this->migrateImages(
                $data,
                'product_option_value',
                $optionValueId,
                $data['name'],
                Registry::language()->getContentLanguageID()
            );
        }
        return $optionValueId;
    }

    /**
     * add from URL download
     *
     * @param array $data
     * @param string $object_txt_id
     * @param int $object_id
     * @param string $title
     * @param $language_id
     *
     * @return bool
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws AException
     */
    protected function migrateImages($data = [], $object_txt_id = '', $object_id = 0, $title = '', $language_id = 1)
    {
        $objects = [
            'products'             => 'Product',
            'categories'           => 'Category',
            'manufacturers'        => 'Brand',
            'product_option_value' => 'ProductOptionValue'
        ];

        if (!in_array($object_txt_id, array_keys($objects)) || !$data || !is_array($data)) {
            $this->toLog("Warning: Missing images for " . $object_txt_id);
            return true;
        }

        $language_list = Registry::language()->getAvailableLanguages();
        $rm = new AResourceManager();
        $rm->setType('image');
        //delete existing resources
        $rm->unmapAndDeleteResources($object_txt_id, $object_id, 'image');

        //IMAGE PROCESSING
        $data['image'] = (array)$data['image'];
        foreach ($data['image'] as $source) {
            if (empty($source)) {
                continue;
            } else {
                if (is_array($source)) {
                    //we have an array from list of values. Run again
                    $this->migrateImages(
                        ['image' => $source],
                        $object_txt_id,
                        $object_id,
                        $title,
                        $language_id
                    );
                    continue;
                }
            }
            //check if image is absolute path or remote URL
            $host = parse_url($source, PHP_URL_HOST);
            $image_basename = basename($source);
            //if url does not contain file extension - try to get mime type from url directly
            if ($host !== null && !pathinfo($image_basename, PATHINFO_EXTENSION)) {
                $mime = $this->getRemoteImageMime($source);
                if (str_contains($mime, 'image')) {
                    $ext = strtolower(substr($mime, 6));
                    $image_basename = microtime(true) . '.' . $ext;
                } else {
                    $this->toLog("Error: Unable to recognize file " . $source . " as image. Response mime type: " . $mime);
                    continue;
                }
            }

            $target = ABC::env('DIR_RESOURCES') . $rm->getTypeDir() . '/' . $image_basename;
            if (!is_dir(ABC::env('DIR_RESOURCES') . $rm->getTypeDir())) {
                @mkdir(ABC::env('DIR_RESOURCES') . $rm->getTypeDir());
            }

            if ($host === null) {
                //this is a path to file
                if (!copy($source, $target)) {
                    $this->toLog("Error: Unable to copy file " . $source . " to " . $target);
                    continue;
                }
            } else {
                //this is URL to image. Download first
                $fl = new AFile();
                if (($file = $fl->downloadFile($source)) === false) {
                    $this->toLog("Error: Unable to download file from " . $source);
                    continue;
                }
                if (!$fl->writeDownloadToFile($file, $target)) {
                    $this->toLog("Error: Unable to save downloaded file to " . $target);
                    continue;
                }
            }

            //save resource
            $resource = [
                'language_id'   => $language_id,
                'name'          => [],
                'title'         => [],
                'description'   => '',
                'resource_path' => $image_basename,
                'resource_code' => '',
            ];
            foreach ($language_list as $lang) {
                $resource['name'][$lang['language_id']] = $title;
                $resource['title'][$lang['language_id']] = $title;
            }
            $resource_id = $rm->addResource($resource);
            if ($resource_id) {
                $this->toLog("Map image resource : " . $image_basename . " " . $resource_id);
                $rm->mapResource($object_txt_id, $object_id, $resource_id);
            } else {
                $this->toLog("Error: Image resource can not be created. " . Registry::db()->error);
            }
        }

        return true;
    }

    protected function getRemoteImageMime($url)
    {
        $size = @getimagesize($url);
        return $size['mime'];
    }

    /**
     * @param string $field
     * @param string $value
     * @param int $languageId
     * @param int $storeId
     *
     * @return null
     * @throws Exception
     */
    public function getProductByField($field, $value, $languageId, $storeId)
    {
        if ($field == 'products.sku') {
            return $this->findProductIDByString((string)$value, (int)$languageId, (int)$storeId, 'sku');
        } elseif ($field == 'products.model') {
            return $this->findProductIDByString((string)$value, (int)$languageId, (int)$storeId, 'model');
        } elseif ($field == 'product_descriptions.name') {
            return $this->findProductIDByString((string)$value, (int)$languageId, (int)$storeId);
        }
        return null;
    }

    /**
     * @param string $needle
     * @param int $languageId
     * @param int $storeId
     * @param string $searchBy - can be 'name','model' or 'sku'
     * @return null
     */
    public function findProductIDByString(string $needle, int $languageId, int $storeId, $searchBy = 'name')
    {
        if (!$needle || !in_array($searchBy, ['name', 'model', 'sku'])) {
            return null;
        }
        $db = Registry::db();

        $query = Product::select('products.product_id')
            ->leftJoin(
                'product_descriptions',
                function ($join) use ($languageId) {
                    /** @var JoinClause $join */
                    $join->on('products.product_id', '=', 'product_descriptions.product_id')
                        ->where('product_descriptions.language_id', '=', $languageId);
                }
            )->join(
                'products_to_stores',
                function ($join) use ($storeId) {
                    /** @var JoinClause $join */
                    $join->on('products.product_id', '=', 'products_to_stores.product_id')
                        ->where('products_to_stores.store_id', '=', $storeId);
                }
            );
        if ($searchBy == 'name') {
            $query->whereRaw("LOWER(" . $db->table_name('product_descriptions') . "." . $searchBy . ") = '" . $db->escape(mb_strtolower($needle)) . "'");
        } elseif ($searchBy == 'model') {
            $query->whereRaw("LOWER(" . $db->table_name('products') . "." . $searchBy . ") = '" . $db->escape(mb_strtolower($needle)) . "'");
        } elseif ($searchBy == 'sku') {
            $query->whereRaw("LOWER(" . $db->table_name('products') . "." . $searchBy . ") = '" . $db->escape(mb_strtolower($needle)) . "'");
        }
        return $query->useCache('product')->first('product_id');
    }

    /**
     * @param string $manufacturer_name
     * @param int $sort_order
     * @param int $store_id
     *
     * @return int|null
     * @throws Exception
     */
    protected function processManufacturer($manufacturer_name, $sort_order, $store_id)
    {
        /** @var Manufacturer $result */
        $result = Manufacturer::select()
            ->whereRaw("LOWER(name) = '" . Registry::db()->escape(mb_strtolower($manufacturer_name)) . "'")
            ->first();
        $manufacturer_id = $result->manufacturer_id;
        if (!$manufacturer_id) {
            $manufacturer_id = Manufacturer::addManufacturer(
                [
                    'sort_order'         => $sort_order,
                    'name'               => $manufacturer_name,
                    'manufacturer_store' => [$store_id],
                ]
            );
            if ($manufacturer_id) {
                $this->toLog("Created manufacturer " . $manufacturer_name . " with ID " . $manufacturer_id);
            } else {
                $this->toLog("Error: Failed to create manufacturer " . $manufacturer_name . ".");
            }
        } else {
            //do this to run event listener on save
            $manufacturer = Manufacturer::find($manufacturer_id);
            $manufacturer?->touch();
        }
        return $manufacturer_id;
    }

    /**
     * @param array $data
     * @param int $language_id
     * @param int $store_id
     *
     * @return array
     * @throws Exception
     */
    protected function processCategories($data, $language_id, $store_id)
    {
        if (!is_array($data['category'])) {
            return [];
        }

        $ret = [];
        for ($i = 0; $i < count($data['category']); $i++) {
            //check if we have a tree in a form of array or just a category
            $categories = [];
            if (is_array($data['category'][$i])) {
                $categories = $data['category'][$i];
            } else {
                $categories[] = str_replace(',', '', $data['category'][$i]);
            }

            $categories = $this->checkCategoryTree($categories, $language_id, $store_id);

            $last_parent_id = 0;
            $index = 0;
            foreach ($categories as $c_name => $cid) {
                if ($c_name === '') {
                    continue;
                }
                //is parent?
                $is_parent = !($index + 1 == count($categories));
                //check if category exists with this name
                if ($is_parent) {
                    if (!$cid) {
                        $last_parent_id = $this->saveCategory($c_name, $language_id, $store_id, $last_parent_id);
                        if (!$last_parent_id) {
                            break;
                        }
                    } else {
                        $last_parent_id = $cid;
                    }
                } else {
                    //last node, leave category
                    if (!$cid) {
                        $cid = $this->saveCategory($c_name, $language_id, $store_id, $last_parent_id);
                    }
                    if ($cid) {
                        $ret[] = ['category_id' => $cid, 'parent_id' => $last_parent_id];
                    }
                    break;
                }
                $index++;
            }
        }
        return $ret;
    }

    /**
     * @param array $nameTree
     * @param $language_id
     * @param $store_id
     *
     * @return array
     * @throws Exception
     */
    protected function checkCategoryTree($nameTree = [], $language_id = 1, $store_id = 0)
    {
        $nameTree = array_values($nameTree);
        Category::setCurrentLanguageID($language_id);
        $output = array_flip($nameTree);
        foreach ($output as &$o) {
            $o = null;
        }
        $fullPath = [];
        $k = 0;

        //check if parent exists
        $parent_exists = $this->findCategoryByNameAndParent($nameTree[0], $language_id, $store_id, 0);
        if (!$parent_exists) {
            return $output;
        }

        foreach ($nameTree as $c_name) {
            if ($c_name === '') {
                continue;
            }
            $parentId = $fullPath ? $fullPath[$k - 1] : 0;
            $exist = $this->findCategoryByNameAndParent($c_name, $language_id, $store_id, $parentId);
            if ($exist) {
                $fullPath[$k] = $exist['category_id'];
                $output[$c_name] = $exist['category_id'];
            } else {
                $output[$c_name] = null;
            }
            $k++;
        }

        return $output;
    }

    protected function findCategoryByNameAndParent($category_name, $language_id, $store_id, $parent_id)
    {
        $db = Registry::db();
        $query = CategoryDescription::select(
            ['category_descriptions.category_id', 'category_descriptions.name', 'categories.path']
        )->whereRaw(
            "LCASE(" . $db->table_name('category_descriptions') . ".name) = '"
            . $db->escape(mb_strtolower($category_name)) . "'"
        )->join(
            'categories_to_stores',
            'category_descriptions.category_id',
            '=',
            'categories_to_stores.category_id'
        )->join(
            'categories',
            'categories.category_id',
            '=',
            'category_descriptions.category_id'
        )->where(
            [
                'category_descriptions.language_id' => $language_id,
                'categories_to_stores.store_id'     => $store_id,
            ]
        );

        if ($parent_id > 0) {
            $query->where('categories.parent_id', '=', $parent_id);
        } elseif ($parent_id == 0) {
            $query->whereNull('categories.parent_id');
        }
        /** @var CategoryDescription|Category $result */
        $result = $query->first();
        if ($result) {
            $output = [
                'category_id' => $result->category_id,
                'path'        => $result->path,
                'name'        => html_entity_decode($result->name, ENT_QUOTES, ABC::env('APP_CHARSET')),
            ];
        } else {
            $output = [];
        }
        return $output;
    }

    /**
     * @param string $category_name
     * @param int $language_id
     * @param int $store_id
     * @param int $pid
     *
     * @return int
     * @throws Exception
     */
    protected function saveCategory($category_name, $language_id, $store_id, $pid = 0)
    {
        $category_id = Category::addCategory(
            [
                'parent_id'            => $pid,
                'sort_order'           => 0,
                'status'               => 1,
                'category_description' => [
                    $language_id => [
                        'name' => $category_name,
                    ],
                ],
                'category_store'       => [$store_id],
            ]
        );
        if ($category_id) {
            $this->toLog("Created category '" . $category_name . "' with ID " . $category_id . ".");
        } else {
            $this->toLog("Error: Failed to create category '" . $category_name . "'.");
        }
        return $category_id;
    }

    /**
     * Map data from record based on the settings
     *
     * @param array $record
     * @param array $import_col
     * @param array $fields
     * @param array $split_col
     *
     * @return array
     */
    protected function buildDataMap($record, $import_col, $fields, $split_col)
    {
        $ret = [];
        $op_index = -1;
        $op_array = [];
        if (!is_array($import_col) || !is_array($fields)) {
            return $ret;
        }

        foreach ($fields as $index => $field) {
            if (empty($field)) {
                continue;
            }
            $arr = [];
            $field_val = $record[$import_col[$index]];
            $keys = array_reverse(explode('.', $field));
            if (end($keys) == 'product_options' && !empty($field_val)) {
                //map options special way
                //check if this is still same option. Or this is new name
                if (count($keys) == 2) {
                    if ($keys[0] == 'name') {
                        $op_array[++$op_index]['name'] = $field_val;
                    } else {
                        $tmp_index = ($op_index >= 0) ? $op_index : 0;
                        $op_array[$tmp_index][$keys[0]] = $field_val;
                    }
                } else {
                    if ($keys[0] == 'image') {
                        //leaf element
                        //check if we need to split the record data from list of values
                        if (isset($split_col) && !empty($split_col[$index])) {
                            $field_val = explode($split_col[$index], $field_val);
                            $field_val = array_map('trim', $field_val);
                        }
                        if (!is_array($field_val)) {
                            $field_val = [$field_val];
                        }
                        $arr['product_option_values']['image'][] = $field_val;

                        $tmp_index = ($op_index >= 0) ? $op_index : 0;
                        $op_array[$tmp_index] = array_merge_recursive($op_array[$tmp_index], $arr);
                    } else {
                        for ($i = 0; $i < count($keys) - 1; $i++) {
                            if ($i == 0) {
                                $arr = [$keys[$i] => $field_val];
                            } else {
                                $arr = [$keys[$i] => $arr];
                            }
                        }
                        $tmp_index = ($op_index >= 0) ? $op_index : 0;
                        $op_array[$tmp_index] = array_merge_recursive((array)$op_array[$tmp_index], $arr);
                    }
                }
            } else {
                foreach ($keys as $key) {
                    if ($key === reset($keys)) {
                        //leaf element
                        //check if we need to split the record data from list of values
                        if (isset($split_col) && !empty($split_col[$index])) {
                            $field_val = explode($split_col[$index], $field_val);
                            $field_val = array_map('trim', $field_val);
                        }
                        $arr[$key][] = $field_val;
                    } else {
                        $arr = [$key => $arr];
                    }
                }

                $ret = array_merge_recursive($ret, $arr);
            }
        }

        if ($op_array) {
            $ret = array_merge_recursive($ret, ['product_options' => $op_array]);
        }
        return $ret;
    }

    /**
     * @param array $arr
     *
     * @return array
     */
    protected function filterArray($arr = [])
    {
        $ret = [];
        if (!$arr || !is_array($arr)) {
            return $ret;
        }

        foreach ($arr as $key => $val) {
            //get only first element of data array
            $ret[$key] = reset($val);
        }
        return $ret;
    }

    /**
     * Get a value from the record based on the setting key
     *
     * @param string $key
     * @param array $record
     * @param array $fields
     * @param array $columns
     *
     * @return mixed|null
     */
    protected function getValueFromDataMap($key, $record, $fields, $columns)
    {
        $index = array_search($key, $fields);
        if ($index !== false) {
            return $record[$columns[$index]];
        }
        return null;
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    protected function toLog($message)
    {
        if (!$message || !$this->imp_log) {
            return false;
        }
        $this->imp_log->error($message);
        return true;
    }

    /**
     * Array wth configurations for import tables and fields
     *
     * @return array()
     */
    public function importTableCols()
    {
        $this->data['output'] = [
            'products'      => [
                'columns' => [
                    'products.status'                                  =>
                        [
                            'title' => 'Status (1 or 0)',
                            'alias' => 'status',
                        ],
                    'products.sku'                                     =>
                        [
                            'title'  => 'SKU (up to 64 chars)',
                            'update' => true,
                            'alias'  => 'sku',
                        ],
                    'products.model'                                   =>
                        [
                            'title'  => 'Model (up to 64 chars)',
                            'update' => true,
                            'alias'  => 'model',
                        ],
                    'product_descriptions.name'                        =>
                        [
                            'title'    => 'Name (up to 255 chars)',
                            'required' => true,
                            'update'   => true,
                            'alias'    => 'name',
                        ],
                    'product_descriptions.blurb'                       =>
                        [
                            'title' => 'Short Description',
                            'alias' => 'blurb',
                        ],
                    'product_descriptions.description'                 =>
                        [
                            'title' => 'Long Description',
                            'alias' => 'description',
                        ],
                    'product_descriptions.meta_keywords'               =>
                        [
                            'title' => 'Meta Keywords',
                            'alias' => 'meta keywords',
                        ],
                    'product_descriptions.meta_description'            =>
                        [
                            'title' => 'Meta Description',
                            'alias' => 'meta description',
                        ],
                    'products.keyword'                                 =>
                        [
                            'title' => 'SEO URL',
                            'alias' => 'seo url',
                        ],
                    'products.location'                                =>
                        [
                            'title' => 'Location (Text up to 128 chars)',
                            'alias' => 'warehouse',
                        ],
                    'products.quantity'                                =>
                        [
                            'title' => 'Quantity',
                            'alias' => 'stock',
                        ],
                    'products.minimum'                                 =>
                        [
                            'title' => 'Minimum Order Quantity',
                            'alias' => 'minimum quantity',
                        ],
                    'products.maximum'                                 =>
                        [
                            'title' => 'Maximum Order Quantity',
                            'alias' => 'maximum quantity',
                        ],
                    'products.price'                                   =>
                        [
                            'title' => 'Product price (In default currency)',
                            'alias' => 'price',
                        ],
                    'products.cost'                                    =>
                        [
                            'title' => 'Product Cost (In default currency)',
                            'alias' => 'cost',
                        ],
                    'products.shipping_price'                          =>
                        [
                            'title' => 'Fixed shipping price (In default currency)',
                            'alias' => 'shipping price',
                        ],
                    'products.tax_class_id'                            =>
                        [
                            'title' => 'Tax Class ID (Number, See tax settings)',
                            'alias' => 'tax id',
                        ],
                    'products.weight_class_id'                         =>
                        [
                            'title' => 'Weight Class ID (Number, See weight settings)',
                            'alias' => 'weight id',
                        ],
                    'products.length_class_id'                         =>
                        [
                            'title' => 'Length Class ID (Number, See length settings)',
                            'alias' => 'length id',
                        ],
                    'products.weight'                                  =>
                        [
                            'title' => 'Product Weight',
                            'alias' => 'weight',
                        ],
                    'products.length'                                  =>
                        [
                            'title' => 'Product Length',
                            'alias' => 'length',
                        ],
                    'products.width'                                   =>
                        [
                            'title' => 'Product Width',
                            'alias' => 'width',
                        ],
                    'products.height'                                  =>
                        [
                            'title' => 'Product Height',
                            'alias' => 'height',
                        ],
                    'products.subtract'                                =>
                        [
                            'title' => 'Track Stock Setting (1 or 0)',
                            'alias' => 'track stock',
                        ],
                    'products.free_shipping'                           =>
                        [
                            'title' => 'Free shipping (1 or 0)',
                            'alias' => 'free shipping',
                        ],
                    'products.shipping'                                =>
                        [
                            'title' => 'Enable Shipping (1 or 0)',
                            'alias' => 'requires shipping',
                        ],
                    'products.sort_order'                              =>
                        [
                            'title' => 'Sorting Order',
                            'alias' => 'sort order',
                        ],
                    'products.call_to_order'                           =>
                        [
                            'title' => 'Order only by calling (1 or 0)',
                            'alias' => 'call to order',
                        ],
                    'products.date_available'                          =>
                        [
                            'title' => 'Date Available (YYYY-MM-DD format)',
                            'alias' => 'date available',
                        ],
                    'categories.category'                              =>
                        [
                            'title'      => 'Category Name or Tree',
                            'split'      => 1,
                            'multivalue' => 1,
                            'alias'      => 'category',
                        ],
                    'manufacturers.manufacturer'                       =>
                        [
                            'title' => 'Manufacturer name',
                            'alias' => 'manufacturer name',
                        ],
                    'images.image'                                     =>
                        [
                            'title'      => "Image or List of URLs/Paths",
                            'split'      => 1,
                            'multivalue' => 1,
                            'alias'      => 'image url',
                        ],
                    'product_options.name'                             =>
                        [
                            'title'      => 'Option name (up to 255 chars)',
                            'multivalue' => 1,
                            'alias'      => 'option name',
                        ],
                    'product_options.sort_order'                       =>
                        [
                            'title'      => 'Option sorting order (numeric)',
                            'multivalue' => 1,
                            'alias'      => 'option sort order',
                        ],
                    'product_options.status'                           =>
                        [
                            'title'      => 'Option status (1 or 0)',
                            'multivalue' => 1,
                            'alias'      => 'option status',
                        ],
                    'product_options.required'                         =>
                        [
                            'title'      => 'Option Required (1 or 0)',
                            'multivalue' => 1,
                            'alias'      => 'option required',
                        ],
                    'product_options.product_option_values.name'       =>
                        [
                            'title'      => 'Option value name (up to 255 chars)',
                            'multivalue' => 1,
                            'alias'      => 'option value name',
                        ],
                    'product_options.product_option_values.sku'        =>
                        [
                            'title'      => 'Option value sku (up to 255 chars)',
                            'multivalue' => 1,
                            'alias'      => 'option value sku',
                        ],
                    'product_options.product_option_values.quantity'   =>
                        [
                            'title'      => 'Option value quantity',
                            'multivalue' => 1,
                            'alias'      => 'option value quantity',
                        ],
                    'product_options.product_option_values.price'      =>
                        [
                            'title'      => 'Option value price',
                            'multivalue' => 1,
                            'alias'      => 'option value price',
                        ],
                    'product_options.product_option_values.default'    =>
                        [
                            'title'      => 'Option value default selection (1 or 0)',
                            'multivalue' => 1,
                            'alias'      => 'option value default',
                        ],
                    'product_options.product_option_values.weight'     =>
                        [
                            'title'      => 'Option value weight (numeric)',
                            'multivalue' => 1,
                            'alias'      => 'option value weight',
                        ],
                    'product_options.product_option_values.sort_order' =>
                        [
                            'title'      => 'Option value sort order (1 or 0)',
                            'multivalue' => 1,
                            'alias'      => 'option value sort order',
                        ],
                    'product_options.product_option_values.image'      =>
                        [
                            'title'      => 'Option value image or List of URLs/Paths',
                            'split'      => 1,
                            'multivalue' => 1,
                            'alias'      => 'option value image',
                        ],
                ],
            ],
            'categories'    => [
                'columns' => [
                    'categories.status'                      =>
                        [
                            'title' => 'Status (0 or 1)',
                            'alias' => 'status',
                        ],
                    'categories.sort_order'                  =>
                        [
                            'title' => 'Sorting Order(Number)',
                            'alias' => 'sort order',
                        ],
                    'categories.keyword'                     =>
                        [
                            'title' => 'SEO URL',
                            'alias' => 'seo url',
                        ],
                    'category_descriptions.name'             =>
                        [
                            'title'      => 'Category Name or Tree',
                            'required'   => true,
                            'split'      => 1,
                            'multivalue' => 1,
                            'alias'      => 'name',
                        ],
                    'category_descriptions.description'      =>
                        [
                            'title' => 'Description',
                            'alias' => 'description',
                        ],
                    'category_descriptions.meta_keywords'    =>
                        [
                            'title' => 'Meta Keywords',
                            'alias' => 'meta keywords',
                        ],
                    'category_descriptions.meta_description' =>
                        [
                            'title' => 'Meta Description',
                            'alias' => 'meta description',
                        ],
                    'images.image'                           =>
                        [
                            'title'      => "Image or List of URLs/Paths",
                            'split'      => 1,
                            'multivalue' => 1,
                            'alias'      => 'image',
                        ],
                ],
            ],
            'manufacturers' => [
                'columns' => [
                    'manufacturers.sort_order' =>
                        [
                            'title'   => 'Sorting Order (Number)',
                            'default' => 0,
                            'alias'   => 'sort order',
                        ],
                    'manufacturers.keyword'    =>
                        [
                            'title' => 'SEO URL',
                            'alias' => 'seo url',
                        ],
                    'manufacturers.name'       =>
                        [
                            'title'    => 'Name (up to 64 chars)',
                            'required' => true,
                            'alias'    => 'name',
                        ],
                    'images.image'             =>
                        [
                            'title'      => "Image or List of URLs/Paths",
                            'split'      => 1,
                            'multivalue' => 1,
                            'alias'      => 'image',
                        ],
                ],
            ],
        ];
        //allow change list from hooks
        Registry::extensions()->hk_UpdateData($this, __FUNCTION__);

        return $this->data['output'];
    }
}