<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 *
 */

namespace abc\models\catalog;

use abc\core\ABC;
use abc\core\engine\HtmlElementFactory;
use abc\core\engine\Registry;
use abc\core\lib\AResourceManager;
use abc\core\lib\AttributeManager;
use abc\models\BaseModel;
use abc\models\QueryBuilder;
use Carbon\Carbon;
use Exception;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class ProductOption
 *
 * @property int $product_option_id
 * @property int $attribute_id
 * @property int $product_id
 * @property int $group_id
 * @property int $sort_order
 * @property int $status
 * @property string $element_type
 * @property int $required
 * @property string $regexp_pattern
 * @property string $settings
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property ProductOptionValue $values
 * @property Product $product
 * @property ProductOptionDescription $description
 * @property ProductOptionDescription $descriptions
 *
 * @method static ProductOption find(int $product_option_id) ProductOption
 * @method static ProductOption create(array $attributes) ProductOption
 * @method static ProductOption first() ProductOption
 *
 * @package abc\models
 */
class ProductOption extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions', 'values'];

    protected $mainClassName = Product::class;
    protected $mainClassKey = 'product_id';
    /**
     * @var string
     */
    protected $primaryKey = 'product_option_id';

    protected $touches = ['product'];

    /**
     * @var array
     */
    protected $casts = [
        'attribute_id' => 'int',
        'product_id'   => 'int',
        'group_id'     => 'int',
        'sort_order'   => 'int',
        'status'       => 'int',
        'required'     => 'int',
        'settings'     => 'serialized',
    ];

    /** @var array */
    protected $dates = [
        'date_added',
        'date_modified',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'product_id',
        'attribute_id',
        'group_id',
        'sort_order',
        'status',
        'element_type',
        'required',
        'regexp_pattern',
        'settings',
    ];

    protected $rules = [
        /** @see validate() */
        'product_id'   => [
            'checks'   => [
                'integer',
                'required',
                'exists:products',
            ],
            'messages' => [
                '*' => ['default_text' => 'Product ID is not Integer or absent in the products table!'],
            ],
        ],
        'attribute_id' => [
            'checks'   => [
                'integer',
                'nullable',
                'exists:global_attributes',
            ],
            'messages' => [
                '*' => ['default_text' => 'Attribute ID is not Integer or not presents in global_attributes table!'],
            ],
        ],
        'group_id'     => [
            'checks'   => [
                'integer',
                'nullable',
            ],
            'messages' => [
                '*' => ['default_text' => 'Group ID is not integer!'],
            ],
        ],
        'sort_order'   => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],
        'status'       => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not boolean!',
                ],
            ],
        ],

        'element_type' => [
            'checks'   => [
                'string',
                'size:1',
                /** @see __construct() method */
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be 1 character length and presents in element_types list of AHtml class!',
                ],
            ],
        ],

        'required' => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not boolean!',
                ],
            ],
        ],

        'regexp_pattern' => [
            'checks'   => [
                'string',
                'nullable',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Blurb must be less than 1500 characters!',
                ],
            ],
        ],
    ];

    public function __construct(array $attributes = [])
    {
        $letters = array_keys(HtmlElementFactory::getAvailableElements());
        $this->rules['element_type']['checks'][] = Rule::in($letters);
        parent::__construct($attributes);
    }

    public function setSettingsAttribute($value)
    {
        $this->attributes['settings'] = serialize($value);
    }

    /**
     * @return BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * @return HasMany
     */
    public function descriptions()
    {
        return $this->hasMany(ProductOptionDescription::class, 'product_option_id');
    }

    /**
     * @return HasOne
     */
    public function description()
    {
        return $this->hasOne(ProductOptionDescription::class, 'product_option_id')
                    ->where('language_id', '=', static::$current_language_id);
    }

    /**
     * @return HasMany
     */
    public function values()
    {
        return $this->hasMany(ProductOptionValue::class, 'product_option_id');
    }

    /**
     * @return false|array
     * @throws InvalidArgumentException
     */
    public function getAllData()
    {
        $cache_key = 'product.alldata.'.$this->getKey();
        $data = $this->cache->get($cache_key);
        if ($data === null) {
            $this->load('descriptions', 'values');
            $data = $this->toArray();
            foreach ($this->values as $optionValue) {
                $data['values'][] = $optionValue->getAllData();
            }
            $this->cache->put($cache_key, $data);
        }
        return $data;
    }

    public function delete()
    {
        $this->load('values');
        if ($this->values) {
            /**
             * @var AResourceManager $rm
             */
            $rm = ABC::getObjectByAlias('AResourceManager');
            $rm->setType('image');
            foreach ($this->values as $option_value) {
                //Remove previous resources of object
                $rm->unmapAndDeleteResources('product_option_value', $option_value->product_option_value_id);
            }
        }
        parent::delete();
    }

    /**
     * @param $po_ids
     *
     * @return bool|Collection
     */
    public static function getProductOptionsByIds($po_ids)
    {

        if (!$po_ids || !is_array($po_ids)) {
            return false;
        }
        /**
         * @var QueryBuilder $query
         */
        $query = static::select(
            [
                'product_options.*',
                'product_option_values.*',
                'product_option_descriptions.name as option_name',
                'product_option_value_descriptions.name as option_value_name',

            ]
        );
        $query->whereIn('product_options.product_option_id', $po_ids)
              ->leftJoin(
                  'product_option_descriptions',
                  function ($join) {
                      /** @var JoinClause $join */
                      $join->on('product_option_descriptions.product_option_id', '=',
                          'product_options.product_option_id')
                           ->where('product_option_descriptions.language_id', '=',
                               static::$current_language_id);
                  }
              )->leftJoin(
                'product_option_values',
                'product_options.product_option_id',
                '=',
                'product_option_values.product_option_id'
            )->leftJoin(
                'product_option_value_descriptions',
                function ($join) {
                    /** @var JoinClause $join */
                    $join->on(
                        'product_option_value_descriptions.product_option_value_id',
                        '=',
                        'product_option_values.product_option_value_id'
                    )->where(
                        'product_option_value_descriptions.language_id',
                        '=',
                        static::$current_language_id
                    );
                }
            )->orderBy('product_options.product_option_id');

        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, func_get_args());
        return $query->get();
    }

    /**
     * @param array $indata - must contains product_id, product_option_value_id
     *
     * @return int|null
     * @throws Exception
     */
    public static function addProductOptionValueAndDescription(array $indata)
    {
        if (empty($indata) || !$indata['product_id'] || !$indata['product_option_id']) {
            return false;
        }

        $optionData = $indata;
        if (is_array($indata['attribute_value_id'])) {
            unset($optionData['attribute_value_id']);
        } else {
            $optionData['attribute_value_id'] = (int)$optionData['attribute_value_id'];
            if (!$optionData['attribute_value_id']) {
                unset($optionData['attribute_value_id']);
            } else {
                $indata['attribute_value_id'] = [$optionData['attribute_value_id']];
            }

        }

        /**
         * @var AttributeManager $am
         */
        $am = ABC::getObjectByAlias('AttributeManager');
        //build grouped attributes if this is a parent attribute
        if (is_array($indata['attribute_value_id'])) {
            //add children option values from global attributes
            $groupData = [];
            foreach ($indata['attribute_value_id'] as $child_option_id => $attribute_value_id) {
                #special data for grouped options. will be serialized in model mutator
                $groupData[] = [
                    'attr_id'   => $child_option_id,
                    'attr_v_id' => $attribute_value_id,
                ];
            }
            $optionData['grouped_attribute_data'] = $groupData;
        }

        $optionValue = ProductOptionValue::create($optionData);
        $optionValueId = $optionValue->getKey();

        //Build options value descriptions
        if (is_array($indata['attribute_value_id'])) {
            //add children option values description from global attributes
            $group_description = [];
            $descr_names = [];
            foreach ($indata['attribute_value_id'] as $child_option_id => $attribute_value_id) {
                #special insert for grouped options
                foreach ($am->getAttributeValueDescriptions($attribute_value_id) as $language_id => $name) {
                    $group_description[$language_id][] = [
                        'attr_v_id' => $attribute_value_id,
                        'name'      => $name,
                    ];
                    $descr_names[$language_id][] = $name;
                }
            }

            // Insert generic merged name
            $grouped_names = null;
            foreach ($descr_names as $language_id => $name) {
                ProductOptionValueDescription::create(
                    [
                        'product_id'              => $optionValue->product_id,
                        'product_option_value_id' => $optionValueId,
                        'language_id'             => $language_id,
                        'name'                    => implode(' / ', $name),
                        //note: serialized data (array)
                        'grouped_attribute_names' => $group_description[$language_id],
                    ]
                );
            }

        } else {
            if (!$indata['attribute_value_id']) {
                //We save custom option value for current language
                if (isset($indata['descriptions'])) {
                    $valueDescriptions = $indata['descriptions'];
                } elseif (isset($indata['description'])) {
                    $valueDescriptions = [
                        static::$current_language_id => [
                            'name' => ($indata['description']['name'] ?? 'Unknown'),
                        ],
                    ];
                } elseif ($indata['name']) {
                    $valueDescriptions = [
                        static::$current_language_id =>
                            [
                                'name' => ($indata['name'] ?? 'Unknown'),
                            ],
                    ];
                } else {
                    $valueDescriptions = [
                        static::$current_language_id =>
                            [
                                'name' => 'Unknown',
                            ],
                    ];
                }
            } else {
                //We have global attributes, copy option value text from there.
                $valueDescriptions = $am->getAttributeValueDescriptions((int)$indata['attribute_value_id']);
            }

            foreach ($valueDescriptions as $language_id => $description) {
                $language_id = (int)$language_id;
                if (!$language_id) {
                    throw new Exception('Wrong format of input data! Description of value must have the language ID as a key!');
                }

                $desc = $description;
                $desc['product_id'] = $optionValue->product_id;
                $desc['product_option_value_id'] = $optionValueId;
                $desc['language_id'] = $language_id;

                ProductOptionValueDescription::create($desc);
            }
        }
        Registry::cache()->flush('product');
        return $optionValueId;
    }

    /**
     * @param array $indata - must contains product_id
     *
     * @return bool|mixed
     * @throws Exception
     */
    public static function addProductOption($indata)
    {
        if (empty($indata) || !$indata['product_id']) {
            return false;
        }
        /** @var AttributeManager $am */
        $am = ABC::getObjectByAlias('AttributeManager');
        $attribute = $am->getAttribute($indata['attribute_id']);

        if ($attribute) {
            $indata['element_type'] = $attribute['element_type'];
            $indata['required'] = $attribute['required'];
            $indata['regexp_pattern'] = $attribute['regexp_pattern'];
            $indata['placeholder'] = $attribute['placeholder'];
            $indata['sort_order'] = $attribute['sort_order'];
            $indata['settings'] = $attribute['settings'];
        } else {
            $indata['placeholder'] = $indata['option_placeholder'];
        }

        $option = ProductOption::create($indata);
        $product_option_id = $option->getKey();

        if ($indata['option_name']) {
            $indata['name'] = $indata['option_name'];
        }
        $indata['product_option_id'] = $product_option_id;

        if (!empty($indata['option_name'])) {
            $attributeDescriptions = [
                static::$current_language_id => $indata,
            ];
        } else {
            $attributeDescriptions = $am->getAttributeDescriptions($indata['attribute_id']);
        }

        foreach ($attributeDescriptions as $language_id => $description) {
            $description['product_id'] = $indata['product_id'];
            $description['product_option_id'] = $indata['product_option_id'];
            $description['language_id'] = $language_id;
            $description['option_placeholder'] = $indata['placeholder'];
            ProductOptionDescription::create($description);
        }

        //add empty option value for single value attributes
        $elements_with_options = HtmlElementFactory::getElementsWithOptions();
        if (!in_array($indata['element_type'], $elements_with_options)) {
            ProductOptionValue::create($indata);
        }

        return $product_option_id;
    }

    public static function updateProductOptionValues($indata)
    {
        if (!is_array($indata['product_option_value_id']) || !$indata['product_option_id'] || !$indata['product_id']) {
            return false;
        }

        foreach ($indata['product_option_value_id'] as $valueId => $status) {
            $option_value_data = [
                'product_id'        => $indata['product_id'],
                'product_option_id' => $indata['product_option_id'],
                'default'           => ($indata['default_value'] == $valueId ? 1 : 0),
            ];

            foreach ($indata as $key => $value) {
                if (is_array($value) && isset($value[$valueId])) {
                    $option_value_data[$key] = $indata[$key][$valueId];
                }
            }

            //Check if new, delete or update
            if ($status == 'delete' && strpos($valueId, 'new') === false) {
                //delete this option value for all languages
                $value = ProductOptionValue::find($valueId);
                if ($value) {
                    $value->forceDelete();
                }
            } else {
                if ($status == 'new') {
                    // Need to create new option value
                    $indata = $option_value_data;
                    ProductOption::addProductOptionValueAndDescription($option_value_data);
                } else {
                    //Existing need to update
                    static::updateProductOptionValueAndDescription(
                        $valueId,
                        $option_value_data);
                }
            }
        }
        return true;
    }

    public static function updateProductOptionValueAndDescription($pd_opt_val_id, $inData)
    {
        $data = $inData;
        $language_id = $data['language_id'] ?? static::$current_language_id;
        $product_id = $data['product_id'];
        if (is_array($data['attribute_value_id']) || !$data['attribute_value_id']) {
            unset($data['attribute_value_id']);
        } else {
            $data['attribute_value_id'] = (int)$data['attribute_value_id'];
            if (!$data['attribute_value_id']) {
                unset($data['attribute_value_id']);
            }
        }

        /**
         * @var AttributeManager $am
         */
        $am = ABC::getObjectByAlias('AttributeManager');
        //build grouped attributes if this is a parent attribute
        if (is_array($inData['attribute_value_id'])) {
            //update children option values from global attributes
            $groupData = [];
            foreach ($inData['attribute_value_id'] as $child_option_id => $attr_val_id) {
                #special serialized data for grouped options
                $groupData[$child_option_id] = [
                    'attr_id'   => (int)$child_option_id,
                    'attr_v_id' => (int)$attr_val_id,
                ];
            }
            $data['grouped_attribute_data'] = $groupData;
        }

        $optionValue = ProductOptionValue::find($pd_opt_val_id);
        if ($optionValue) {
            $optionValue->update($data);
        }

        if (is_array($inData['attribute_value_id'])) {
            //update children option values description from global attributes
            $group_description = [];
            $descr_names = [];
            foreach ($data['attribute_value_id'] as $child_option_id => $attr_val_id) {
                #special insert for grouped options
                foreach ($am->getAttributeValueDescriptions($attr_val_id) as $lang_id => $name) {
                    if ($language_id == $lang_id) {
                        $group_description[$language_id][] = [
                            'attr_v_id' => $attr_val_id,
                            'name'      => $name,
                        ];
                        $descr_names[$language_id][] = $name;
                    }
                }
            }
            // update generic merged name
            foreach ($descr_names as $lang_id => $name) {
                if ($language_id == $lang_id && count($group_description[$language_id])) {
                    $group_description[$language_id][] = $name;

                    $upd = ['name' => implode(' / ', $name)];
                    if ($group_description[$language_id]) {
                        //note: serialized data (array)
                        $upd['grouped_attribute_names'] = $group_description[$language_id];
                    }
                    ProductOptionValueDescription::where(
                        [
                            'product_id'              => $product_id,
                            'product_option_value_id' => $pd_opt_val_id,
                            'language_id'             => $language_id,
                        ]
                    )->update($upd);
                }
            }
        } else {
            if (!$inData['attribute_value_id']) {
                $exist = ProductOptionValueDescription::where(
                    [
                        'product_id'              => $product_id,
                        'product_option_value_id' => $pd_opt_val_id,
                        'language_id'             => $language_id,
                    ]
                )->first();
                if ($exist) {
                    $exist->update(['name' => $data['name']]);
                } else {
                    ProductOptionValueDescription::create(
                        [
                            'product_id'              => $product_id,
                            'product_option_value_id' => $pd_opt_val_id,
                            'name'                    => $data['name'],
                            'language_id'             => $language_id,
                        ]
                    );
                }
            } else {
                $valueDescriptions = $am->getAttributeValueDescriptions((int)$inData['attribute_value_id']);
                foreach ($valueDescriptions as $lang_id => $name) {
                    if ($language_id == $lang_id) {
                        //Update only language that we currently work with
                        ProductOptionValueDescription::where(
                            [
                                'product_id'              => $product_id,
                                'product_option_value_id' => $pd_opt_val_id,
                                'language_id'             => $language_id,
                            ]
                        )->update(['name' => $name]);
                    }
                }
            }
        }

    }

    /**
     * check attribute before add to product options
     * cant add attribute that is already in group attribute that assigned to product
     *
     * @param $product_id
     * @param $attribute_id
     *
     * @return int
     * @throws \Exception
     */
    public static function isProductGroupOption($product_id, $attribute_id = null)
    {
        return ProductOption::where(
            [
                'product_id'   => $product_id,
                'attribute_id' => $attribute_id,
            ]
        )->whereNotNull('group_id')
                            ->count();
    }

    public function getProductOptionValueArray($option_value_id)
    {

        $product_option_value = ProductOptionValue::with('description')
                                                  ->whereNull('group_id')
                                                  ->find($option_value_id);
        if (!$product_option_value) {
            return [];
        }
        //when asking value of another product - throw exception
        if ($this->product_id != $product_option_value->product_id) {
            throw new Exception('Option value not found for productID '.$this->product_id);
        }

        $result = $product_option_value->toArray();

//        $option_value = $product_option_value->row;
//        $value_description_data = [];
//        $value_description = $this->db->query(
//            "SELECT *
//            FROM ".$this->db->table_name("product_option_value_descriptions")."
//            WHERE product_option_value_id = '".(int)$option_value['product_option_value_id']."'");
//
//        foreach ($value_description->rows as $description) {
//            //regular option value name
//            $value_description_data[$description['language_id']]['name'] = $description['name'];
//            //get children (grouped options) individual names array
//            if ($description['grouped_attribute_names']) {
//                $value_description_data[$description['language_id']]['children_options_names'] =
//                    unserialize($description['grouped_attribute_names']);
//            }
//        }

        $result = [
            'product_option_value_id' => $option_value['product_option_value_id'],
            'language'                => $value_description_data,
            'sku'                     => $option_value['sku'],
            'quantity'                => $option_value['quantity'],
            'subtract'                => $option_value['subtract'],
            'price'                   => $option_value['price'],
            'prefix'                  => $option_value['prefix'],
            'weight'                  => $option_value['weight'],
            'weight_type'             => $option_value['weight_type'],
            'attribute_value_id'      => $option_value['attribute_value_id'],
            'grouped_attribute_data'  => $option_value['grouped_attribute_data'],
            'sort_order'              => $option_value['sort_order'],
            'default'                 => $option_value['default'],
        ];

        //get children (grouped options) data
        $child_option_values = unserialize($result['grouped_attribute_data']);
        if (is_array($child_option_values) && sizeof($child_option_values)) {
            $result['children_options'] = [];
            foreach ($child_option_values as $child_value) {
                $result['children_options'][$child_value['attr_id']] = (int)$child_value['attr_v_id'];
            }
        }

        return $result;
    }
}

