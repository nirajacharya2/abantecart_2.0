<?php

namespace abc\core\backend;

use abc\core\ABC;
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
        if( !is_file(ABC::env('DIR_VENDOR').'robmorgan/phinx/bin/phinx')){
            return ['Error: File '.ABC::env('DIR_VENDOR').'robmorgan/phinx/bin/phinx required to run migrations!'];
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
        $action = !$action ? 'all' : $action;
        $this->_call_phinx($action, $options);
    }


    public function finish(string $action, array $options)
    {
        $output = "Success: Database migration command have been successfully processed.\n";
        $output .= implode("\n", $this->results);
        return $output;
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    public function help( $options = [] )
    {
        $this->_call_phinx( 'help', $options );
    }

    protected function _get_option_list()
    {
        return [
            'list' =>
                [
                    'description' => 'Output list of migration files from directory '.ABC::env('DIR_MIGRATIONS'),
                    'arguments'   => [],
                    'example'     => 'php abcexec migrate:list'
                ],
            'up' =>
                [
                    'description' => 'Forward one migration operation (step)',
                    'arguments'   => [],
                    'example'     => 'php abcexec migrate:up'
                ],
            'down' =>
                [
                    'description' => 'Rollback one migration operation (step)',
                    'arguments'   => [],
                    'example'     => 'php abcexec migrate:down'
                ],
            'rollback' =>
                [
                    'description' => 'Rollback all migrations',
                    'arguments'   => [
                        '--step' => [
                                        'description'   => 'Rollback a number of migrations',
                                        'default_value' => '',
                                        'required'      => false,
                                    ],
                        '--all' => [
                                        'description'   => 'Rollback all app migrations',
                                        'default_value' => '',
                                        'required'      => false,
                                    ],
                    ],
                    'example'     => 'php abcexec migrate:rollback'
                ],

            'create' =>
                [
                    'description' => 'make new migration scenario and save into directory '.ABC::env('DIR_MIGRATIONS'),
                    'arguments'   => [
                        '--name' => [
                                        'description'   => 'script name',
                                        'default_value' => '',
                                        'required'      => false,
                                    ]
                    ],
                    'example'     => 'php abcexec migrate:create'
                ],
        ];
    }

    protected function _call_phinx( $action, $options ){

        $result = $this->_make_migration_config($options['stage_name']);
        if( !$result ){
            return false;
        }

        $this->_adapt_argv($action);

        //phinx status -e development
        $app =  require ABC::env('DIR_VENDOR').'robmorgan/phinx/app/phinx.php';
        $app->run();
    }

    protected function _adapt_argv( $action ){
        $argv = $_SERVER['argv'];
        //remove abcexec
        array_shift($argv);
        //replace abcexec with phinx
        array_shift($argv);
        array_unshift($argv, 'phinx');

        switch($action){
            case 'help':
                $argv[] = '-h';
            break;
            default:
                $argv[] = $action;
        }
        if($action !='list') {
            $argv[] = '-c '.ABC::env('DIR_CONFIG').'migration.config.php';
        }

        foreach($argv as $k=>$v){
            if(is_int(strpos($v,'--stage_name='))){
                unset($argv[$k]);
                break;
            }
        }
        //add configuration file

        $_SERVER['argv'] = $argv;
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

        $dir = ABC::env('DIR_MIGRATIONS');
        $db_drv = $app_config['DB_CURRENT_DRIVER'];
        $content = <<<EOD
<?php
    return [
        'paths' => [
            'migrations' => ['{$dir}']
        ],
        'environments' => [
            'default_migration_table' => 'abc_migration_log',
            'default_database' => 'dev',
            'dev' => [
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