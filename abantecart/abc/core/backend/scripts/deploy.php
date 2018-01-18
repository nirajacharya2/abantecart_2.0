<?php

namespace abc\core\backend;

use abc\lib\AException;

class Deploy implements ABCExec
{
    /**
     * @var Cache
     */
    protected $cache;
    /**
     * @var Publish
     */
    protected $publish;
    protected $results = [];
    public function __construct()
    {
        require_once('cache.php');
        require_once('publish.php');
        $this->cache = new Cache();
        $this->publish = new Publish();
    }

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
        $errors = [];
        if(in_array($action, array('all', 'core', 'extensions', 'vendors') )) {
            $result = $this->cache->run('clear', ['all'=>1]);
            if(is_array($result) && $result){
                $errors += $result;
            }else {
                $this->publish->run('publish', [$action=>1]);
                $this->results[] = $this->publish->finish('publish', [$action=>1]);
                $this->cache->run('create', ['build'=>1]);
                $this->results[] = $this->cache->finish('create', ['build'=>1]);
            }
        }else{
            $errors = [ 'Error: unknown deploy action!' ];
        }

        return $result ? true : $errors;
    }

    public function finish(string $action, array $options)
    {
        $output = "Success: Deployment have been successfully processed.\n";
        $output .= implode("\n", $this->results);
        return $output;
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
                    'description' => 'deploy all files',
                    'arguments'   => [],
                    'example'     => 'php abcexec deploy:all'
                ],
            'core' =>
                [
                    'description' => 'deploy only default template asset files',
                    'arguments'   => [],
                    'example'     => 'php abcexec deploy:core'
                ],
            'extensions' =>
                [
                    'description' => 'publish only extensions asset files',
                    'arguments'   => [
                            '--extension'   => [
                                                'description'   => 'Deploy only assets of extension with given text ID',
                                                'default_value' => 'your_extension_txt_id',
                                                'required'      => false,
                                            ],
                    ],
                    'example'     => 'php abcexec deploy:extensions --extension=your_extension_txt_id'
                ],
            'vendors' =>
                [
                    'description' => 'deploy only vendors asset files',
                    'arguments'   => [],
                    'example'     => 'php abcexec deploy:vendors'
                ],
        ];
    }

}