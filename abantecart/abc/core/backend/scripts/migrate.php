<?php

namespace abc\core\backend;

use abc\core\ABC;
use abc\core\engine\Registry;
use abc\core\lib\AException;
use abc\core\lib\AExtensionManager;
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
    public $results = [];
    public function __construct()
    {
        //$this->cache = new Cache();
    }

    public function validate(string $action, array $options)
    {
        $errors = [];
        if( !in_array($action, ['help'])
            && !is_file(ABC::env('DIR_VENDOR').'robmorgan'.DS.'phinx'.DS.'bin'.DS.'phinx')){
            return ['Error: File '.ABC::env('DIR_VENDOR').'robmorgan'.DS.'phinx'.DS.'bin'.DS.'phinx required to run migrations!'];
        }
        if(isset($options['init'])){
            return ['You don\'t need to initiate phinx. File of phinx configuration will be created inside directory '.ABC::env('DIR_CONFIG').' automatically.'];
        }
        if( $action == 'phinx' && !isset($options['stage'])  && !isset($options['help']) ){
            return ["Please provide stage name! For example: --stage='default'"];
        }
        return $errors;
    }

    /**
     * @param string $action
     * @param array  $options
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
    //nothing to do here. Use Phinx output here
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
                                            'required'      => true,
                                        ]
                    ],
                    'example'     => "php abcexec migrate:phinx help\n".
                        "\t  To create new migration:\n\n\t\t   php abcexec migrate::phinx create YourMigrationClassName\n\n"
                        ."\t  To run all new migrations:\n\n\t\t   php abcexec migrate::phinx migrate --stage=default\n\n"
                        ."\t  To rollback last migration:\n\n\t\t   php abcexec migrate::phinx rollback --stage=default\n\n"
                        ."\t  To rollback all migrations (reset):\n\n\t\t   php abcexec migrate::phinx rollback --target=0 --stage=default\n\n"


                ]
        ];
    }

    protected function _call_phinx( $action, $options ){
        $stage_name = $options['stage'] ? $options['stage'] : 'default';
        $result = $this->createMigrationConfig(['stage' => $stage_name]);
        if(!$result){
            throw new AException(AC_ERR_LOAD, implode("\n",$this->results)."\n");
        }
        $this->_adapt_argv($action);

        //phinx status -e development
        $app =  require ABC::env('DIR_VENDOR').'robmorgan'.DS.'phinx'.DS.'app'.DS.'phinx.php';
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
            $template = ABC::env('DIR_CORE').'backend'.DS.'scripts'.DS.'Migration.template.txt';
            if(!is_file($template) || !is_readable($template)){
                $this->results[] = 'Cannot to find migration template file '.$template.'!';
                return false;
            }
            $argv[] = '--template='.$template;
        }


        foreach($argv as $k=>$v){
            if(is_int(strpos($v,'--stage='))){
                unset($argv[$k]);
                break;
            }
        }

        array_unshift($argv, 'phinx');


        //add configuration file
        $_SERVER['argv'] = $argv;

        return true;
    }

    public function createMigrationConfig(array $data ){
        $migration_config_file = ABC::env('DIR_CONFIG').'migration.config.php';
        $stage_name = $data['stage'];
        $app_config_file = ABC::env('DIR_CONFIG').$stage_name.'.config.php';
        @unlink($migration_config_file);
        if(!$stage_name || !is_file($app_config_file)){
            $this->results[] = 'Cannot to create migration configuration. Unknown stage name!';
            return false;
        }
        $app_config = @include $app_config_file;
        if( !$app_config ){
            $this->results[] = 'Cannot to create migration configuration. Empty stage environment!';
            return false;
        }

        $dirs = [ABC::env('DIR_MIGRATIONS')];
        if($data['extension_text_id']){
            $dirs[] = ABC::env('DIR_APP_EXTENSIONS').$data['extension_text_id'].DS.'migrations';
        }
        //otherwise include all paths (core + extensions migrations)

        $dirs = array_merge($dirs, glob( ABC::env( 'DIR_APP_EXTENSIONS' ).'*/migrations', GLOB_ONLYDIR ));
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