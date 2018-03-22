<?php

namespace abc\core\backend;

use abc\core\ABC;
use abc\core\engine\Registry;
use abc\core\lib\AException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\Migrator;

class Migrate implements ABCExec
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
        //$this->cache = new Cache();
    }

    public function validate(string $action, array $options)
    {
        $errors = [];
        if( !in_array($action, ['help'])
            && !is_file(ABC::env('DIR_VENDOR').'robmorgan'.DIRECTORY_SEPARATOR.'phinx'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'phinx')){
            return ['Error: File '.ABC::env('DIR_VENDOR').'robmorgan'.DIRECTORY_SEPARATOR.'phinx'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'phinx required to run migrations!'];
        }
        if(isset($options['init'])){
            return ['You don\'t need to initiate phinx. File of phinx configuration will be created inside directory '.ABC::env('DIR_CONFIG').' automatically!'];
        }
        return $errors;
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

        $action = !$action ? 'help' : $action;
        if($action == 'phinx') {
            $this->_call_phinx($action, $options);
        }else{
            $this->help();
        }
    }


    public function finish(string $action, array $options)
    {

    }

    /**
     * @param array $options
     *
     * @return bool
     */
    public function help( $options = [] )
    {
        //show basic suggestion for usage via abcexec
        if(!$options){
           return $this->_get_option_list();
        }
        //show phinx help
        return $this->_call_phinx( 'help', $options );
    }

    protected function _get_option_list()
    {
        return [
            'phinx' =>
                [
                    'description' => 'Run phinx command. See more details http://docs.phinx.org/en/latest/commands.html'.
                        "\n Note: Every time file abc/config/migration.config.php will be recreated.",
                    'arguments'   => [
                            '--stage' => [
                                            'description'   => 'stage name',
                                            'default_value' => 'default',
                                            'required'      => false,
                                        ]
                    ],
                    'example'     => "php abcexec migrate:phinx help\n".
                        "\t  To create new migration:\n\t\t   php abcexec migrate::phinx create YourMigrationClassName\n"
                        ."\t  To run all new migrations:\n\t\t   php abcexec migrate::phinx migrate\n"
                        ."\t  To rollback last migration:\n\t\t   php abcexec migrate::phinx rollback\n"
                        ."\t  To rollback all migrations (reset):\n\t\t   php abcexec migrate::phinx rollback -t 0\n"


                ]
        ];
    }

    protected function _call_phinx( $action, $options ){
        $stage_name = $options['stage_name'] ? $options['stage_name'] : 'default';
        $result = $this->_make_migration_config($stage_name);
        if(!$result){
            throw new AException(AC_ERR_LOAD, implode("\n",$this->results)."\n");
        }
        $this->_adapt_argv($action);

        //phinx status -e development
        $app =  require ABC::env('DIR_VENDOR').'robmorgan'.DIRECTORY_SEPARATOR.'phinx'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'phinx.php';
        $app->run();
    }

    protected function _adapt_argv( $action ){
        //do the trick for help output
        $_SERVER['PHP_SELF'] = 'abcexec migrate:phinx';

        $argv = $_SERVER['argv'];
        //remove abcexec
        array_shift($argv);
        array_shift($argv);

        switch($action){
            case 'help':
            case 'init':
                //$argv[] = '-h';
            break;
            default:
                if($action != 'phinx') {
                    $argv[] = $action;
                }
            $argv[] = '--configuration='.ABC::env('DIR_CONFIG').'migration.config.php';
        }

        //add migration template parameter for new migrations
        if(in_array('create',$argv)){
            $template = ABC::env('DIR_CORE').'backend'.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'Migration.template.txt';
            if(!is_file($template) || !is_readable($template)){
                $this->results[] = 'Cannot to find migration template file '.$template.'!';
                return false;
            }
            $argv[] = '--template='.$template;
        }


        foreach($argv as $k=>$v){
            if(is_int(strpos($v,'--stage_name='))){
                unset($argv[$k]);
                break;
            }
        }

        array_unshift($argv, 'phinx');


        //add configuration file
        $_SERVER['argv'] = $argv;

        return true;
    }

    protected function _make_migration_config($stage_name = 'default'){
        $migration_config_file = ABC::env('DIR_CONFIG').'migration.config.php';
        $app_config_file = ABC::env('DIR_CONFIG').$stage_name.'.config.php';
        @unlink($migration_config_file);
        if(!$stage_name || !is_file($app_config_file)){
            $this->results[] = 'Cannot to create migration configuration. Unknown stage name!';
            return false;
        }
        $app_config = require $app_config_file;

        $dirs = [ABC::env('DIR_MIGRATIONS')];
        $dirs = array_merge($dirs, glob(ABC::env('DIR_APP_EXTENSIONS').'*/migrations',GLOB_ONLYDIR));
        $db_drv = $app_config['DB_CURRENT_DRIVER'];
        $content = <<<EOD
<?php
    return [
        'paths' => [
            'migrations' => [

EOD;
        foreach($dirs as $dir){
            $content .= "                              '".$dir."',\n";
        }
$content .= <<<EOD
                            ]
        ],
        'environments' => [
            'default_migration_table' => 'abc_migration_log',
            'default_database' => '{$stage_name}',
            '{$stage_name}' => [
                'adapter' => '{$db_drv}',
                'host'    => '{$app_config['DATABASES'][ $db_drv ]['DB_HOST']}',
                'name'    => '{$app_config['DATABASES'][ $db_drv ]['DB_NAME']}',
                'user'    => '{$app_config['DATABASES'][ $db_drv ]['DB_USER']}',
                'pass'    => '{$app_config['DATABASES'][ $db_drv ]['DB_PASSWORD']}',
                'port'    => '{$app_config['DATABASES'][ $db_drv ]['DB_PORT']}',
                'table_prefix' => '{$app_config['DATABASES'][ $db_drv ]['DB_PREFIX']}',
                'charset' => '{$app_config['DATABASES'][ $db_drv ]['DB_CHARSET']}',
                'collation' => '{$app_config['DATABASES'][ $db_drv ]['DB_COLLATION']}',
            ]
        ]
    ];
EOD;
        $file = fopen($migration_config_file, 'w');
        if ( ! fwrite($file, $content)) {
            $this->results[] = 'Cannot to write file '.$file;
            return false;
        }
        fclose($file);
        return true;
    }


}