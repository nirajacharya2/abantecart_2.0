<?php

namespace abc\cli\scripts;

use abc\ABC;
use abc\cli\AbcDo;
use abc\core\helper\AHelperUtils;
use abc\lib\ACache;
use abc\lib\ADB;
use abc\lib\AException;

require dirname(__DIR__,2).'/init.php';

class Publish implements AbcDo
{

    public function help()
    {
        return $this->_help();
    }

    public function validate(string $action, array $options)
    {
        $action = !$action ? 'publish' : $action;
        $errors = [];
        //if now options - check action
        if(!$options){
            if(!in_array($action, array('publish', 'help'))){
                return ['Error: Unknown Action Parameter!'];
            }
        }

        if($action == 'help'){
            return [];
        }

        //Check if cart is already installed
        $file_config = [];
        if (file_exists(ABC::env('DIR_CONFIG').'app.php')) {
            $file_config = require '/var/www/github/abantecart_2.0/abantecart/abc/config/app.php';
        }

        if (isset($file_config['ADMIN_PATH'])) {
            return ['AbanteCart is already installed!'];
        }

        //check requirements first
        $errors = $this->validateRequirements();
        if($errors){
            return $errors;
        }
        //then check options
        if ( ! $options['admin_path']) {
            $errors['admin_path'] = 'Admin unique name is required!';
        } else {
            if (preg_match('/[^A-Za-z0-9_]/', $options['admin_path'])) {
                $errors['admin_path'] = 'Admin unique name contains non-alphanumeric characters!';
            }
        }

        return $errors;
    }

    public function run(string $action, array $options)
    {
        $output = null;
        $action = !$action ? 'install' : $action;

        if($action == 'install'){
            $errors = $this->_configure($options);

            if(!$errors){
                $errors = $this->_run_sql($options);
            }
            if(!$errors && isset( $options['with-sample-data'] )){
                $errors = $this->_load_demo_data($options);
            }
            $output = $errors;
        }elseif($action == 'help'){
            $output = $this->_help();
        }

        return $output;
    }

    public function finish(string $action, array $options)
    {
        $output = "\n\nSUCCESS! AbanteCart successfully installed on your server\n\n";
        $output .= "\t"."Store link: ".$options['http_server']."\n\n";
        $output .= "\t"."Admin link: ".$options['http_server']."?s=".$options['admin_path']."\n\n";
        return $output;
    }

    protected function validateRequirements(){
        $errors = [];
        if (version_compare(phpversion(), ABC::env('MIN_PHP_VERSION'), '<') == true) {
            $errors['warning'] = 'Warning: You need to use PHP ' . ABC::env('MIN_PHP_VERSION') . ' or above for AbanteCart to work!';
        }

        if (!ini_get('file_uploads')) {
            $errors['warning'] = 'Warning: file_uploads needs to be enabled in PHP!';
        }

        if (ini_get('session.auto_start')) {
            $errors['warning'] = 'Warning: AbanteCart will not work with session.auto_start enabled!';
        }

        if (!extension_loaded('mysqli') && !extension_loaded('pdo_mysql')) {
            $errors['warning'] = 'Warning: MySQLi extension needs to be loaded for AbanteCart to work!';
        }

        if (!function_exists('simplexml_load_file')) {
            $errors['warning'] = 'Warning: SimpleXML functions needs to be available in PHP!';
        }

        if (!extension_loaded('gd')) {
            $errors['warning'] = 'Warning: GD extension needs to be loaded for AbanteCart to work!';
        }

        if (!extension_loaded('mbstring') || !function_exists('mb_internal_encoding')) {
            $errors['warning'] = 'Warning: MultiByte String extension needs to be loaded for AbanteCart to work!';
        }
        if (!extension_loaded('zlib')) {
            $errors['warning'] = 'Warning: ZLIB extension needs to be loaded for AbanteCart to work!';
        }

        if ( !is_writable(ABC::env('DIR_CONFIG')) ) {
            $errors['warning'] = 'Warning: abc/config folder and files needs to be writable for AbanteCart to be installed!';
        }

        if (!is_writable(ABC::env('DIR_SYSTEM'))) {
            $errors['warning'] = 'Warning: System directory and all its children files/directories need to be writable for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('DIR_CACHE'))) {
            $errors['warning'] = 'Warning: Cache directory needs to be writable for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('DIR_LOGS'))) {
            $errors['warning'] = 'Warning: Logs directory needs to be writable for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('DIR_PUBLIC') . 'images')) {
            $errors['warning'] = 'Warning: Image directory and all its children files/directories need to be writable for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('DIR_PUBLIC') . 'images/thumbnails')) {
            if (file_exists(ABC::env('DIR_PUBLIC') . 'images/thumbnails') && is_dir(ABC::env('DIR_PUBLIC') . 'images/thumbnails')) {
                $errors['warning'] = 'Warning: images/thumbnails directory needs to be writable for AbanteCart to work!';
            } else {
                $result = mkdir(ABC::env('DIR_PUBLIC') . 'images/thumbnails', 0777, true);
                if ($result) {
                    chmod(ABC::env('DIR_PUBLIC') . 'images/thumbnails', 0777);
                    chmod(ABC::env('DIR_PUBLIC') . 'image', 0777);
                } else {
                    $errors['warning'] = 'Warning: images/thumbnails does not exists!';
                }
            }
        }

        if (!is_writable(ABC::env('DIR_APP') . 'downloads')) {
            $errors['warning'] = 'Warning: Download directory needs to be writable for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('DIR_APP_EXTENSIONS'))) {
            $errors['warning'] = 'Warning: Extensions directory needs to be writable for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('DIR_PUBLIC') . 'resources')) {
            $errors['warning'] = 'Warning: Resources directory needs to be writable for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('DIR_SYSTEM'))) {
            $errors['warning'] = 'Warning: Admin/system directory needs to be writable for AbanteCart to work!';
        }

        return $errors;
    }


    protected function _configure(array $options){
        if (!$options) {
            return ['No options to configure!'];
        }
        if (!ABC::env('DIR_CONFIG')) {
            ABC::env('DIR_CONFIG', ABC::env('DIR_APP') . 'system/config/');
        }

        if(!isset($options['root_dir']) || !$options['root_dir']){
            $options['root_dir'] = ABC::env('DIR_ROOT');
        }
        if(!isset($options['app_dir']) || !$options['app_dir']){
            $options['app_dir'] = ABC::env('DIR_APP');
        }
        if(!isset($options['public_dir']) || !$options['public_dir']){
            $options['public_dir'] = ABC::env('DIR_PUBLIC');
        }
        if(!isset($options['cache_driver']) || !$options['cache_driver']){
            $options['cache_driver'] = 'file';
        }

        //server name needs to be set for emails
        $server_name = getenv("SERVER_NAME");
        if(!$server_name){
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
    'default' => [
        'APP_NAME' => 'AbanteCart',
        'MIN_PHP_VERSION' => '7.0',
        'DIR_ROOT' => '{$options['root_dir']}',
        'DIR_APP' => '{$options['app_dir']}',
        'DIR_PUBLIC' => '{$options['public_dir']}',
        'SERVER_NAME' => '{$server_name}',
        'ADMIN_PATH' => '{$options['admin_path']}',
        'UNIQUE_ID' => '{$unique_id}',
        // SEO URL Keyword separator
        'SEO_URL_SEPARATOR' => '-',
        // EMAIL REGEXP PATTERN
        'EMAIL_REGEX_PATTERN' => '/^[A-Z0-9._%-]+@[A-Z0-9.-]{0,61}[A-Z0-9]\.[A-Z]{2,16}$/i',
        //postfixes for template override
        'POSTFIX_OVERRIDE' => '.override',
        'POSTFIX_PRE' => '.pre',
        'POSTFIX_POST' => '.post',
        'APP_CHARSET' => 'UTF-8'
    ]
];
EOD;
        $file = fopen(ABC::env('DIR_CONFIG') . 'app.php', 'w');
        if (!fwrite($file, $content)) {
            $result[] = 'Cannot to write file '. $file;
        }
        fclose($file);

        //write database config
        $content = <<<EOD
<?php
// Database Configuration
return [
    'default' => [
        'DB_DRIVER' => '{$options['db_driver']}',
        'DB_HOSTNAME' => '{$options['db_host']}',
        'DB_USERNAME' => '{$options['db_user']}',
        'DB_PASSWORD' => '{$options['db_password']}',
        'DB_DATABASE' => '{$options['db_name']}',
        'DB_PREFIX' => '{$options['db_prefix']}',
        'DB_CHARSET' => 'utf8',
        'DB_COLLATION' => 'utf8_unicode_ci'
        ]
];
EOD;
    $file = fopen(ABC::env('DIR_CONFIG') . 'database.php', 'w');
    if (!fwrite($file, $content)) {
        $result[] = 'Cannot to write file '. $file;
    }
    fclose($file);

    //write cache config
    $content = <<<EOD
<?php
return [
    'default' => [
        'CACHE_DRIVER' => '{$options['cache_driver']}'
    ]
];
EOD;
        $file = fopen(ABC::env('DIR_CONFIG') . 'cache.php', 'w');
        if (!fwrite($file, $content)) {
            $result[] = 'Cannot to write file '. $file;
        }
        fclose($file);

        return $result;
    }

    protected function _run_sql($data){
        $errors = [];
        $file = __DIR__.'/abantecart_database.sql';
        if (!is_file($file)) {
            $errors[] = 'Error: file ' . $file . ' not found!';
            return $errors;
        }
        $sql = file($file);
        if ($sql === false) {
            $errors[] = 'Error: cannot open file ' . $file;
            return $errors;
        }

        $db = new ADB(array(
                        'driver'    => $data['db_driver'],
                        'host'      => $data['db_host'],
                        'database'  => $data['db_name'],
                        'username'  => $data['db_user'],
                        'password'  => $data['db_password'],
                        'charset'   => 'utf8',
                        'collation' => 'utf8_unicode_ci',
                        'prefix'    => $data['db_prefix']
                        ));
        $query = '';
        foreach ($sql as $line) {
            $tsl = trim($line);

            if (($sql != '') && (substr($tsl, 0, 2) != "--") && (substr($tsl, 0, 1) != '#')) {
                $query .= $line;

                if (preg_match('/;\s*$/', $line)) {
                    $query = str_replace(" `ac_", " `" . $data['db_prefix'], $query);
                    $db->query($query); //no silence mode! if error - will throw to exception
                    $query = '';
                }
            }
        }

        $db->query("SET CHARACTER SET utf8;");
        $db->query("SET @@session.sql_mode = 'MYSQL40';");
        $salt_key = AHelperUtils::genToken(8);
        $db->query(
                "INSERT INTO `" . $data['db_prefix'] . "users`
            SET user_id = '1',
                user_group_id = '1',
                email = '" . $db->escape($data['email']) . "',
                username = '" . $db->escape($data['username']) . "',
                salt = '" . $db->escape($salt_key) . "', 
                password = '" . $db->escape(sha1($salt_key . sha1($salt_key . sha1($data['password'])))) . "',
                status = '1',
                date_added = NOW();");

        $db->query(
                "UPDATE `" . $data['db_prefix'] . "settings` 
                    SET value = '" . $db->escape($data['email']) . "' 
                    WHERE `key` = 'store_main_email'; ");
        $db->query(
                "UPDATE `" . $data['db_prefix'] . "settings` 
                    SET value = '" . $db->escape($data['http_server']) . "' 
                    WHERE `key` = 'config_url'; ");
        if (ABC::env('HTTPS')) {
            $db->query(
                    "UPDATE `" . $data['db_prefix'] . "settings` 
                        SET value = '" . $db->escape($data['http_server']) . "' 
                        WHERE `key` = 'config_ssl_url'; ");
            $db->query(
                    "UPDATE `" . $data['db_prefix'] . "settings` 
                        SET value = '2' 
                        WHERE `key` = 'config_ssl'; ");
        }
        $db->query(
                "UPDATE `" . $data['db_prefix'] . "settings` 
                SET value = '" . $db->escape(AHelperUtils::genToken(16)) . "' 
                WHERE `key` = 'task_api_key'; ");
        $db->query("INSERT INTO `" . $data['db_prefix'] . "settings` 
                    SET `group` = 'config', `key` = 'install_date', value = NOW(); ");
        $db->query("UPDATE `" . $data['db_prefix'] . "products` SET `viewed` = '0';");

        //run destructor and close db-connection
        unset($db);
        //clear cache dir in case of reinstall
        $cache = new ACache();
        $cache->setCacheStorageDriver('file');
        $cache->enableCache();
        $cache->remove('*');
        return $errors;
    }

    protected function _load_demo_data($options){
        $errors = [];
        $file = __DIR__.'/demo_data/abantecart_demo_data.sql';
        if (!is_file($file)) {
            $errors[] = 'Error: file ' . $file . ' not found!';
            return $errors;
        }
        $sql = file($file);
        if ($sql === false) {
            $errors[] = 'Error: cannot open file ' . $file;
            return $errors;
        }

        $db = new ADB( array(
            'driver'    => $options['db_driver'],
            'host'      => $options['db_host'],
            'database'  => $options['db_name'],
            'username'  => $options['db_user'],
            'password'  => $options['db_password'],
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => $options['db_prefix']
                        ) );
        $db->query("SET NAMES 'utf8'");
        $db->query("SET CHARACTER SET utf8");

        $query = '';

        foreach ($sql as $line) {
            $tsl = trim($line);

            if (($sql != '') && (substr($tsl, 0, 2) != "--") && (substr($tsl, 0, 1) != '#')) {
                $query .= $line;

                if (preg_match('/;\s*$/', $line)) {
                    $query = str_replace("DROP TABLE IF EXISTS `ac_", "DROP TABLE IF EXISTS `" .$options['db_prefix'], $query);
                    $query = str_replace("CREATE TABLE `ac_", "CREATE TABLE `" .$options['db_prefix'], $query);
                    $query = str_replace("INSERT INTO `ac_", "INSERT INTO `" .$options['db_prefix'], $query);

                    $result = $db->query($query);

                    if (!$result || $db->error) {
                        $errors[] = $db->error . "\n\t\t" . $query;
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

    protected function _help()
    {
        $output = "Usage:"."\n";
        $output .= "------------------------------------------------"."\n";
        $output .= "\n";
        $output .= "Commands:"."\n";
        $output .= "\t"."help - get help"."\n";
        $output .= "\t"."install - run installation process"."\n\n";

        $output .= "Parameters:"."\n\n";
        $options = $this->_get_option_list();

        foreach ($options as $opt => $ex) {
            $output .= "\t".$opt;
            if ($ex) {
                $output .= "=<value>"." \t\t"."\033[0;31m[required]\033[0m";
            } else {
                $output .= "     \t\t"."[optional]";
            }
            $output .= "\n\n";

        }

        $output .= "\n\nExample:\n";

        $output .= 'php do.php install ';
        foreach ($options as $opt => $ex) {
            if ($opt == '--demo-mode') {
                continue;
            }
            $output .= $opt.($ex ? "=".$ex : '')."  ";
        }
        $output .= "\n\n";

        return $output;
    }


    protected function _get_option_list()
    {
        return [
            '--root_dir'         => ABC::env('DIR_ROOT'),
            '--app_dir'          => ABC::env('DIR_APP'),
            '--public_dir'       => ABC::env('DIR_PUBLIC'),
            '--db_host'          => 'localhost',
            '--db_user'          => 'root',
            '--db_password'      => '******',
            '--db_name'          => 'abantecart',
            '--db_driver'        => 'amysqli',
            '--db_prefix'        => 'ac_',
            '--cache-driver'     => 'file',
            '--admin_path'       => 'your_admin',
            '--username'         => 'admin',
            '--password'         => 'admin',
            '--email'            => 'your_email@example.com',
            '--http_server'      => 'http://localhost/abantecart',
            '--with-sample-data' => '',
            '--demo-mode'        => '',
        ];
    }

}