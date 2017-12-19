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

namespace abc\core\backend\scripts;

use abc\ABC;
use abc\core\backend\ABCExec;
use abc\core\helper\AHelperUtils;
use abc\lib\AAssetPublisher;
use abc\lib\ACache;
use abc\lib\ADB;
use abc\lib\AException;

class Install implements ABCExec
{
    public function validate(string $action, array $options)
    {
        $action = ! $action ? 'install' : $action;
        $errors = [];
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

        if (isset($file_config['default']['ADMIN_PATH'])) {
            return ["AbanteCart is already installed!\n Note: to reinstall application just delete file abc/config/app.php"];
        }

        //check requirements first
        $errors = $this->validateRequirements();
        if ($errors) {
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

        if ( ! $options['db_driver']) {
            $errors['db_driver'] = 'Database driver required!';
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
                    'driver'    => $options['db_driver'],
                    'host'      => $options['db_host'],
                    'database'  => $options['db_name'],
                    'username'  => $options['db_user'],
                    'password'  => $options['db_password'],
                    'charset'   => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                    'prefix'    => $options['db_prefix'],
                ));
            } catch (AException $e) {
                $errors['warning'] = $e->getMessage();
            }
        }

        if ( ! is_writable(ABC::env('DIR_CONFIG'))) {
            $errors['warning'] = 'Error: Could not write to abc/config folder. Please check you have set the correct permissions on: '.ABC::env('DIR_CONFIG').'!';
        }

        return $errors;
    }

    public function run(string $action, array $options)
    {
        $output = null;
        $action = ! $action ? 'install' : $action;

        if ($action == 'install') {
            $errors = $this->_configure($options);

            if ( ! $errors) {
                $errors = $this->_run_sql($options);
            }
            if ( ! $errors && isset($options['with-sample-data'])) {
                $errors = $this->_load_demo_data($options);
            }
            // move assets to public directory
            if ( ! $errors) {
                $ap = new AAssetPublisher();
                $ap->publish('all');
                $errors = $ap->errors;
            }
            $output = $errors;
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

    protected function validateRequirements()
    {
        $errors = [];
        if (version_compare(phpversion(), ABC::env('MIN_PHP_VERSION'), '<')
            == true) {
            $errors['warning'] = 'Warning: You need to use PHP '
                .ABC::env('MIN_PHP_VERSION')
                .' or above for AbanteCart to work!';
        }

        if ( ! ini_get('file_uploads')) {
            $errors['warning'] = 'Warning: file_uploads needs to be enabled in PHP!';
        }

        if (ini_get('session.auto_start')) {
            $errors['warning'] = 'Warning: AbanteCart will not work with session.auto_start enabled!';
        }

        if ( ! extension_loaded('mysqli') && ! extension_loaded('pdo_mysql')) {
            $errors['warning'] = 'Warning: MySQLi extension needs to be loaded for AbanteCart to work!';
        }

        if ( ! function_exists('simplexml_load_file')) {
            $errors['warning'] = 'Warning: SimpleXML functions needs to be available in PHP!';
        }

        if ( ! extension_loaded('gd')) {
            $errors['warning']
                = 'Warning: GD extension needs to be loaded for AbanteCart to work!';
        }

        if ( ! extension_loaded('mbstring')
            || ! function_exists('mb_internal_encoding')) {
            $errors['warning'] = 'Warning: MultiByte String extension needs to be loaded for AbanteCart to work!';
        }
        if ( ! extension_loaded('zlib')) {
            $errors['warning'] = 'Warning: ZLIB extension needs to be loaded for AbanteCart to work!';
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
            if (file_exists(ABC::env('DIR_PUBLIC').'images/thumbnails')
                && is_dir(ABC::env('DIR_PUBLIC').'images/thumbnails')) {
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

        $content
            = <<<EOD
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
        $file = fopen(ABC::env('DIR_CONFIG').'app.php', 'w');
        if ( ! fwrite($file, $content)) {
            $result[] = 'Cannot to write file '.$file;
        }
        fclose($file);

        //write database config
        $content
            = <<<EOD
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
        $file = fopen(ABC::env('DIR_CONFIG').'database.php', 'w');
        if ( ! fwrite($file, $content)) {
            $result[] = 'Cannot to write file '.$file;
        }
        fclose($file);

        //write cache config
        $content
            = <<<EOD
<?php
return [
    'default' => [
        'CACHE_DRIVER' => '{$options['cache_driver']}'
    ]
];
EOD;
        $file = fopen(ABC::env('DIR_CONFIG').'cache.php', 'w');
        if ( ! fwrite($file, $content)) {
            $result[] = 'Cannot to write file '.$file;
        }
        fclose($file);

        return $result;
    }

    protected function _run_sql($data)
    {
        $errors = [];
        $file = __DIR__.'/abantecart_database.sql';
        if ( ! is_file($file)) {
            $errors[] = 'Error: file '.$file.' not found!';

            return $errors;
        }
        $sql = file($file);
        if ($sql === false) {
            $errors[] = 'Error: cannot open file '.$file;

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
            'prefix'    => $data['db_prefix'],
        ));
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
        $db->query("INSERT INTO `".$data['db_prefix']."settings` 
                    SET `group` = 'config', `key` = 'install_date', value = NOW(); ");
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
        $file = __DIR__.'/demo_data/abantecart_demo_data.sql';
        if ( ! is_file($file)) {
            $errors[] = 'Error: file '.$file.' not found!';
            return $errors;
        }
        $sql = file($file);
        if ($sql === false) {
            $errors[] = 'Error: cannot open file '.$file;
            return $errors;
        }

        $db = new ADB(array(
            'driver'    => $options['db_driver'],
            'host'      => $options['db_host'],
            'database'  => $options['db_name'],
            'username'  => $options['db_user'],
            'password'  => $options['db_password'],
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => $options['db_prefix'],
        ));
        $db->query("SET NAMES 'utf8'");
        $db->query("SET CHARACTER SET utf8");

        $query = '';

        foreach ($sql as $line) {
            $tsl = trim($line);

            if (($sql != '') && (substr($tsl, 0, 2) != "--")
                && (substr($tsl, 0, 1) != '#')) {
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
            $output = "php do install:".$action." ";
            if ($help_info['arguments']) {
                foreach ($help_info['arguments'] as $arg => $desc) {
                    if ($arg == '--demo-mode') {
                        continue;
                    }
                    $output .= $arg.($desc['default_value'] ? "="
                            .$desc['default_value'] : '')."  ";
                }
            }
            $options[$action]['example'] = $output;
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
                            'required'      => true,
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
                            'required'      => true,
                        ],
                        '--db_prefix'        => [
                            'description'   => 'Database table name prefix',
                            'default_value' => 'abc_',
                            'required'      => true,
                        ],
                        '--cache-driver'     => [
                            'description'   => 'Cache driver',
                            'default_value' => 'file',
                            'required'      => true,
                        ],
                        '--admin_path'       => [
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