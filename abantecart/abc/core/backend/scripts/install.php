<?php
/*
  AbanteCart, Ideal Open Source Ecommerce Solution
  http://www.abantecart.com

  Copyright 2011-2018 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.abantecart.com for more information.
*/

namespace abc\core\backend;

use abc\core\ABC;
use abc\core\engine\Registry;
use abc\core\helper\AHelperUtils;
use abc\core\lib\AAssetPublisher;
use abc\core\cache\ACache;
use abc\core\lib\AConfig;
use abc\core\lib\AConnect;
use abc\core\lib\ADB;
use abc\core\lib\AException;
use abc\core\lib\ALanguageManager;
use abc\core\lib\ASession;

class Install implements ABCExec
{
    public function validate(string $action, array $options)
    {
        $action = ! $action ? 'install' : $action;
        //if now options - check action
        if ( ! $options) {
            if ( ! in_array($action, array('install', 'help'))) {
                return ['Error: Unknown Action Parameter!'];
            }
        }

        if ($action == 'help') {
            return [];
        }

        //Check if cart is already installed
        $file_config = [];
        if (file_exists(ABC::env('DIR_CONFIG').'app.php')) {
            $file_config = include ABC::env('DIR_CONFIG').'app.php';
        }

        if (isset($file_config['default']['ADMIN_SECRET'])) {
            return ["AbanteCart is already installed!\n Note: to reinstall application just delete file abc/config/app.php"];
        }

        //check requirements first
        $errors = $this->validateRequirements($options);
        if ($errors) {
            return $errors;
        }

        $this->_fill_defaults($options);
        //then check options
        if ( ! $options['admin_secret']) {
            $errors['admin_secret'] = 'Admin unique name is required!';
        } else {
            if (preg_match('/[^A-Za-z0-9_]/', $options['admin_secret'])) {
                $errors['admin_secret'] = 'Admin unique name contains non-alphanumeric characters!';
            }
        }

        if ( $options['db_driver'] == 'mysql' && !(extension_loaded('mysqli') || extension_loaded('pdo_mysql')) ) {
            $errors['db_driver'] = 'MySQLi or PDO_mysql extension needs to be loaded for AbanteCart to work!';
        }
        if ( ! $options['db_host']) {
            $errors['db_host'] = 'Host name required!';
        }

        if ( ! $options['db_user']) {
            $errors['db_user'] = 'User name required!';
        }

        if ( ! $options['db_name']) {
            $errors['db_name'] = 'Database Name required!';
        }

        if ( ! $options['username']) {
            $errors['username'] = 'Username required!';
        }

        if ( ! $options['password']) {
            $errors['password'] = 'Password required!';
        }

        $pattern = '/^([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])(([a-z0-9-])*([a-z0-9]))+(\.([a-z0-9])([-a-z0-9_-])?([a-z0-9])+)+$/i';

        if ( ! preg_match($pattern, $options['email'])) {
            $errors['email'] = 'Invalid E-Mail!';
        }

        if ( ! empty($options['db_prefix'])
            && preg_match('/[^A-Za-z0-9_]/', $options['db_prefix'])) {
            $errors['db_prefix'] = 'DB prefix contains non-alphanumeric characters!';
        }

        if ($options['db_driver']
            && $options['db_host']
            && $options['db_user']
            && $options['db_password']
            && $options['db_name']
        ) {
            try {
                new ADB(array(
                    'DB_DRIVER'    => $options['db_driver'],
                    'DB_HOST'      => $options['db_host'],
                    'DB_NAME'      => $options['db_name'],
                    'DB_USER'      => $options['db_user'],
                    'DB_PASSWORD'  => $options['db_password'],
                    'DB_CHARSET'   => 'utf8',
                    'DB_COLLATION' => 'utf8_unicode_ci',
                    'DB_PREFIX'    => $options['db_prefix'],
                ));
            } catch (AException $e) {
                $errors['error'] = $e->getMessage()."\n";
            }
        }

        if ( ! is_writable(ABC::env('DIR_CONFIG'))) {
            $errors['error'] .= 'Error: Could not write to abc/config folder. Please check you have set the correct permissions on: '.ABC::env('DIR_CONFIG')."!\n";
        }

        if(!is_file(__DIR__.'/deploy.php')){
            $errors['warning'] .= 'Dependency Error: deploy.php script not found in '.__DIR__."\n";
        }

        $url_parse_result = parse_url($options['http_server']);
        if( !$url_parse_result ){
            $errors['http_server'] = 'Wrong value of http_server parameter!';
        }

        //check self-connection via http
        if( !isset($errors['http_server']) && !isset($options['skip-caching'])){
            $connect = new AConnect();
            $connect->connect_method = extension_loaded('curl') ? 'curl' : 'socket';
            $data = $connect->getData($options['http_server'].'robots.txt');

            if ( !$data ){
                $errors['http_server'] = 'Cannot to connect to '.$options['http_server'].'!';
            }
        }

        return $errors;
    }

    public function run(string $action, array $options)
    {
        $output = null;
        $action = ! $action ? 'install' : $action;

        if ($action == 'install') {
            $this->_fill_defaults($options);

            //make config-files
            $errors = $this->_configure($options);
            //fill database
            if ( ! $errors) {
                $errors = $this->_run_sql($options);
            }
            if(!$errors){
                $registry = Registry::getInstance();
                $registry->set('cache', new ACache());
                $config = new AConfig($registry, (string)$options['http_server']);
                $registry->set('config', $config);
                $registry->set('language', new ALanguageManager($registry));
                require_once('deploy.php');
                $deploy = new Deploy();
                $ops = ['stage' => 'default'];
                if(isset($options['skip-caching'])){
                    $ops['skip-caching'] = 1;
                }
                $deploy->run('config', $ops);
            }

            if ( ! $errors && isset($options['with-sample-data'])) {
                $errors = $this->_load_demo_data($options);
            }
            // deploy assets and generate cache
            if ( ! $errors) {
                require_once 'deploy.php';
                $deploy = new Deploy();
                $ops = ['all' => 1];
                if(isset($options['skip-caching'])){
                    $ops['skip-caching'] = 1;
                }
                $result = $deploy->run('all', $ops);
                if(is_array($result)){
                    $errors = $result;
                }
            }
            $output = $errors;
        }

        return $output;
    }

    protected function _fill_defaults(array &$options){
        if(!$options){
            return false;
        }
        if ( ! isset($options['root_dir']) || ! $options['root_dir']) {
            $options['root_dir'] = ABC::env('DIR_ROOT');
        }
        if ( ! isset($options['app_dir']) || ! $options['app_dir']) {
            $options['app_dir'] = ABC::env('DIR_APP');
        }
        if ( ! isset($options['public_dir']) || ! $options['public_dir']) {
            $options['public_dir'] = ABC::env('DIR_PUBLIC');
        }
        if ( ! isset($options['cache_driver']) || ! $options['cache_driver']) {
            $options['cache_driver'] = 'file';
        }
        if ( ! isset($options['db_host']) || ! $options['db_host']) {
            $options['db_host'] = 'localhost';
        }
        if ( ! isset($options['db_prefix']) || ! $options['db_prefix']) {
            $options['db_prefix'] = 'ac_';
        }
        if ( ! isset($options['db_driver']) || ! $options['db_driver']) {
            $options['db_driver'] = 'mysql';
        }
        if( substr($options['http_server'],-1) != '/' ){
            $options['http_server'] .= '/';
        }

        return true;
    }

    public function finish(string $action, array $options)
    {
        $output = "\n\nSUCCESS! AbanteCart successfully installed on your server\n\n";
        $output .= "\t"."Store link: ".$options['http_server']."\n\n";
        $output .= "\t"."Admin link: ".$options['http_server']."?s=".$options['admin_secret']."\n\n";

        return $output;
    }

    protected function validateRequirements()
    {
        $errors = [];
        if (version_compare(phpversion(), ABC::env('MIN_PHP_VERSION'), '<') == true) {
            $errors['warning'] = 'Warning: You need to use PHP ' . ABC::env('MIN_PHP_VERSION') . ' or above for AbanteCart to work!';
        }

        if ( ! ini_get('file_uploads')) {
            $errors['warning'] = 'Warning: file_uploads needs to be enabled in PHP!';
        }

        if (ini_get('session.auto_start')) {
            $errors['warning'] = 'Warning: AbanteCart will not work with session.auto_start enabled!';
        }

        if ( ! function_exists('simplexml_load_file')) {
            $errors['warning'] = 'Warning: SimpleXML functions needs to be available in PHP!';
        }

        if ( ! extension_loaded('gd')) {
            $errors['warning'] = 'Warning: GD extension needs to be loaded for AbanteCart to work!';
        }

        if ( ! extension_loaded('mbstring')
            || ! function_exists('mb_internal_encoding')) {
            $errors['warning'] = 'Warning: MultiByte String extension needs to be loaded for AbanteCart to work!';
        }
        if ( ! extension_loaded('zlib')) {
            $errors['warning'] = 'Warning: ZLIB extension needs to be loaded for AbanteCart to work!';
        }
        if ( ! extension_loaded('phar')) {
            $errors['warning'] = 'Warning: PHAR extension needs to be loaded for AbanteCart to work!';
        }

        if ( ! is_writable(ABC::env('DIR_CONFIG'))) {
            $errors['warning'] = 'Warning: abc/config folder and files needs to be writable for AbanteCart to be installed!';
        }

        if ( ! is_writable(ABC::env('DIR_SYSTEM'))) {
            $errors['warning'] = 'Warning: System directory and all its children files/directories need to be writable for AbanteCart to work!';
        }

        if ( ! is_writable(ABC::env('DIR_CACHE'))) {
            $errors['warning'] = 'Warning: Cache directory needs to be writable for AbanteCart to work!';
        }

        if ( ! is_writable(ABC::env('DIR_LOGS'))) {
            $errors['warning'] = 'Warning: Logs directory needs to be writable for AbanteCart to work!';
        }

        if ( ! is_writable(ABC::env('DIR_PUBLIC').'images')) {
            $errors['warning'] = 'Warning: Image directory and all its children files/directories need to be writable for AbanteCart to work!';
        }

        if ( ! is_writable(ABC::env('DIR_PUBLIC').'images/thumbnails')) {
            if ( is_dir(ABC::env('DIR_PUBLIC').'images/thumbnails') ) {
                $errors['warning'] = 'Warning: images/thumbnails directory needs to be writable for AbanteCart to work!';
            } else {
                $result = mkdir(ABC::env('DIR_PUBLIC').'images/thumbnails', 0777, true);
                if ($result) {
                    chmod(ABC::env('DIR_PUBLIC').'images/thumbnails', 0777);
                    chmod(ABC::env('DIR_PUBLIC').'image', 0777);
                } else {
                    $errors['warning'] = 'Warning: images/thumbnails does not exists!';
                }
            }
        }

        if ( ! is_dir(ABC::env('DIR_APP').'downloads')) {
            @mkdir(ABC::env('DIR_APP').'downloads');
        }

        if ( ! is_writable(ABC::env('DIR_APP').'downloads')) {
            $errors['warning'] = 'Warning: Download directory needs to be writable for AbanteCart to work!';
        }

        if ( ! is_writable(ABC::env('DIR_APP_EXTENSIONS'))) {
            $errors['warning'] = 'Warning: Extensions directory needs to be writable for AbanteCart to work!';
        }

        if ( ! is_writable(ABC::env('DIR_PUBLIC').'resources')) {
            $errors['warning'] = 'Warning: Resources directory needs to be writable for AbanteCart to work!';
        }

        if ( ! is_writable(ABC::env('DIR_SYSTEM'))) {
            $errors['warning'] = 'Warning: Admin/system directory needs to be writable for AbanteCart to work!';
        }

        return $errors;
    }

    protected function _configure(array $options)
    {
        if ( ! $options) {
            return ['No options to configure!'];
        }
        if ( ! ABC::env('DIR_CONFIG')) {
            ABC::env('DIR_CONFIG', ABC::env('DIR_APP').'system/config/');
        }


        //server name needs to be set for emails
        $server_name = getenv("SERVER_NAME");
        if ( ! $server_name) {
            $value = rtrim($options['http_server'], '/.\\').'/';
            $server_name = parse_url($value, PHP_URL_HOST);
        }

        //generate unique app ID
        $unique_id = md5(time());

        $result = [];
        //write application config
        $content = <<<EOD
<?php
return [
        'APP_NAME' => 'AbanteCart',
        'MIN_PHP_VERSION' => '7.0',
        'DIR_ROOT' => '{$options['root_dir']}',
        'DIR_APP' => '{$options['app_dir']}',
        'DIR_PUBLIC' => '{$options['public_dir']}',
        'SERVER_NAME' => '{$server_name}',
        'ADMIN_SECRET' => '{$options['admin_secret']}',
        'UNIQUE_ID' => '{$unique_id}',
        // SEO URL Keyword separator
        'SEO_URL_SEPARATOR' => '-',
        // EMAIL REGEXP PATTERN
        'EMAIL_REGEX_PATTERN' => '/^[A-Z0-9._%-]+@[A-Z0-9.-]{0,61}[A-Z0-9]\.[A-Z]{2,16}$/i',
        //postfixes for template override
        'POSTFIX_OVERRIDE' => '.override',
        'POSTFIX_PRE' => '.pre',
        'POSTFIX_POST' => '.post',
        'APP_CHARSET' => 'UTF-8',

        'DB_CURRENT_DRIVER' => '{$options['db_driver']}',
        'DATABASES' =>[
            '{$options['db_driver']}' => [
                        'DB_DRIVER'    => '{$options['db_driver']}',
                        'DB_HOST'      => '{$options['db_host']}',
                        'DB_PORT'      => '{$options['db_port']}',
                        'DB_USER'      => '{$options['db_user']}',
                        'DB_PASSWORD'  => '{$options['db_password']}',
                        'DB_NAME'      => '{$options['db_name']}',
                        'DB_PREFIX'    => '{$options['db_prefix']}',
                        'DB_CHARSET'   => 'utf8',
                        'DB_COLLATION' => 'utf8_unicode_ci'
            ]
        ],

        'CACHE' => 
                    [
                        'CACHE_DRIVER' => '{$options['cache_driver']}',                        
                        //for "apc", "apcu", "xcache", "memcache" and "memcached" cache-drivers
                        //'CACHE_SECRET' => 'your_cache_secret',                        
                        //for "memcache" and "memcached" cache-drivers
                        //'CACHE_HOST' => 'your_cache_host',
                        //'CACHE_PORT' => 'your_cache_port',                        
                        //'CACHE_PERSISTENT' => false, //boolean
                        //'CACHE_COMPRESS_LEVEL' => false, //boolean
                    ]
];
EOD;
        $file = fopen(ABC::env('DIR_CONFIG').'default.config.php', 'w');
        if ( ! fwrite($file, $content)) {
            $result[] = 'Cannot to write file '.$file;
        }
        fclose($file);

        $file = fopen(ABC::env('DIR_CONFIG').'enabled.config.php', 'w');
        $content = <<<EOD
<?php
// config file with current stage values
return 'default.config.php';
EOD;
        if ( ! fwrite($file, $content)) {
            $result[] = 'Cannot to write file '.$file;
        }
        fclose($file);

        //adds into environment
        $registry = Registry::getInstance();
        $db_config = [
                        $options['db_driver'] =>
                        [
                            'DB_DRIVER'   => $options['db_driver'],
                            'DB_HOST'     => $options['db_host'],
                            'DB_PORT'     => $options['db_port'],
                            'DB_USER'     => $options['db_user'],
                            'DB_PASSWORD' => $options['db_password'],
                            'DB_NAME'     => $options['db_name'],
                            'DB_PREFIX'   => $options['db_prefix'],
                            'DB_CHARSET'  => 'utf8',
                            'DB_COLLATION'=> 'utf8_unicode_ci'
                        ]
                    ];
        ABC::env('DB_CURRENT_DRIVER', $options['db_driver']);
        ABC::env('DATABASES',$db_config);
        $registry->set('db', new ADB( $db_config[$options['db_driver']] ));
        ABC::env('CACHE', ['CACHE_DRIVER' => $options['cache_driver'] ]);
        return $result;
    }

    protected function _run_sql($data)
    {
        $errors = [];
        $file = ABC::env('DIR_ROOT').'install'.DIRECTORY_SEPARATOR.'abantecart_database.sql';
        if ( ! is_file($file)) {
            $errors[] = 'Error: file '.$file.' not found!';

            return $errors;
        }
        $sql = file($file);
        if ($sql === false) {
            $errors[] = 'Error: cannot open file '.$file;
            return $errors;
        }
        $db_config = ABC::env('DATABASES');
        $db = new ADB($db_config[ABC::env('DB_CURRENT_DRIVER')]);
        $query = '';
        foreach ($sql as $line) {
            $tsl = trim($line);
            if (($sql != '') && (substr($tsl, 0, 2) != "--")
                && (substr($tsl, 0, 1) != '#')) {
                $query .= $line;
                if (preg_match('/;\s*$/', $line)) {
                    $query = str_replace(" `ac_", " `".$data['db_prefix'],$query);
                    $db->query($query); //no silence mode! if error - will throw to exception
                    $query = '';
                }
            }
        }

        $db->query("SET CHARACTER SET utf8;");
        $db->query("SET @@session.sql_mode = 'MYSQL40';");
        $salt_key = AHelperUtils::genToken(8);
        $db->query(
           "INSERT INTO `".$data['db_prefix']."users`
            SET user_id = '1',
                user_group_id = '1',
                email = '".$db->escape($data['email'])."',
                username = '".$db->escape($data['username'])."',
                salt = '".$db->escape($salt_key)."', 
                PASSWORD = '".$db->escape(sha1($salt_key.sha1($salt_key.sha1($data['password']))))."',
                STATUS = '1',
                date_added = NOW();");

        $db->query(
            "UPDATE `".$data['db_prefix']."settings` 
                    SET value = '".$db->escape($data['email'])."' 
                    WHERE `key` = 'store_main_email'; ");
        $db->query(
            "UPDATE `".$data['db_prefix']."settings` 
                    SET value = '".$db->escape($data['http_server'])."' 
                    WHERE `key` = 'config_url'; ");
        if (ABC::env('HTTPS')) {
            $db->query(
                "UPDATE `".$data['db_prefix']."settings` 
                        SET value = '".$db->escape($data['http_server'])."' 
                        WHERE `key` = 'config_ssl_url'; ");
            $db->query(
                "UPDATE `".$data['db_prefix']."settings` 
                        SET value = '2' 
                        WHERE `key` = 'config_ssl'; ");
        }
        $db->query(
                "UPDATE `".$data['db_prefix']."settings` 
                SET value = '".$db->escape(AHelperUtils::genToken(16))."' 
                WHERE `key` = 'task_api_key'; ");
        $db->query(
                   "INSERT INTO `".$data['db_prefix']."settings` 
                    SET 
                        `group` = 'config', 
                        `key` = 'install_date', 
                        `value` = NOW(); ");
        $db->query("UPDATE `".$data['db_prefix']."products` SET `viewed` = '0';");

        //run destructor and close db-connection
        unset($db);
        //clear cache dir in case of reinstall
        $cache = new ACache();
        $cache->setCacheStorageDriver('file');
        $cache->enableCache();
        $cache->remove('*');

        return $errors;
    }

    protected function _load_demo_data($options)
    {
        $errors = [];
        $file = ABC::env('DIR_ROOT').'install'.DIRECTORY_SEPARATOR.'demo_data'.DIRECTORY_SEPARATOR.'abantecart_demo_data.sql';
        if ( ! is_file($file)) {
            $errors[] = 'Error: file '.$file.' not found!';
            return $errors;
        }
        $sql = file($file);
        if ($sql === false) {
            $errors[] = 'Error: cannot open file '.$file;
            return $errors;
        }
        $db_config = ABC::env('DATABASES');
        $db = new ADB($db_config[ABC::env('DB_CURRENT_DRIVER')]);
        $db->query("SET NAMES 'utf8'");
        $db->query("SET CHARACTER SET utf8");

        $query = '';
        foreach ($sql as $line) {
            $tsl = trim($line);
            if (($sql != '') && (substr($tsl, 0, 2) != "--") && (substr($tsl, 0, 1) != '#')) {
                $query .= $line;
                if (preg_match('/;\s*$/', $line)) {
                    $query = str_replace("DROP TABLE IF EXISTS `ac_","DROP TABLE IF EXISTS `".$options['db_prefix'], $query);
                    $query = str_replace("CREATE TABLE `ac_", "CREATE TABLE `".$options['db_prefix'], $query);
                    $query = str_replace("INSERT INTO `ac_", "INSERT INTO `".$options['db_prefix'], $query);
                    $result = $db->query($query);
                    if ( ! $result || $db->error) {
                        $errors[] = $db->error."\n\t\t".$query;
                        break;
                    }
                    $query = '';
                }
            }
        }
        $db->query("SET CHARACTER SET utf8");
        $db->query("SET @@session.sql_mode = 'MYSQL40'");

        //clear earlier created cache by AConfig and ALanguage classes in previous step
        $cache = new ACache();
        $cache->setCacheStorageDriver('file');
        $cache->enableCache();
        $cache->remove('*');

        return $errors;
    }

    public function help()
    {
        $options = $this->_get_option_list();
        foreach ($options as $action => $help_info) {
            $output = "php abcexec install:".$action." ";
            $maximal = $minimal = '';
            if ($help_info['arguments']) {
                foreach ($help_info['arguments'] as $arg => $desc) {
                    if ($arg == '--demo-mode') {
                        continue;
                    }
                    $maximal .= $arg.($desc['default_value'] ? "=" .$desc['default_value'] : '')."  ";
                    if($desc['required']){
                        $minimal .= $arg.($desc['default_value'] ? "=" .$desc['default_value'] : '')."  ";
                    }
                }
            }
            $options[$action]['example'] = "\n\t\tWith minimal parameters\n\n\t\t\t". $output.$minimal."\n\n";
            $options[$action]['example'] .= "\t\tWith all parameters\n\n\t\t\t". $output.$maximal."\n\n";
        }

        return $options;
    }

    protected function _get_option_list()
    {
        return [
            'install' =>
                [
                    'description' => 'run installation process',
                    'arguments'   => [
                        /*'--root_dir'         => [
                                                'description'   => 'Custom full path to root directory',
                                                'default_value' => ABC::env('DIR_ROOT')
                                                ],
                        '--app_dir'         => [
                                                'description'   => 'Custom full path to "abc" directory',
                                                'default_value' => ABC::env('DIR_APP')
                                                ],
                        '--public_dir'         => [
                                                'description'   => 'Custom full path to "public" directory',
                                                'default_value' => ABC::env('DIR_PUBLIC')
                                                ],
                        */
                        '--db_host'          => [
                            'description'   => 'Database hostname',
                            'default_value' => 'localhost',
                            'required'      => false,
                        ],
                        '--db_user'          => [
                            'description'   => 'Database username',
                            'default_value' => 'root',
                            'required'      => true,
                        ],
                        '--db_password'      => [
                            'description'   => 'Database user password',
                            'default_value' => '******',
                            'required'      => true,
                        ],
                        '--db_name'          => [
                            'description'   => 'Database username',
                            'default_value' => 'abantecart',
                            'required'      => true,
                        ],
                        '--db_driver'        => [
                            'description'   => 'Database driver',
                            'default_value' => 'mysql',
                            'required'      => false,
                        ],
                        '--db_prefix'        => [
                            'description'   => 'Database table name prefix',
                            'default_value' => 'ac_',
                            'required'      => true,
                        ],
                        '--cache-driver'     => [
                            'description'   => 'Cache driver',
                            'default_value' => 'file',
                            'required'      => false,
                        ],
                        '--admin_secret'       => [
                            'description'   => 'Secure value of url "s" parameter for admin side',
                            'default_value' => 'your_admin',
                            'required'      => true,
                        ],
                        '--username'         => [
                            'description'   => 'Top administrator login name',
                            'default_value' => 'admin',
                            'required'      => true,
                        ],
                        '--password'         => [
                            'description'   => 'Top administrator password',
                            'default_value' => 'admin',
                            'required'      => true,
                        ],
                        '--email'            => [
                            'description'   => 'Top administrator email',
                            'default_value' => 'your_email@example.com',
                            'required'      => true,
                        ],
                        '--http_server'      => [
                            'description'   => 'Top administrator email',
                            'default_value' => 'http://localhost',
                            'required'      => true,
                        ],
                        '--with-sample-data' => [
                            'description'   => 'Fill sample demo data during installation',
                            'default_value' => null,
                            'required'      => false,
                        ],
                        '--skip-caching'        => [
                            'description'   => 'Skip cache creation during installation',
                            'default_value' => null,
                            'required'      => false,
                        ],
                        '--demo-mode'        => [
                            'description'   => 'Enable demonstration mode',
                            'default_value' => null,
                            'required'      => false,
                        ],
                    ],
                    'example'     => '',
                ],
        ];
    }

}