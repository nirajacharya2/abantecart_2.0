<?php

namespace abc\core\backend\scripts;
use abc\core\backend\ABCExec;
use abc\lib\AAssetPublisher;


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
            $result = $ap->publish($action);
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


    public function help()
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
                                        'required'      => false,
                                    ],
                    ],
                    'example'     => 'php abcexec publish:extensions --extension=your_extension_txt_id'
                ],
            'vendors' =>
                [
                    'description' => 'publish only vendors asset files',
                    'arguments'   => [],
                    'example'     => 'php abcexec publish:vendors'
                ],
        ];
    }

}