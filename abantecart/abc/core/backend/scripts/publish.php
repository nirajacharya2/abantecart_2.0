<?php

namespace abc\core\backend;
use abc\core\ABC;
use abc\core\lib\AAssetPublisher;


class Publish implements ABCExec
{
    public function validate(string $action, array $options)
    {
        $action = !$action ? 'all' : $action;
        //if now options - check action
        if(!$options){
            if(!in_array($action, array('all', 'help', 'core', 'extensions', 'vendors'))){
                return ['Error: Unknown Action Parameter!'];
            }
        }elseif( $options && $action == 'extensions'){
            if(isset($options['extension'])){
                $extension_name = trim($options['extension']);
                $dir_name = ABC::env('DIR_APP_EXTENSIONS').$extension_name;
                if(!is_dir($dir_name)){
                    return ['Error: directory of extension "'.$extension_name.'" not found!'];
                }
            }
        }elseif( $options && $action == 'vendors'){
            if(isset($options['package'])){
                list($vendor_name, $package_name) = explode(':',$options['package']);
                $vendor_name = trim($vendor_name);
                $package_name = trim($package_name);
                $dir_name = ABC::env('DIR_VENDOR').'assets'.DS.$vendor_name.DS.$package_name;
                if(!$vendor_name || !$package_name){
                    return ['Error: Incorrect vendor package name "'.$options['package'].'"!'];
                }elseif(!is_dir($dir_name)){
                    return ['Error: directory of vendor package "'.$dir_name.'" not found!'];
                }
            }
        }
        return [];
    }

    public function run(string $action, array $options)
    {
        $output = null;
        $action = !$action ? 'all' : $action;
        $result = false;
        if(in_array($action, array('all', 'core', 'extensions', 'vendors') )) {
            $ap = new AAssetPublisher();
            $result = $ap->publish($action, $options);
            $errors = $ap->errors;
        }else{
            $errors = ['Error: unknown public action!'];
        }

        return $result ? true : $errors;
    }

    public function finish(string $action, array $options)
    {
        return 'Success: Assets have been successfully published.';
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function help( $options = [] )
    {
        return $this->_get_option_list();
    }

    protected function _get_option_list()
    {
        return [
            'all' =>
                [
                    'description' => 'publish all asset files',
                    'arguments'   => [],
                    'example'     => 'php abcexec publish:all'
                ],
            'core' =>
                [
                    'description' => 'publish only default template asset files',
                    'arguments'   => [],
                    'example'     => 'php abcexec publish:core'
                ],
            'extensions' =>
                [
                    'description' => 'publish only extensions asset files',
                    'arguments'   => [
                        '--extension'   => [
                                            'description'   => 'Publish only assets of extension with given text ID',
                                            'default_value' => 'your_extension_txt_id',
                                            'required'      => false
                                        ],
                    ],
                    'example'     => 'php abcexec publish:extensions --extension=your_extension_txt_id'
                ],
            'vendors' =>
                [
                    'description' => 'publish only vendors asset files',
                    'arguments'   => [
                        '--package'   => [
                                                'description'   => 'Publish only assets of vendor package with given package name',
                                                'default_value' => 'vendor_name:package_name',
                                                'required'      => false
                                         ]
                    ],
                    'example'     => 'php abcexec publish:vendors --vendor=vendor_name:package_name'
                ],
        ];
    }

}