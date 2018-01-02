<?php

namespace abc\core\backend;
use abc\ABC;
use abc\controllers\admin\ControllerPagesToolCache;
use abc\core\engine\Registry;
use abc\lib\AAssetPublisher;
use abc\lib\AException;
use abc\lib\ALanguageManager;

class Cache implements ABCExec
{
    public $errors = [];
    public function validate(string $action, array $options)
    {
        $action = !$action ? 'create' : $action;
        //if now options - check action
        if(!$options){
            if(!in_array($action, array('help', 'create', 'delete'))){
                return ['Error: Unknown Action Parameter!'];
            }
        }

        return [];
    }

    public function run(string $action, array $options)
    {
        $output = null;
        $result = false;
        if(!in_array($action, array('create', 'delete') ) || !$options) {
            return ['Error: Unknown action.'];
        }
        //looking for "ALL" parameter in option set. If presents - skip other.
        $k = array_search('all', array_keys($options));
        if($k !== false){
            $options = ['all' => 1];
        }

        $opt_list = $this->_get_option_list();

        foreach(array_keys($options) as $cache_section){
            $alias = $opt_list[$action]['arguments']['--'.$cache_section]['alias'];
            if(!$alias){continue;}
            $cache_groups = explode(',', $alias);
            $cache_groups = array_map('trim', $cache_groups);

            if($action == 'delete'){
                $result = $this->_process_delete($cache_groups);
            }elseif($action == 'create'){
                $result = $this->_process_create($cache_groups);
            }
        }
        return $result ? true : $this->errors;
    }

    protected function _process_create($cache_groups)
    {

    }
    protected function _process_delete($cache_groups)
    {
        $this->errors = [];
        $registry = Registry::getInstance();
        $app_cache = $registry->get('cache');
        $lang_obj = new ALanguageManager($registry);
        $languages = $lang_obj->getActiveLanguages();
        $registry->get('load')->model('setting/store');
        $stores = $registry->get('model_setting_store')->getStores();
        var_dump($cache_groups);
        foreach ($cache_groups as $group) {
            if ($group == 'media') {
                try {
                    require(ABC::env('DIR_APP').'controllers'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.'tool'.DIRECTORY_SEPARATOR.'cache.php');
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
                    $this->errors[] = 'Cannot to delete application history. '.$e->getMessage();
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
        return 'Success: Cache have been successfully processed.';
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

                                     '--all' => [
                                                'description'   => 'create all cache',
                                                'default_value' => '',
                                                'required'      => false,
                                                'alias'         => '*'
                                                ],
                                     '--html_cache' => [
                                                'description'   => 'create html-cache',
                                                'default_value' => '',
                                                'required'      => false,
                                                'alias'         => 'html_cache'
                                                ],
                                     '--layouts' => [
                                                'description'   => 'create cache of layouts, pages, blocks data',
                                                'default_value' => '',
                                                'required'      => false,
                                                'alias'         => 'layout, pages, blocks'
                                                ],
                                     '--forms' => [
                                                'description'   => 'create cache of dynamical html-forms data',
                                                'default_value' => '',
                                                'required'      => false,
                                                'alias'         => 'forms'
                                                ],
                                     '--media' => [
                                                'description'   => 'create thumbnails of images',
                                                'default_value' => '',
                                                'required'      => false,
                                                'alias'         => 'media'
                                                ],
                                     '--products' => [
                                                'description'   => 'create products data cache',
                                                'default_value' => '',
                                                'required'      => false,
                                                'alias'         => 'product'
                                                ],
                                     '--categories' => [
                                                'description'   => 'create categories data cache',
                                                'default_value' => '',
                                                'required'      => false,
                                                'alias'         => 'category'
                                                ],
                                     '--manufacturers' => [
                                                'description'   => 'create manufacturers data cache',
                                                'default_value' => '',
                                                'required'      => false,
                                                'alias'         => 'manufacturer'
                                                ],
                                     '--localizations' => [
                                                'description'   => 'create localizations data cache (languages, definitions, currencies etc)',
                                                'default_value' => '',
                                                'required'      => false,
                                                'alias'         => 'localization'
                                                ]
                    ],
                    'example'     => 'php abcexec cache:create --all'
                ],
            'delete' =>
                [
                    'description' => 'delete cache',
                    'arguments'   => [

                                     '--all' => [
                                                'description'   => 'Delete all cache data',
                                                'default_value' => '',
                                                'required'      => false,
                                                'alias'         => '*'
                                                ],
                                     '--html_cache' => [
                                                'description'   => 'Delete html-cache',
                                                'default_value' => '',
                                                'required'      => false,
                                                'alias'         => 'html_cache'
                                                ],
                                     '--layouts' => [
                                                'description'   => 'Delete cache of layouts, pages, blocks data',
                                                'default_value' => '',
                                                'required'      => false,
                                                'alias'         => 'layout, pages, blocks'
                                                ],
                                     '--forms' => [
                                                'description'   => 'Delete cache of dynamical html-forms data',
                                                'default_value' => '',
                                                'required'      => false,
                                                'alias'         => 'forms'
                                                ],
                                     '--media' => [
                                                'description'   => 'Delete thumbnails of images',
                                                'default_value' => '',
                                                'required'      => false,
                                                'alias'         => 'media'
                                                ],
                                     '--products' => [
                                                'description'   => 'Delete products data cache',
                                                'default_value' => '',
                                                'required'      => false,
                                                'alias'         => 'product'
                                                ],
                                     '--categories' => [
                                                'description'   => 'Delete categories data cache',
                                                'default_value' => '',
                                                'required'      => false,
                                                'alias'         => 'category'
                                                ],
                                     '--manufacturers' => [
                                                'description'   => 'Delete manufacturers data cache',
                                                'default_value' => '',
                                                'required'      => false,
                                                'alias'         => 'manufacturer'
                                                ],
                                     '--localizations' => [
                                                'description'   => 'Delete localizations data cache (languages, definitions, currencies etc)',
                                                'default_value' => '',
                                                'required'      => false,
                                                'alias'         => 'localization'
                                     ],
                                     '--logs' => [
                                                'description'   => 'Clear all log-files',
                                                'default_value' => '',
                                                'required'      => false,
                                                'alias'         => 'logs'
                                     ],
                                     '--application_history' => [
                                                'description'   => 'Clear install/upgrade history',
                                                'default_value' => '',
                                                'required'      => false,
                                                'alias'         => 'install_upgrade_history'
                                     ]
                    ],
                    'example'     => 'php abcexec cache:delete --products'
                ]
        ];
    }

    //TODO: need to complete
    protected function _create_cache($section_name){

    }

}