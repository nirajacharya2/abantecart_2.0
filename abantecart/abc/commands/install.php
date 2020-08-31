<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */

namespace abc\commands;

use abc\commands\base\BaseCommand;
use abc\core\ABC;
use abc\core\engine\Registry;

use abc\core\lib\{
    AbcCache, AConfig, ADB, AError, AException, AExtensionManager, ALanguageManager, APackageManager
};
use H;

/**
 * Class Install
 *
 * @package abc\commands
 */
class Install extends BaseCommand
{
    public function validate(string $action, array &$options)
    {
        $action = !$action ? 'app' : $action;
        //if now options - check action
        if (!$options) {
            if (!in_array($action, ['app', 'package', 'extension', 'help'])) {
                return ['Error: Unknown Action Parameter!'];
            }
        }

        switch ($action) {
            case 'app':
                return $this->validateAppInstall($options);
            case 'extension':
                return $this->validateExtensionOptions($options);
            case 'package':
                return $this->validatePackageOptions($options);
            default:
                return [];
        }
    }

    /**
     * @param string $action
     * @param array $options
     *
     * @return array|bool|null
     * @throws AException
     * @throws \DebugBar\DebugBarException
     * @throws \ReflectionException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function run(string $action, array $options)
    {
        parent::run($action, $options);
        $action = !$action ? 'app' : $action;
        if ($action == 'app') {
            return $this->installApp($options);
        } elseif ($action == 'package') {
            return $this->installPackage($options);
        } elseif ($action == 'extension') {
            if (isset($options['install'])) {
                return $this->installExtension($options);
            } elseif (isset($options['uninstall'])) {
                return $this->uninstallExtension($options);
            } elseif (isset($options['remove'])) {
                return $this->removeExtension($options);
            }
        }

        return ['Error: unknown command called!'];
    }

    /**
     * @param $options
     *
     * @return array|bool
     * @throws AException
     * @throws \DebugBar\DebugBarException
     * @throws \ReflectionException
     */
    protected function installApp($options)
    {
        $this->fillDefaults($options);

        //make config-files
        $errors = $this->configure($options);
        //fill database
        if (!$errors) {
            $errors = $this->runSQL($options);
        }
        if (!$errors) {
            $registry = Registry::getInstance();
            $registry->set('cache', new AbcCache('file'));
            $config = new AConfig($registry, (string)$options['http_server']);
            $registry->set('config', $config);
            $registry->set('language', new ALanguageManager($registry));
            require_once ABC::env('DIR_APP').'commands'.DS.'deploy.php';
            $deploy = new Deploy();
            $ops = ['stage' => 'default'];
            if (isset($options['skip-caching'])) {
                $ops['skip-caching'] = 1;
            }
            $deploy->run('config', $ops);
        }

        if (!$errors && isset($options['with-sample-data'])) {
            $errors = $this->loadDemoData($options);
        }
        // deploy assets and generate cache
        if (!$errors) {
            require_once ABC::env('DIR_APP').'commands'.DS.'deploy.php';
            $deploy = new Deploy();
            $ops = ['all' => 1];
            if (isset($options['skip-caching'])) {
                $ops['skip-caching'] = 1;
            }
            $result = $deploy->run('all', $ops);
            if (is_array($result)) {
                $errors = $result;
            }
        }
        return $errors;
    }

    /**
     * @param $options
     *
     * @throws AException
     * @throws \ReflectionException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function installPackage($options)
    {
        if (!$options) {
            exit('Error: empty options for package installation');
        }
        $pm = new APackageManager([]);
        //confirm start of process
        if (!isset($options['force'])) {
            echo 'Do you really want to install package?'."\n\n";
            echo "Continue? (Y/N) : ";
            $stdIn = fopen('php://stdin', 'r');
            $user_response = fgetc($stdIn);
            if (!in_array($user_response, ['Y', 'YES', 'y', 'yes'])) {
                $this->stopRun(new APackageManager([]), 'Aborted.');
            }
            @fclose($stdIn);
        }

        $temp_dir = $archive_filename = '';

        if (isset($options['file'])) {
            $basename = basename($options['file']);
            $temp_dir = $pm->getTempDir().pathinfo(pathinfo($basename, PATHINFO_FILENAME), PATHINFO_FILENAME).'/';
            //remove temp directory if already exists
            if (is_dir($temp_dir)) {
                $result = $pm->removeDir($temp_dir);
                if (!$result) {
                    $this->stopRun($pm);
                }
            }
            @mkdir($temp_dir);
            if (!is_dir($temp_dir)) {
                $this->stopRun($pm, 'Cannot to create temporary directory '.$temp_dir.'. Please check permissions!');
            }
            $archive_filename = $options['file'];
        } elseif (isset($options['url'])) {
            $result = $pm->downloadPackageByURL($options['url']);
            if (!$result) {
                $this->stopRun($pm);
            }
            $temp_dir = $pm->getTempDir();
            $archive_filename = $temp_dir.$pm->package_info['package_name'];
        }

        $package_info = [
            'package_source' => 'file',
            'tmp_dir'        => $temp_dir,
            'package_name'   => basename($archive_filename),
            'package_size'   => filesize($archive_filename),
        ];

        //1. try to unpack archive
        if (!$pm->unpack($archive_filename, $package_info['tmp_dir'])) {
            $this->stopRun($pm);
        }

        $package_info = $pm->package_info;

        //get package info from package.xml
        $result = $pm->extractPackageInfo();
        if (!$result) {
            $this->stopRun($pm);
        }

        if (!$package_info['package_content']
            || ($package_info['package_content']['core'] && $package_info['package_content']['extensions'])
        ) {
            $this->stopRun($pm, 'Wrong package structure! Cannot find code-file list inside package.xml.');
        }

        $error_text = '';
        //check cart version compatibility
        if (!$pm->checkCartVersion()) {
            if ($pm->isCorePackage()) {
                $error_text = "Error: Can't install package. Your cart version is ".ABC::env('VERSION').". ";
                $error_text .= "Version(s) ".implode(', ', $pm->package_info['supported_cart_versions'])."  required.";
                $this->stopRun($pm, $error_text);
            } //do pause and ask user in non-forced mode
            elseif (!isset($options['force'])) {
                //for extensions show command prompt
                echo "\t\e[93mCurrent copy of this package is not verified for your version of AbanteCart (v"
                    .ABC::env('VERSION').").\n"
                    ."\tPackage build is specified for AbanteCart version(s) ".implode(', ',
                        $pm->package_info['supported_cart_versions'])."\n"
                    ."\tThis is not a problem, but if you notice issues"
                    ." or incompatibility, please contact extension developer.\n\n"
                    ."Continue? (Y/N) : ";
                $stdIn = fopen('php://stdin', 'r');
                $user_response = fgetc($stdIn);
                if (!in_array($user_response, ['Y', 'YES', 'y', 'yes'])) {
                    $this->stopRun($pm, 'Aborted.');
                }
                @fclose($stdIn);
            }
        }

        //need to validate destination dirs and files before start
        $result = $pm->validateDestination();
        if (!$result) {
            $this->stopRun($pm, "Permission denied for files:\n".implode("\n", $pm->errors));
        }

        //ok. let's show license text
        if (!isset($options['force'])) {
            foreach (
                [
                    $package_info['package_dir']."release_notes.txt",
                    $package_info['package_dir']."license.txt",
                ] as $file
            ) {
                $this->showConfirmation($file);
                echo "\n\n";
            }
        }

        //ok. let's install all from package
        //first try to install all extensions
        if ($pm->isCorePackage()) {
            $pm->upgradeCore();
        } else {
            $pm->installPackageExtensions();
        }
        if ($pm->message_log) {
            echo "\n\n";
            echo implode("\n", $pm->message_log);
        }
        if ($pm->errors) {
            $error_text .= implode("\n", $pm->errors)."\n";
            throw new AException($error_text, AC_ERR_LOAD);
        }
    }

    /**
     * @param $file_path
     *
     * @throws AException
     * @throws \ReflectionException
     */
    protected function showConfirmation($file_path)
    {
        if (file_exists($file_path)) {
            $agreement_text = file_get_contents($file_path);
            //detect encoding of file
            $is_utf8 = mb_detect_encoding($agreement_text, ABC::env('APP_CHARSET'), true);
            if (!$is_utf8) {
                $agreement_text = 'Oops. Something goes wrong. Try to continue or check error log for details.';
                $err = new AError('Incorrect character set encoding of file '.$file_path.' has been detected.');
                $err->toLog();
            }
            if ($agreement_text) {
                echo $agreement_text."\n\n";
                echo "I Agree/ Not Agree (Y/N) : ";
                $stdIn = fopen('php://stdin', 'r');
                $user_response = fgetc($stdIn);
                if (!in_array($user_response, ['Y', 'YES', 'y', 'yes'])) {
                    $this->stopRun(new APackageManager([]), 'Aborted.');
                }
                @fclose($stdIn);
            }
        }
    }

    /**
     * @param $options
     *
     * @return bool|null
     * @throws AException
     * @throws \ReflectionException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function uninstallExtension($options)
    {
        if (!$options) {
            exit('Error: empty options for uninstall!');
        }
        $pm = new APackageManager([]);
        if (!isset($options['force'])) {
            echo "\n\nUninstall extension {$options['extension_text_id']}?\n"
                ."Continue? (Y/N) : ";
            $stdIn = fopen('php://stdin', 'r');
            $user_response = fgetc($stdIn);
            if (!in_array($user_response, ['Y', 'YES', 'y', 'yes'])) {
                $this->stopRun($pm, 'Aborted');
            }
            @fclose($stdIn);
        }
        $em = new AExtensionManager();
        $all_installed = $em->getInstalled('exts');
        if (!in_array($options['extension_text_id'], $all_installed)) {
            exit('Error: '.$options['extension_text_id'].' is not installed!'."\n");
        }
        $result = $em->uninstall($options['extension_text_id'],
            H::getExtensionConfigXml($options['extension_text_id']));
        if (!$result) {
            echo implode("\n", $em->errors)."\n";
        }
        return $result;
    }

    /**
     * @param $options
     *
     * @return bool
     * @throws AException
     * @throws \ReflectionException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function installExtension($options)
    {
        if (!$options) {
            exit('Error: empty options for extension install!');
        }
        $pm = new APackageManager([]);
        if (!isset($options['force'])) {
            echo "\n\nDo you want to install extension {$options['extension_text_id']}?\n"
                ."Continue? (Y/N) : ";
            $stdIn = fopen('php://stdin', 'r');
            $user_response = fgetc($stdIn);
            if (!in_array($user_response, ['Y', 'YES', 'y', 'yes'])) {
                $this->stopRun($pm, 'Aborted');
            }
            @fclose($stdIn);
        }
        $em = new AExtensionManager();
        $all_installed = $em->getInstalled('exts');
        if (in_array($options['extension_text_id'], $all_installed)) {
            exit('Error: '.$options['extension_text_id'].' already installed!');
        }

        $result = $em->install($options['extension_text_id'],
            H::getExtensionConfigXml($options['extension_text_id']));
        if (!$result) {
            echo implode("\n", $em->errors)."\n";
        }
        return $result;
    }

    /**
     * @param $options
     *
     * @return bool|null
     * @throws AException
     * @throws \ReflectionException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function removeExtension($options)
    {
        if (!$options) {
            exit('Error: empty options for extension uninstall!');
        }
        $pm = new APackageManager([]);
        if (!isset($options['force'])) {
            echo "\n\nPlease confirm extension {$options['extension_text_id']} removing.\n"
                ."Continue? (Y/N) : ";
            $stdIn = fopen('php://stdin', 'r');
            $user_response = fgetc($stdIn);
            if (!in_array($user_response, ['Y', 'YES', 'y', 'yes'])) {
                $this->stopRun($pm, 'Aborted');
            }
            @fclose($stdIn);
        }

        //uninstall first without confirmation
        $em = new AExtensionManager();
        $opts = $options;
        $opts['force'] = true;
        $all_installed = $em->getInstalled('exts');
        if (in_array($options['extension_text_id'], $all_installed)) {
            $result = $this->uninstallExtension($opts);
            if (!$result) {
                return false;
            }
        }

        $result = $em->delete($options['extension_text_id']);
        if (!$result) {
            echo implode("\n", $em->errors);
        }
        return $result;
    }

    /**
     * @param APackageManager $pm
     * @param string $error_text
     *
     * @throws AException
     * @throws \ReflectionException
     */
    protected function stopRun(APackageManager $pm, $error_text = '')
    {
        $pm->removeDir($pm->package_info['tmp_dir']);
        $error_text = $error_text ? $error_text : implode("\n", $pm->errors);
        throw new AException($error_text, AC_ERR_USER_ERROR);
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    protected function fillDefaults(array &$options)
    {
        if (!$options) {
            return false;
        }
        if (!isset($options['root_dir']) || !$options['root_dir']) {
            $options['root_dir'] = ABC::env('DIR_ROOT');
        }
        if (!isset($options['app_dir']) || !$options['app_dir']) {
            $options['app_dir'] = ABC::env('DIR_APP');
        }
        if (!isset($options['public_dir']) || !$options['public_dir']) {
            $options['public_dir'] = ABC::env('DIR_PUBLIC');
        }
        if (!isset($options['cache_driver']) || !$options['cache_driver']) {
            $options['cache_driver'] = 'file';
        }
        if (!isset($options['db_host']) || !$options['db_host']) {
            $options['db_host'] = 'localhost';
        }
        if (!isset($options['db_prefix']) || !$options['db_prefix']) {
            $options['db_prefix'] = 'ac_';
        }
        if (!isset($options['db_driver']) || !$options['db_driver']) {
            $options['db_driver'] = 'mysql';
        }
        if (substr($options['http_server'], -1) != '/') {
            $options['http_server'] .= '/';
        }

        return true;
    }

    /**
     * @param string $action
     * @param array $options
     *
     * @return bool|void
     */
    public function finish(string $action, array $options)
    {
        if ($action == 'app') {
            $this->finalMessageAppInstall($options);
        } elseif ($action == 'package') {
            $this->write("Package installation process complete.");
        } elseif ($action == 'extension') {
            $this->finalMessageExtensionInstall($options);
        }
        parent::finish($action, $options);
    }

    /**
     * @param array $options
     */
    protected function finalMessageExtensionInstall($options = [])
    {
        $this->write("AbanteCart extension installation process complete\n");
    }

    /**
     * @param $options
     */
    protected function finalMessageAppInstall($options)
    {
        $this->write("AbanteCart installation process complete\n");
        $this->write("\t"."Store link: ".$options['http_server']."\n");
        $this->write("\t"."Admin link: ".$options['http_server']."?s=".$options['admin_secret']."\n");
        //suggest to change permissions
        $dirs = [
            ABC::env('DIR_CONFIG'),
            ABC::env('DIR_SYSTEM'),
            ABC::env('CACHE')['stores']['file']['path'],
            ABC::env('DIR_LOGS'),
            ABC::env('DIR_PUBLIC').'images',
            ABC::env('DIR_PUBLIC').'images/thumbnails',
            ABC::env('DIR_APP').'downloads',
            ABC::env('DIR_APP_EXTENSIONS'),
            ABC::env('DIR_PUBLIC').'resources',
        ];
        $this->write("Following directories must have write permissions for this server webserver user\n");
        foreach ($dirs as $dir) {
            $this->write("\t".$dir);
        }
    }

    /**
     * @return array
     */
    protected function validateAppRequirements()
    {
        $errors = [];
        if (version_compare(phpversion(), ABC::env('MIN_PHP_VERSION'), '<') == true) {
            $errors['warning'] =
                'Warning: You need to use PHP '.ABC::env('MIN_PHP_VERSION').' or above for AbanteCart to work!';
        }

        if (!ini_get('file_uploads')) {
            $errors['warning'] = 'Warning: "file_uploads" needs to be enabled in PHP!';
        }

        if (ini_get('session.auto_start')) {
            $errors['warning'] = 'Warning: AbanteCart will not work with session.auto_start enabled!';
        }

        if (!function_exists('simplexml_load_file')) {
            $errors['warning'] = 'Warning: SimpleXML functions needs to be available in PHP!';
        }

        if (!extension_loaded('gd')) {
            $errors['warning'] = 'Warning: GD extension needs to be loaded for AbanteCart to work!';
        }

        if (!extension_loaded('mbstring')
            || !function_exists('mb_internal_encoding')
        ) {
            $errors['warning'] = 'Warning: MultiByte String extension needs to be loaded for AbanteCart to work!';
        }
        if (!extension_loaded('zlib')) {
            $errors['warning'] = 'Warning: ZLIB extension needs to be loaded for AbanteCart to work!';
        }
        if (!extension_loaded('phar')) {
            $errors['warning'] = 'Warning: PHAR extension needs to be loaded for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('DIR_CONFIG'))) {
            $errors['warning'] = 'Warning: '.ABC::env('DIR_CONFIG')
                .' folder and files needs to be writable for AbanteCart to be installed!';
        }

        if (!is_writable(ABC::env('DIR_SYSTEM'))) {
            $errors['warning'] = 'Warning: System directory '.ABC::env('DIR_SYSTEM')
                .' and all its children files/directories need to be writable for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('CACHE')['stores']['file']['path'])) {
            $errors['warning'] =
                'Warning: Cache directory '.ABC::env('CACHE')['stores']['file']['path']
                .' needs to be writable for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('DIR_LOGS'))) {
            $errors['warning'] =
                'Warning: Logs directory '.ABC::env('DIR_LOGS').' needs to be writable for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('DIR_PUBLIC').'images')) {
            $errors['warning'] = 'Warning: Image directory '.ABC::env('DIR_PUBLIC')
                .'images and all its children files/directories need to be writable for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('DIR_PUBLIC').'images/thumbnails')) {
            if (is_dir(ABC::env('DIR_PUBLIC').'images/thumbnails')) {
                $errors['warning'] = 'Warning: '.ABC::env('DIR_PUBLIC')
                    .'images/thumbnails directory needs to be writable for AbanteCart to work!';
            } else {
                $result = mkdir(ABC::env('DIR_PUBLIC').'images/thumbnails', 0777, true);
                if ($result) {
                    chmod(ABC::env('DIR_PUBLIC').'images/thumbnails', 0777);
                    chmod(ABC::env('DIR_PUBLIC').'image', 0777);
                } else {
                    $errors['warning'] = 'Warning: '.ABC::env('DIR_PUBLIC').' images/thumbnails does not exists!';
                }
            }
        }

        if (!is_dir(ABC::env('DIR_APP').'downloads')) {
            @mkdir(ABC::env('DIR_APP').'downloads');
        }

        if (!is_writable(ABC::env('DIR_APP').'downloads')) {
            $errors['warning'] = 'Warning: Download directory '.ABC::env('DIR_APP').'downloads'
                .' needs to be writable for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('DIR_APP_EXTENSIONS'))) {
            $errors['warning'] = 'Warning: Extensions directory '.ABC::env('DIR_APP_EXTENSIONS')
                .' needs to be writable for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('DIR_PUBLIC').'resources')) {
            $errors['warning'] = 'Warning: Resources directory '.ABC::env('DIR_PUBLIC')
                .'resources needs to be writable for AbanteCart to work!';
        }

        return $errors;
    }

    /**
     * @param array $options
     *
     * @return array
     * @throws AException
     * @throws \DebugBar\DebugBarException
     */
    public function configure(array $options)
    {
        if (!$options) {
            return ['No options to configure!'];
        }
        if (!ABC::env('DIR_CONFIG')) {
            ABC::env('DIR_CONFIG', ABC::env('DIR_APP').'system/config/');
        }

        //server name needs to be set for emails
        $server_name = getenv("SERVER_NAME");
        if (!$server_name) {
            $value = rtrim($options['http_server'], '/.\\').'/';
            $server_name = parse_url($value, PHP_URL_HOST);
        }

        //generate unique app ID
        $unique_id = md5(time());

        $result = [];
        //write application config

        $dirs = [
            'root'   => (DS == '\\' ? $options['root_dir'].'\\' : $options['root_dir']),
            'app'    => (DS == '\\' ? $options['app_dir'].'\\' : $options['app_dir']),
            'public' => (DS == '\\' ? $options['public_dir'].'\\' : $options['public_dir']),
            'config' => $options['app_dir'].'config'.DS,
            'cache'  => $options['app_dir'].'system'.DS.'cache'.DS,

        ];
        $content = <<<EOD
<?php
return [
        'APP_NAME' => 'AbanteCart',
        'MIN_PHP_VERSION' => '7.0.0',
        'DIR_ROOT' => '{$dirs['root']}',
        'DIR_APP' => '{$dirs['app']}',
        'DIR_PUBLIC' => '{$dirs['public']}',
        'SERVER_NAME' => '{$server_name}',
        'ADMIN_SECRET' => '{$options['admin_secret']}',
        'UNIQUE_ID' => '{$unique_id}',
        // SEO URL Keyword separator
        'SEO_URL_SEPARATOR' => '-',
        // EMAIL REGEXP PATTERN
        'EMAIL_REGEX_PATTERN' => '/^[A-Z0-9._\'%-]+@[A-Z0-9.-]{0,61}[A-Z0-9]\.[A-Z]{2,16}$/i',
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
                        'driver' => '{$options['cache_driver']}',
                        'stores' => [
                            '{$options['cache_driver']}' => [
                                //folder where we storing cache files
                                'path'   => '{$dirs['cache']}',
                                //time-to-live in seconds
                                //also can be Datetime Object
                                'ttl'    => 86400
                            ],
                            /*'memcached' => [ 
                                               'servers' => [
                                                    [
                                                        'host' => '127.0.0.1',
                                                        'port' => 11211,
                                                        'weight' => 100
                                                    ]
                                               ]
                                            ]*/
                        ]
                    ],
        //enable debug info collection
        // 1 - output to debug-bar and logging, 2 - only logging (see log-directory)
        'DEBUG' => 1,
        /*
         * Level 0 - no logs , only exception errors
         * Level 1 - errors and warnings
         * Level 2 - #1 + mysql site load, php file execution time and page elements load time
         * Level 3 - #2 + basic logs and stack of execution
         * Level 4 - #3 + dump mysql statements
         * Level 5 - #4 + intermediate variable
         *
         * */
        'DEBUG_LEVEL' => 5,
        'ENCRYPTION_KEY' => '12345',
        // bootstrap 3 admin template
        // 'adminTemplate' => 'default_bs3',

        // cache settings for abac 3d-party factory
        
        'ABAC' =>
            [   
                'CONFIG_DIRECTORY' => [
                    '{$dirs['config']}abac'
                ],
                'CACHE_ENABLE' => true,
            //  'CACHE_FOLDER' => '{$dirs['cache']}abac',
            //  'CACHE_TTL'    => '3600',
            //  'CACHE_DRIVER' => 'text'
            ],
            
        'RABBIT_MQ' => [
            'HOST' => '',
            'PORT' => 5672,
            'USER' => '',
            'PASSWORD' => ''
           ]

];
EOD;
        $file = fopen(ABC::env('DIR_CONFIG').'default'.DS.'config.php', 'w');
        if (!fwrite($file, $content)) {
            $result[] = 'Cannot to write file '.$file;
        }
        fclose($file);

        $file = fopen(ABC::env('DIR_CONFIG').'enabled.config.php', 'w');
        $content = <<<EOD
<?php
// name of current stage. Will be used to find filename with current config by mask your_stage_name.config.php 
return 'default';
EOD;
        if (!fwrite($file, $content)) {
            $result[] = 'Cannot to write file '.$file;
        }
        fclose($file);

        //adds into environment
        $registry = Registry::getInstance();
        $db_config = [
            $options['db_driver'] =>
                [
                    'DB_DRIVER'    => $options['db_driver'],
                    'DB_HOST'      => $options['db_host'],
                    'DB_PORT'      => $options['db_port'],
                    'DB_USER'      => $options['db_user'],
                    'DB_PASSWORD'  => $options['db_password'],
                    'DB_NAME'      => $options['db_name'],
                    'DB_PREFIX'    => $options['db_prefix'],
                    'DB_CHARSET'   => 'utf8',
                    'DB_COLLATION' => 'utf8_unicode_ci',
                ],
        ];
        ABC::env('DB_CURRENT_DRIVER', $options['db_driver']);
        ABC::env('DATABASES', $db_config);
        $registry->set('db', new ADB($db_config[$options['db_driver']]));
        ABC::env('CACHE', ['driver' => $options['cache_driver']]);
        return $result;
    }

    /**
     * @param array $options
     *
     * @return array
     * @throws AException
     * @throws \DebugBar\DebugBarException
     */
    public function runSQL(array $options)
    {
        $errors = [];
        $file = ABC::env('DIR_ROOT').'install'.DS.$options['db_driver'].'.database.sql';
        if (!is_file($file)) {
            $errors[] = 'Error: file '.$file.' not found!';

            return $errors;
        }
        $sql = file($file);
        if ($sql === false) {
            $errors[] = 'Error: cannot open file '.$file;
            return $errors;
        }
        $db_config = ABC::env('DATABASES');
        $db = new ADB($db_config[$options['db_driver']]);
        $query = '';
        foreach ($sql as $line) {
            $tsl = trim($line);
            if (($sql != '') && (substr($tsl, 0, 2) != "--")
                && (substr($tsl, 0, 1) != '#')
            ) {
                $query .= $line;
                if (preg_match('/;\s*$/', $line)) {
                    $query = str_replace(" `ac_", " `".$options['db_prefix'], $query);
                    $db->query($query); //no silence mode! if error - will throw to exception
                    $query = '';
                }
            }
        }

        $db->query("SET CHARACTER SET utf8;");
        $salt_key = H::genToken(8);
        $db->query(
            "INSERT INTO `".$options['db_prefix']."users`
            SET user_id = '1',
                user_group_id = '1',
                email = '".$db->escape($options['email'])."',
                username = '".$db->escape($options['username'])."',
                salt = '".$db->escape($salt_key)."', 
                PASSWORD = '".$db->escape(sha1($salt_key.sha1($salt_key.sha1($options['password']))))."',
                STATUS = '1',
                date_added = NOW();");

        $db->query(
            "UPDATE `".$options['db_prefix']."settings` 
                    SET value = '".$db->escape($options['email'])."' 
                    WHERE `key` = 'store_main_email'; ");
        $db->query(
            "UPDATE `".$options['db_prefix']."settings` 
                    SET value = '".$db->escape($options['http_server'])."' 
                    WHERE `key` = 'config_url'; ");
        if (ABC::env('HTTPS')) {
            $db->query(
                "UPDATE `".$options['db_prefix']."settings` 
                        SET value = '".$db->escape($options['http_server'])."' 
                        WHERE `key` = 'config_ssl_url'; ");
            $db->query(
                "UPDATE `".$options['db_prefix']."settings` 
                        SET value = '2' 
                        WHERE `key` = 'config_ssl'; ");
        }
        $db->query(
            "UPDATE `".$options['db_prefix']."settings` 
                SET value = '".$db->escape(H::genToken(16))."' 
                WHERE `key` = 'task_api_key'; ");
        $db->query(
            "INSERT INTO `".$options['db_prefix']."settings` 
                    SET 
                        `group` = 'config', 
                        `key` = 'install_date', 
                        `value` = NOW(); ");
        $db->query("UPDATE `".$options['db_prefix']."products` SET `viewed` = '0';");

        //run destructor and close db-connection
        unset($db);
        //clear cache dir in case of reinstall
        $cache = new AbcCache('file');
        $cache->flush();

        return $errors;
    }

    /**
     * @param $options
     *
     * @return array
     * @throws AException
     * @throws \DebugBar\DebugBarException
     */
    public function loadDemoData($options)
    {
        $errors = [];
        $file = ABC::env('DIR_ROOT').'install'.DS.'demo_data'.DS.$options['db_driver'].'.demo_data.sql';
        if (!is_file($file)) {
            $errors[] = 'Error: file '.$file.' not found!';
            return $errors;
        }
        $sql = file($file);
        if ($sql === false) {
            $errors[] = 'Error: cannot open file '.$file;
            return $errors;
        }
        $db_config = ABC::env('DATABASES');
        $db = new ADB($db_config[$options['db_driver']]);
        $db->query("SET NAMES 'utf8'");
        $db->query("SET CHARACTER SET utf8");

        $query = '';
        foreach ($sql as $line) {
            $tsl = trim($line);
            if (($sql != '') && (substr($tsl, 0, 2) != "--") && (substr($tsl, 0, 1) != '#')) {
                $query .= $line;

                if (preg_match('/;\s*$/', $line)) {
                    $query = str_replace(" `ac_", " `".$options['db_prefix'], $query);
                    $result = $db->query($query, true); //silence mode
                    if (!$result || $db->error) {
                        $errors[] = $db->error."\n\t\t".$query;
                        break;
                    }
                    $query = '';
                }
            }
        }
        $db->query("SET CHARACTER SET utf8");

        //clear earlier created cache by AConfig and ALanguage classes in previous step
        $cache = new AbcCache('file');
        $cache->flush();

        return $errors;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function help($options = [])
    {
        $options = $this->getOptionList();
        foreach ($options as $action => $help_info) {
            $output = "php abcexec install:".$action." ";
            $maximal = $minimal = '';
            if ($help_info['arguments']) {
                foreach ($help_info['arguments'] as $arg => $desc) {
                    if ($arg == '--demo-mode') {
                        continue;
                    }
                    $maximal .= $arg.($desc['default_value'] ? "=".$desc['default_value'] : '')."  ";
                    if ($desc['required']) {
                        $minimal .= $arg.($desc['default_value'] ? "=".$desc['default_value'] : '')."  ";
                    }
                }
            }
            if ($action == 'app' && $maximal != $minimal) {
                $options[$action]['example'] = "\n\t\tWith minimal parameters\n\n\t\t\t".$output.$minimal."\n\n";
                $options[$action]['example'] .= "\t\tWith all parameters\n\n\t\t\t".$output.$maximal."\n\n";
            } else {
                $options[$action]['example'] = "\n\t\t".$help_info['example']."\n\n";
            }
        }

        return $options;
    }

    /**
     * @return array
     */
    protected function getOptionList()
    {
        return [
            'app'       =>
                [
                    'description' => 'Run AbanteCart installation process',
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
                            'default_value' => 'your_database_name',
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
                        '--admin_secret'     => [
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
                        '--skip-caching'     => [
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
            'package'   =>
                [
                    'description' => 'Install package',
                    'arguments'   => [
                        '--file'             => [
                            'description'   => 'Full path to package. Only ZIP, TAR and TAR.GZ archives allowed.',
                            'default_value' => '/full/path/to/your/package.zip',
                            'required'      => 'conditional',
                        ],
                        '--url'              => [
                            'description'   => 'URL of package.',
                            'default_value' => '',
                            'required'      => 'conditional',
                        ],
                        '--installation_key' => [
                            'description'   => "Secret installation Key for remote install "
                                                ."from official Abantecart servers",
                            'default_value' => '',
                            'required'      => 'conditional',
                        ],
                        '--force'            => [
                            'description'   => 'Force mode to prevent script pause on command prompt.',
                            'default_value' => '',
                            'required'      => false,
                        ],
                    ],
                    'example'     => "php ".$_SERVER['PHP_SELF']
                        ." install:package --file=/full/path/to/your/package.zip\n\tor\n".
                        "\t\tphp ".$_SERVER['PHP_SELF']." install:package --url=http://your_url_to_package\n\tor\n".
                        "\t\tphp ".$_SERVER['PHP_SELF']." install:package --installation_key=****************\n",
                ],
            'extension' =>
                [
                    'description' => 'Run install/uninstall/remove process of extension',
                    'arguments'   => [
                        '--install'           => [
                            'description'   => 'Install extension',
                            'default_value' => '',
                            'required'      => 'conditional',
                        ],
                        '--uninstall'         => [
                            'description'   => 'Uninstall extension',
                            'default_value' => '',
                            'required'      => 'conditional',
                        ],
                        '--remove'            => [
                            'description'   => 'Uninstall and delete extension',
                            'default_value' => '',
                            'required'      => 'conditional',
                        ],
                        '--extension_text_id' => [
                            'description'   => 'Extension Text ID. Required parameter'
                                                .' for uninstall and remove process.',
                            'default_value' => '',
                            'required'      => true,
                        ],
                        '--force'             => [
                            'description'   => 'Force mode to prevent script pause on command prompt.',
                            'default_value' => '',
                            'required'      => false,
                        ],
                    ],
                    'example'     => "php ".$_SERVER['PHP_SELF']
                        ." install:extension --install --extension_text_id=default_cod\n\tor\n".
                        "\t\tphp ".$_SERVER['PHP_SELF']
                        ." install:extension --uninstall --extension_text_id=default_cod\n\tor\n".
                        "\t\tphp ".$_SERVER['PHP_SELF']." install:extension --remove --extension_text_id=default_cod\n",
                ],
        ];
    }

    /**
     * @param $options
     *
     * @return array
     * @throws \DebugBar\DebugBarException
     */
    public function validateAppInstall($options)
    {
        //Check if cart is already installed
        if (
            file_exists(ABC::env('DIR_CONFIG').'enabled.config.php')
            ||
            file_exists(ABC::env('DIR_CONFIG').'default'.DS.'config.php')

        ) {
            return [
                "AbanteCart is already installed!\n "
                ."Suggestion: to reinstall application just delete files:\n "
                .ABC::env('DIR_CONFIG')."enabled.config.php"
                ." AND\n"
                .ABC::env('DIR_CONFIG')."default".DS."config.php"
            ];
        }

        //check requirements first
        $errors = $this->validateAppRequirements();
        if ($errors) {
            return $errors;
        }

        $this->fillDefaults($options);
        //then check options
        if (!$options['admin_secret']) {
            $errors['admin_secret'] = 'Admin unique name is required!';
        } else {
            if (preg_match('/[^A-Za-z0-9_]/', $options['admin_secret'])) {
                $errors['admin_secret'] = 'Admin unique name contains non-alphanumeric characters!';
            }
        }

        if ($options['db_driver'] == 'mysql' && !(extension_loaded('mysqli') || extension_loaded('pdo_mysql'))) {
            $errors['db_driver'] = 'MySQLi or PDO_mysql extension needs to be loaded for AbanteCart to work!';
        }
        if (!$options['db_host']) {
            $errors['db_host'] = 'Host name required!';
        }

        if (!$options['db_user']) {
            $errors['db_user'] = 'User name required!';
        }

        if (!$options['db_name']) {
            $errors['db_name'] = 'Database Name required!';
        }

        if (!$options['username']) {
            $errors['username'] = 'Username required!';
        }

        if (!$options['password']) {
            $errors['password'] = 'Password required!';
        }

        $pattern = '/^([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])(([a-z0-9-])*([a-z0-9]))+(\.([a-z0-9])([-a-z0-9_-])?([a-z0-9])+)+$/i';

        if (!preg_match($pattern, $options['email'])) {
            $errors['email'] = 'Invalid E-Mail!';
        }

        if (!empty($options['db_prefix'])
            && preg_match('/[^A-Za-z0-9_]/', $options['db_prefix'])
        ) {
            $errors['db_prefix'] = 'DB prefix contains non-alphanumeric characters!';
        }

        if ($options['db_driver']
            && $options['db_host']
            && $options['db_user']
            && $options['db_password']
            && $options['db_name']
        ) {
            try {
                new ADB([
                    'DB_DRIVER'    => $options['db_driver'],
                    'DB_HOST'      => $options['db_host'],
                    'DB_NAME'      => $options['db_name'],
                    'DB_USER'      => $options['db_user'],
                    'DB_PASSWORD'  => $options['db_password'],
                    'DB_CHARSET'   => 'utf8',
                    'DB_COLLATION' => 'utf8_unicode_ci',
                    'DB_PREFIX'    => $options['db_prefix'],
                ]);
            } catch (AException $e) {
                $errors['error'] = $e->getMessage()."\n";
            }
        }

        if (!is_writable(ABC::env('DIR_CONFIG'))) {
            $errors['error'] .=
                'Error: Could not write to abc/config folder. Please check you have set the correct permissions on: '
                .ABC::env('DIR_CONFIG')."!\n";
        }

        if (!is_file(__DIR__.DS.'deploy.php')) {
            $errors['warning'] .= 'Dependency Error: deploy.php script not found in '.__DIR__."\n";
        }

        $url_parse_result = parse_url($options['http_server']);
        if (!$url_parse_result) {
            $errors['http_server'] = 'Wrong value of http_server parameter!';
        }

        return $errors;
    }

    /**
     * @param $options
     *
     * @return array
     * @throws AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function validateExtensionOptions($options)
    {
        $errors = [];
        if (!isset($options['extension_text_id'])
            && !isset($options['install'])
            && !isset($options['uninstall'])
            && !isset($options['remove'])
        ) {
            return [
                'Oops. Have no idea what should i do. '
                .'Please give me extension_text_id and action (--install, --uninstall or --remove).'
            ];
        }

        $deleting = isset($options['uninstall']) || isset($options['remove']) ? true : false;

        if ($deleting) {
            $registry = Registry::getInstance();
            $extension_info = $registry->get('extensions')->getExtensionInfo($options['extension']);
            if (!$extension_info) {
                return ['Extension "'.$options['extension_text_id'].'" not found!'];
            }
            // check dependencies
            $ext = new AExtensionManager();
            $validate = $ext->checkDependantsBeforeUninstall($options['extension']);
            if (!$validate) {
                $errors = $ext->errors;
            }
        }
        return $errors;
    }

    /**
     * @param $options
     *
     * @return array
     */
    public function validatePackageOptions($options)
    {
        $errors = [];
        if (!isset($options['file'])
            && !isset($options['installation_key'])
            && !isset($options['url'])
        ) {
            return [
                'Oops. Have no idea what should i do. Please give me file path, '
                .'url or installation key of package! See help for syntax.'
            ];
        }

        //check for file install
        if (isset($options['file']) && !is_file($options['file'])) {
            $errors = ['Cannot find package file '.$options['file']];
        }
        if (isset($options['file']) && !$errors
            && !is_int(strpos($options['file'], '.tar.gz'))
            && strtolower(pathinfo($options['file'], PATHINFO_EXTENSION)) != 'zip'
            && strtolower(pathinfo($options['file'], PATHINFO_EXTENSION)) != 'tar'
        ) {
            $errors = ['Only ZIP, TAR or TAR.GZ files allowed!'];
        }

        if (!$options['url'] || parse_url($options['file']) === 'false') {
            $errors = ['Incorrect URL!'];
        }

        return $errors;
    }

}