<?php

namespace abc\core\backend;

use abc\ABC;
use abc\controllers\admin\ControllerPagesToolCache;
use abc\core\engine\Registry;
use abc\lib\AAssetPublisher;
use abc\lib\AConfig;
use abc\lib\AConnect;
use abc\lib\AContentManager;
use abc\lib\ACurrency;
use abc\lib\AException;
use abc\lib\ALanguageManager;
use abc\models\admin\ModelSettingStore;
use abc\models\storefront\ModelCatalogCategory;

class Cache implements ABCExec
{
    public $errors = [];
    protected $results = [];
    protected $languages = [];
    protected $currencies = [];
    protected $connect;

    public function validate(string $action, array $options)
    {
        $action = ! $action ? 'create' : $action;
        //if now options - check action
        if ( ! $options) {
            if ( ! in_array($action, array('help', 'create', 'clear'))) {
                return ['Error: Unknown Action Parameter!'];
            }
        }

        return [];
    }

    public function run(string $action, array $options)
    {
        $output = null;
        $result = false;
        if ( ! in_array($action, array('create', 'clear')) || ! $options) {
            return ['Error: Unknown action.'];
        }
        //looking for "ALL" parameter in option set. If presents - skip other.
        $opt_list = $this->_get_option_list();
        if ($action == 'clear') {
            $k = array_search('all', array_keys($options));
            if ($k !== false) {
                $options = [
                    'all'                     => 1,
                    'media'                   => 1,
                    'install_upgrade_history' => 1,
                    'logs'                    => 1,
                    'html_cache'              => 1,
                ];
            }
        }

        foreach (array_keys($options) as $cache_section) {
            $alias = $opt_list[$action]['arguments']['--'.$cache_section]['alias'];
            if ( ! $alias) {
                continue;
            }
            $cache_groups = explode(',', $alias);
            $cache_groups = array_map('trim', $cache_groups);

            if ($action == 'clear') {
                $result = $this->_process_clear($cache_groups);
            } elseif ($action == 'create') {
                $result = $this->_process_create($cache_section);
            }
        }

        return $result ? true : $this->errors;
    }

    protected function _process_create($action = '')
    {
        //clear all cache if need to rebuild
        if ($action == 'rebuild') {
            $opt_list = $this->_get_option_list();
            $this->_process_clear([
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
            if ( ! $store_url) {
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
            $contents = $cm->getContents(array(), 'default', $store['store_id']);
            foreach ($contents as $content) {
                $seo_url = $registry->get('html')->getSEOURL('content/content', '&content_id='.$content['content_id']);
                //loop for all variants
                $this->_touch_url($seo_url);
                $this->results['contents']++;
            }

            //loop by all categories of store
            $registry->get('load')->model('catalog/category');
            $categories = $registry->get('model_catalog_category')->getCategoriesData(null, $store['store_id']);
            foreach ($categories as $category) {
                $seo_url = $registry->get('html')->getSEOURL('product/category', '&category_id='.$category['category_id']);
                //loop for all variants
                $this->_touch_url($seo_url);
                $this->results['categories']++;
            }

            //loop by all products of store
            $registry->get('load')->model('catalog/product');
            $total_products = $registry->get('model_catalog_product')->getTotalProducts(array('store_id' => $store['store_id']));
            $i = 0;
            while ($i <= $total_products) {
                $products = $registry->get('model_catalog_product')->getProducts(
                    array(
                        'store_id' => $store['store_id'],
                        'start'    => $i,
                        'limit'    => 20,
                    ));
                foreach ($products as $product) {
                    $seo_url = $registry->get('html')->getSEOURL('product/product', '&product_id='.$product['product_id']);
                    //loop for all variants
                    $this->_touch_url($seo_url);
                    $this->results['products']++;
                }

                $i += 20;
            }
        }

        return true;
    }

    protected function _touch_url($url)
    {
        foreach ($this->languages as $lang) {
            foreach ($this->currencies as $curr) {
                $this->connect->setCurlOptions(
                    array(
                        CURLOPT_COOKIE => 'language='.$lang['code'].'; currency='.$curr['code'],
                    ), false);
                $this->connect->getDataHeaders($url);
                sleep(1);
            }
        }
    }

    protected function _process_clear($cache_groups)
    {
        $this->errors = [];
        $registry = Registry::getInstance();
        $app_cache = $registry->get('cache');
        $lang_obj = new ALanguageManager($registry);
        $languages = $lang_obj->getActiveLanguages();
        $registry->get('load')->model('setting/store');
        $stores = $registry->get('model_setting_store')->getStores();

        foreach ($cache_groups as $group) {
            if ($group == 'media') {
                try {
                    require_once(ABC::env('DIR_APP').'controllers'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.'tool'.DIRECTORY_SEPARATOR.'cache.php');
                    $cc = new ControllerPagesToolCache($registry, 0, 'tool/cache');
                    $cc->deleteThumbnails();
                } catch (\Exception $e) {
                    $this->errors[] = 'Cannot to delete thumbnails. '.$e->getMessage();

                }
            } elseif ($group == 'install_upgrade_history') {
                try {
                    $registry->get('load')->model('tool/install_upgrade_history');
                    $registry->get('model_tool_install_upgrade_history')->deleteData();
                } catch (\Exception $e) {
                    $this->errors[] = 'Cannot to clear application history. '.$e->getMessage();
                }
            } elseif ($group == 'logs') {
                $file = ABC::env('DIR_LOGS').$registry->get('config')->get('config_error_filename');
                if (is_file($file)) {
                    unlink($file);
                }
            } elseif ($group == 'html_cache') {
                $app_cache->remove('html_cache');
            } else {
                $app_cache->remove($group);
                foreach ($languages as $lang) {
                    foreach ($stores as $store) {
                        $app_cache->remove($group."_".$store['store_id']."_".$lang['language_id']);
                    }
                }
            }
        }

        return $this->errors ? false : true;
    }

    public function finish(string $action, array $options)
    {
        $output = "Success: Cache have been successfully processed.\n";
        $output .= "\tContent pages count: ".$this->results['contents']."\n";
        $output .= "\tCategory pages count: ".$this->results['categories']."\n";
        $output .= "\tProduct pages count: ".$this->results['products'];

        return $output;
    }

    public function help()
    {
        return $this->_get_option_list();
    }

    protected function _get_option_list()
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
                            'description'   => 'Clear localizations data cache (languages, definitions, currencies etc)',
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

    //TODO: need to complete
    protected function _create_cache($section_name)
    {

    }

}