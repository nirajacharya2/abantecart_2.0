<?php

namespace abc\core\backend;

use abc\core\ABC;
use abc\core\lib\AException;

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
        require_once ABC::env('DIR_CORE').'backend/scripts/cache.php';
        require_once ABC::env('DIR_CORE').'backend/scripts/publish.php';
        $this->cache = new Cache();
        $this->publish = new Publish();
    }

    public function validate(string $action, array $options)
    {
        $action = !$action ? 'all' : $action;

        if(!in_array($action, array('all', 'help', 'core', 'config', 'extensions', 'vendors'))){
                return ['Error: Unknown Action Parameter!'];
        }

        if( $action=='config' ) {
            if ( ! $options) {
                return ['Error: Stage name required!'];
            }
            if ( ! is_writable(ABC::env('DIR_CONFIG'))) {
                return ['Error: Directory '.ABC::env('DIR_CONFIG').' is not writable!'];
            }
        }else{
            $errors = $this->publish->validate($action,$options);
            if($errors){
                return $errors;
            }
        }
        return [];
    }

    /**
     * @param string $action
     * @param array  $options
     * @return array|bool
     * @throws AException
     */
    public function run(string $action, array $options)
    {
        $output = null;
        $action = !$action ? 'all' : $action;
        $result = false;
        $errors = [];
        $clr_result = [];
        if(in_array($action, array('all', 'core', 'config', 'extensions', 'vendors') )) {
            if(!isset($options['skip-caching'])) {
                $clr_result = $this->cache->run('clear', ['all' => 1]);
            }
            if(is_array($clr_result) && $clr_result){
                $errors = $clr_result;
            }else {
                if($action == 'config'){
                    $result = $this->_switch_config($options['stage']);
                }else {
                    $this->publish->run($action, $options);
                    $this->results[] = $this->publish->finish($action, $options);

                    if(!isset($options['skip-caching'])) {
                        echo "Building all cache...\n";
                        $this->cache->run('create', ['build' => 1]);
                        $this->results[] = $this->cache->finish('create', ['build' => 1]);
                    }
                }
            }
        }else{
            $errors = [ 'Error: unknown deploy action!' ];
        }

        return $result && !$errors ? true : $errors;
    }

    /**
     * @param $stage_name
     * @return bool
     * @throws AException
     */
    protected function _switch_config($stage_name){
        if(!trim($stage_name)){
            throw new AException(AC_ERR_USER_ERROR, "Error: Wrong stage name!");
        }
        $stage_config = ABC::env('DIR_CONFIG').$stage_name.'.config.php';
        if( !is_file($stage_config)
            &&
            !is_file(ABC::env('DIR_CONFIG').$stage_name.'.php')
        ){
            throw new AException(AC_ERR_USER_ERROR, "Error: Cannot find config file of stage (looking for ".ABC::env('DIR_CONFIG').$stage_name.'.config.php'.")!");
        }

        $tmp_file = ABC::env('DIR_CONFIG').'tmp.php';
        @unlink($tmp_file);

        $file = fopen($tmp_file, 'w');
        $content = "
<?php
// config file with current stage values
return '".$stage_name.".config.php';
";
        if ( ! fwrite($file, $content)) {
            $result[] = 'Cannot to write temporary file '.$file;
        }
        fclose($file);


        //write enabled config. If it already presents - do backup first
        $enabled_config = ABC::env('DIR_CONFIG').'enabled.config.php';
        if(file_exists($enabled_config)){
            $result = rename($enabled_config,$enabled_config.'.bkp');
            if(!$result){
                throw new AException(AC_ERR_USER_ERROR, "Cannot rename prior config-file ".$enabled_config.". Please check permissions.");
            }
        }
        //let's switch
        $result = rename($tmp_file, $enabled_config);
        if(!$result){
            throw new AException(AC_ERR_USER_ERROR, "Cannot rename temporary file ".$tmp_file." to ".$enabled_config.".");
        }
        return true;
    }

    public function finish(string $action, array $options)
    {
        $output = "Success: Deployment have been successfully processed.\n";
        $output .= implode("\n", $this->results);
        return $output;
    }

    public function help( $options = [] )
    {
        return $this->_get_option_list();
    }

    protected function _get_option_list()
    {
        return [
            'all' =>
                [
                    'description' => 'deploy all files',
                    'arguments'   => [
                        '--skip-caching'        => [
                            'description'   => 'Skip cache re-creation during deployment',
                            'default_value' => null,
                            'required'      => false,
                        ],
                    ],
                    'example'     => 'php abcexec deploy:all'
                ],
            'core' =>
                [
                    'description' => 'deploy only default template asset files',
                    'arguments'   => [
                        '--skip-caching'        => [
                            'description'   => 'Skip cache re-creation during deployment',
                            'default_value' => null,
                            'required'      => false,
                        ],
                    ],
                    'example'     => 'php abcexec deploy:core'
                ],
            'config' =>
                [
                    'description' => 'build production config file',
                    'arguments'   => [
                                '--stage' =>
                                    [
                                        'description'   => 'deploy to stage with given name',
                                        'default_value' => '',
                                        'required'      => true,
                                        'alias'         => '*'
                                    ],
                                '--skip-caching'        => [
                                    'description'   => 'Skip cache re-creation during deployment',
                                    'default_value' => null,
                                    'required'      => false,
                                ],
                    ],
                    'example'     => 'php abcexec deploy:config --stage=default'
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
                            '--skip-caching'        => [
                                'description'   => 'Skip cache re-creation during deployment',
                                'default_value' => null,
                                'required'      => false,
                            ],
                    ],
                    'example'     => 'php abcexec deploy:extensions --extension=your_extension_txt_id'
                ],
            'vendors' =>
                [
                    'description' => 'deploy only vendors asset files',
                    'arguments'   => [
                        '--package'   => [
                                            'description'   => 'Publish only assets of vendor package with given package name',
                                            'default_value' => 'vendor_name:package_name',
                                            'required'      => false
                                         ],
                        '--skip-caching'        => [
                            'description'   => 'Skip cache re-creation during deployment',
                            'default_value' => null,
                            'required'      => false,
                        ],
                    ],
                    'example'     => 'php abcexec deploy:vendors'
                ],
        ];
    }

}