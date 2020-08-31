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
 */

namespace abc\commands;

use abc\commands\base\BaseCommand;
use abc\core\ABC;
use abc\controllers\admin\ControllerPagesToolCache;
use abc\core\engine\Registry;
use abc\core\lib\AConfig;
use abc\core\lib\AConnect;
use abc\core\lib\AContentManager;
use abc\core\lib\ACurrency;
use abc\core\lib\ALanguageManager;
use abc\models\admin\ModelSettingStore;
use abc\models\admin\ModelToolInstallUpgradeHistory;
use abc\models\catalog\Category;

/**
 * Class Cache
 *
 * @package abc\commands
 */
class Cache extends BaseCommand
{
    public $errors = [];
    protected $results = [];
    protected $languages = [];
    protected $currencies = [];
    /**
     * @var AConnect
     */
    protected $connect;

    public function validate(string $action, array &$options)
    {
        $action = !$action ? 'create' : $action;
        //if now options - check action
        if (!$options) {
            if (!in_array($action, ['help', 'create', 'clear'])) {
                return ['Error: Unknown Action Parameter!'];
            }
        }

        return [];
    }

    public function run(string $action, array $options)
    {
        parent::run($action, $options);
        $result = false;
        if (!in_array($action, ['create', 'clear']) || !$options) {
            return ['Error: Unknown action or missing option.'];
        }
        //looking for "ALL" parameter in option set. If presents - skip other.
        $opt_list = $this->getOptionList();
        if ($action == 'clear') {
            $k = array_search('all', array_keys($options));
            if ($k !== false) {
                $options = [
                    'media'                   => 1,
                    'install_upgrade_history' => 1,
                    'logs'                    => 1,
                    'html_cache'              => 1,
                    'all'                     => 1,
                ];
            }
        }

        foreach (array_keys($options) as $cache_section) {
            $alias = $opt_list[$action]['arguments']['--'.$cache_section]['alias'];
            if (!$alias) {
                continue;
            }
            $cache_groups = explode(',', $alias);
            $cache_groups = array_map('trim', $cache_groups);

            if ($action == 'clear') {
                $result = $this->processClear($cache_groups);
                if ($result) {
                    $this->write("Success: $cache_section cache was cleared.");
                }
            } elseif ($action == 'create') {
                $result = $this->processCreate($cache_section);
                if ($result) {
                    $this->write("Success: Cache have been successfully processed.");
                    if ($this->results) {
                        $this->write("Content pages count: ".$this->results['contents']);
                        $this->write("Category pages count: ".$this->results['categories']);
                        $this->write("Product pages count: ".$this->results['products']);
                    }
                }
            }
        }

        return $result ? true : $this->errors;
    }

    protected function processCreate($action = '')
    {
        //clear all cache if need to rebuild
        if ($action == 'rebuild') {
            $opt_list = $this->getOptionList();
            $this->processClear([
                'all'                     => '*',
                'media'                   => $opt_list['clear']['arguments']['--media']['alias'],
                'install_upgrade_history' => $opt_list['clear']['arguments']['--install_upgrade_history']['alias'],
                'logs'                    => $opt_list['clear']['arguments']['--logs']['alias'],
                'html_cache'              => $opt_list['clear']['arguments']['--html_cache']['alias'],
            ]);
        }

        $this->errors = [];
        $registry = Registry::getInstance();
        /**
         * @var ModelSettingStore $store_model
         */
        $store_model = $registry->get('load')->model('setting/store');
        $stores = $store_model->getStores();

        $lang_obj = new ALanguageManager($registry);
        $this->languages = $lang_obj->getActiveLanguages();

        $currencies_obj = new ACurrency($registry);
        $this->currencies = $currencies_obj->getCurrencies();

        $this->connect = new AConnect(true, true);

        foreach ($stores as $store) {
            $store_settings = $store_model->getStore($store['store_id']);
            $store_url = $store_settings['config_url'];
            if (!$store_url) {
                continue;
            }
            //load settings for store into config
            new AConfig($registry, $store_url);
            //request for home page
            $this->connect->getDataHeaders($store_url);
            //override store_url in env for seo-urls
            ABC::env('HTTP_SERVER', $store_url, true);

            //loop by all content pages
            $cm = new AContentManager();
            $contents = $cm->getContents([], 'default', $store['store_id']);
            foreach ($contents as $content) {
                $seo_url = $registry->get('html')->getSEOURL('content/content', '&content_id='.$content['content_id']);
                //loop for all variants
                $this->touchUrl($seo_url);
                $this->results['contents']++;
            }

            //loop by all categories of store
            $categories = Category::getCategoriesData(['store_id' => $store['store_id']]);
            foreach ($categories as $category) {
                $seo_url = $registry->get('html')->getSEOURL(
                    'product/category',
                    '&category_id='.$category['category_id']
                );
                //loop for all variants
                $this->touchUrl($seo_url);
                $this->results['categories']++;
            }

            //loop by all products of store
            $registry->get('load')->model('catalog/product', 'storefront');
            /**
             * @var \abc\models\storefront\ModelCatalogProduct $model
             */
            $model = $registry->get('model_catalog_product');
            $total_products = $model->getTotalProducts(['store_id' => $store['store_id']]);
            $i = 0;
            while ($i <= $total_products) {
                $products = $model->getProducts(
                    [
                        'store_id' => $store['store_id'],
                        'start'    => $i,
                        'limit'    => 20,
                    ]);
                foreach ($products as $product) {
                    $seo_url = $registry->get('html')->getSEOURL(
                        'product/product',
                        '&product_id='.$product['product_id']
                    );
                    //loop for all variants
                    $this->touchUrl($seo_url);
                    $this->results['products']++;
                }

                $i += 20;
            }
        }

        return true;
    }

    protected function touchUrl($url)
    {
        foreach ($this->languages as $lang) {
            foreach ($this->currencies as $curr) {
                $this->connect->setCurlOptions(
                    [
                        CURLOPT_COOKIE => 'language='.$lang['code'].'; currency='.$curr['code'],
                    ], false);
                $this->connect->getDataHeaders($url);
                sleep(1);
            }
        }
    }

    protected function processClear($cache_groups)
    {
        $this->errors = [];
        $registry = Registry::getInstance();
        $cache = $registry->get('cache');
        $lang_obj = new ALanguageManager($registry);
        $languages = $lang_obj->getActiveLanguages();
        $registry->get('load')->model('setting/store');
        /**
         * @var ModelSettingStore $model
         */
        $model = $registry->get('model_setting_store');
        $stores = $model->getStores();

        foreach ($cache_groups as $group) {
            if ($group == 'media') {
                try {
                    $filename = ABC::env('DIR_APP');
                    $filename .= 'controllers'.DS;
                    $filename .= 'admin'.DS;
                    $filename .= 'pages'.DS;
                    $filename .= 'tool'.DS;
                    $filename .= 'cache.php';
                    require_once($filename);
                    $cc = new ControllerPagesToolCache($registry, 0, 'tool/cache');
                    $cc->deleteThumbnails();
                } catch (\Exception $e) {
                    $this->errors[] = 'Cannot to delete thumbnails. '.$e->getMessage();
                }
            } elseif ($group == 'install_upgrade_history') {
                try {
                    $registry->get('load')->model('tool/install_upgrade_history');
                    /**
                     * @var ModelToolInstallUpgradeHistory $model
                     */
                    $model = $registry->get('model_tool_install_upgrade_history');
                    $model->deleteData();
                } catch (\Exception $e) {
                    $this->errors[] = 'Cannot to clear application history. '.$e->getMessage();
                }
            } elseif ($group == 'logs') {
                //TODO: make clear logs
                $args = ABC::getClassDefaultArgs('ALog');
                $file = ABC::env('DIR_LOGS').$args[0]['app'];
                if (is_file($file)) {
                    unlink($file);
                }
            } elseif ($group == 'html_cache') {
                $cache->flush('html_cache');
            } else {
                $cache->flush($group);
                foreach ($languages as $lang) {
                    foreach ($stores as $store) {
                        $cache->flush($group."_".$store['store_id']."_".$lang['language_id']);
                    }
                }
            }
        }

        return $this->errors ? false : true;
    }

    public function finish(string $action, array $options)
    {
        parent::finish($action, $options);
        return true;
    }

    protected function getOptionList()
    {
        return [
            'create' =>
                [
                    'description' => 'create cache',
                    'arguments'   => [

                        '--build'   => [
                            'description'   => 'create all cache',
                            'default_value' => '',
                            'required'      => false,
                            'alias'         => '*',
                        ],
                        '--rebuild' => [
                            'description'   => 'create all cache',
                            'default_value' => '',
                            'required'      => false,
                            'alias'         => '*',
                        ],
                    ],
                    'example'     => 'php abcexec cache:create --all',
                ],
            'clear'  =>
                [
                    'description' => 'clear cache',
                    'arguments'   => [

                        '--all'                 => [
                            'description'   => 'Clear all cache data',
                            'default_value' => '',
                            'required'      => false,
                            'alias'         => '*',
                        ],
                        '--html_cache'          => [
                            'description'   => 'Clear html-cache',
                            'default_value' => '',
                            'required'      => false,
                            'alias'         => 'html_cache',
                        ],
                        '--layouts'             => [
                            'description'   => 'Clear cache of layouts, pages, blocks data',
                            'default_value' => '',
                            'required'      => false,
                            'alias'         => 'layout, pages, blocks',
                        ],
                        '--forms'               => [
                            'description'   => 'Clear cache of dynamical html-forms data',
                            'default_value' => '',
                            'required'      => false,
                            'alias'         => 'forms',
                        ],
                        '--media'               => [
                            'description'   => 'Clear thumbnails of images',
                            'default_value' => '',
                            'required'      => false,
                            'alias'         => 'media',
                        ],
                        '--products'            => [
                            'description'   => 'Clear products data cache',
                            'default_value' => '',
                            'required'      => false,
                            'alias'         => 'product',
                        ],
                        '--categories'          => [
                            'description'   => 'Clear categories data cache',
                            'default_value' => '',
                            'required'      => false,
                            'alias'         => 'category',
                        ],
                        '--manufacturers'       => [
                            'description'   => 'Clear manufacturers data cache',
                            'default_value' => '',
                            'required'      => false,
                            'alias'         => 'manufacturer',
                        ],
                        '--localizations'       => [
                            'description'   =>
                                'Clear localizations data cache (languages, definitions, currencies etc)',
                            'default_value' => '',
                            'required'      => false,
                            'alias'         => 'localization',
                        ],
                        '--logs'                => [
                            'description'   => 'Clear all log-files',
                            'default_value' => '',
                            'required'      => false,
                            'alias'         => 'logs',
                        ],
                        '--application_history' => [
                            'description'   => 'Clear install/upgrade history',
                            'default_value' => '',
                            'required'      => false,
                            'alias'         => 'install_upgrade_history',
                        ],
                    ],
                    'example'     => 'php abcexec cache:clear --products',
                ],
        ];
    }
}
