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

namespace install\models;

use abc\core\ABC;
use abc\commands\Deploy;
use abc\commands\Install;
use abc\core\engine\Model;
use abc\core\lib\AbcCache;
use abc\core\lib\ADB;
use abc\core\lib\AException;

require_once ABC::env('DIR_APP').'commands'.DS.'install.php';
require_once ABC::env('DIR_APP').'commands'.DS.'deploy.php';

class ModelInstall extends Model
{
    public $error;

    /**
     * @param array $data
     *
     * @return bool
     * @throws \DebugBar\DebugBarException
     */
    public function validateSettings($data)
    {
        if (!$data['admin_secret']) {
            $this->error['admin_secret'] = 'Admin unique name is required!';
        } else {
            if (preg_match('/[^A-Za-z0-9_]/', $data['admin_secret'])) {
                $this->error['admin_secret'] = 'Admin unique name contains non-alphanumeric characters!';
            }
        }

        if (!$data['db_driver']) {
            $this->error['db_driver'] = 'Driver required!';
        }
        if (!$data['db_host']) {
            $this->error['db_host'] = 'Host required!';
        }

        if (!$data['db_user']) {
            $this->error['db_user'] = 'User required!';
        }

        if (!$data['db_name']) {
            $this->error['db_name'] = 'Database Name required!';
        }

        if (!$data['username']) {
            $this->error['username'] = 'Username required!';
        }

        if (!$data['password']) {
            $this->error['password'] = 'Password required!';
        }
        if (!$data['password_confirm']) {
            $this->error['password_confirm'] = 'Password required!';
        }
        if ($data['password'] != $data['password_confirm']) {
            $this->error['password_confirm'] = 'Password does not match the confirm password!';
        }

        $pattern =
            '/^([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])(([a-z0-9-])*([a-z0-9]))+(\.([a-z0-9])([-a-z0-9_-])?([a-z0-9])+)+$/i';

        if (!preg_match($pattern, $data['email'])) {
            $this->error['email'] = 'Invalid E-Mail!';
        }

        if (!empty($data['db_prefix']) && preg_match('/[^A-Za-z0-9_]/', $data['db_prefix'])) {
            $this->error['db_prefix'] = 'DB prefix contains non-alphanumeric characters!';
        }

        if ($data['db_driver']
            && $data['db_host']
            && $data['db_user']
            && $data['db_password']
            && $data['db_name']
        ) {
            try {
                new ADB($data);
            } catch (AException $exception) {
                $this->error['warning'] = $exception->getMessage();
            }
        }

        if (!is_writable(ABC::env('DIR_CONFIG'))) {
            $this->error['warning'] =
                'Error: Could not write to config.php please check you have set the correct permissions on: '
                .ABC::env('DIR_CONFIG').' !';
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function validateRequirements()
    {
        if (version_compare(phpversion(), ABC::env('MIN_PHP_VERSION'), '<') == true) {
            $this->error['warning'] =
                'Warning: You need to use PHP '.ABC::env('MIN_PHP_VERSION').' or above for AbanteCart to work!';
        }

        if (!ini_get('file_uploads')) {
            $this->error['warning'] = 'Warning: file_uploads needs to be enabled in PHP!';
        }

        if (ini_get('session.auto_start')) {
            $this->error['warning'] = 'Warning: AbanteCart will not work with session.auto_start enabled!';
        }

        if (!extension_loaded('mysql') && !extension_loaded('mysqli') && !extension_loaded('pdo_mysql')) {
            $this->error['warning'] = 'Warning: MySQL extension needs to be loaded for AbanteCart to work!';
        }

        if (!function_exists('simplexml_load_file')) {
            $this->error['warning'] = 'Warning: SimpleXML functions needs to be available in PHP!';
        }

        if (!extension_loaded('gd')) {
            $this->error['warning'] = 'Warning: GD extension needs to be loaded for AbanteCart to work!';
        }

        if (!extension_loaded('mbstring') || !function_exists('mb_internal_encoding')) {
            $this->error['warning'] = 'Warning: MultiByte String extension needs to be loaded for AbanteCart to work!';
        }
        if (!extension_loaded('zlib')) {
            $this->error['warning'] = 'Warning: ZLIB extension needs to be loaded for AbanteCart to work!';
        }
        if (!extension_loaded('openssl')) {
            $this->error['warning'] = 'Warning: OpenSSL extension needs to be loaded for AbanteCart to work!';
        }
        if (!extension_loaded('phar')) {
            $this->error['warning'] = 'Warning: PHAR extension needs to be loaded for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('DIR_CONFIG'))) {
            $this->error['warning'] = 'Warning: Config directory needs to be writable for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('DIR_SYSTEM'))) {
            $this->error['warning'] = 'Warning: System directory and all its '
                .'children files/directories need to be writable for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('CACHE')['stores']['file']['path'])) {
            $this->error['warning'] = 'Warning: Cache directory needs to be writable for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('DIR_LOGS'))) {
            $this->error['warning'] = 'Warning: Logs directory needs to be writable for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('DIR_IMAGES'))) {
            $this->error['warning'] = 'Warning: Images directory and all its children files/directories '
                .'need to be writable for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('DIR_IMAGES').'thumbnails')) {
            if (file_exists(ABC::env('DIR_IMAGES').'thumbnails') && is_dir(ABC::env('DIR_IMAGES').'thumbnails')) {
                $this->error['warning'] =
                    'Warning: images/thumbnails directory needs to be writable for AbanteCart to work!';
            } else {
                @chmod(ABC::env('DIR_IMAGES'), 0775);
                $result = mkdir(ABC::env('DIR_IMAGES').'thumbnails', 0775, true);
                if ($result) {
                    chmod(ABC::env('DIR_IMAGES').'thumbnails', 0775);
                } else {
                    $this->error['warning'] = 'Warning: images/thumbnails does not exists!';
                }
            }
        }

        if (!is_writable(ABC::env('DIR_DOWNLOADS'))) {
            $this->error['warning'] = 'Warning: Downloads directory needs to be writable for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('DIR_APP_EXTENSIONS'))) {
            $this->error['warning'] = 'Warning: Extensions directory needs to be writable for AbanteCart to work!';
        }

        if (!is_writable(ABC::env('DIR_RESOURCES'))) {
            $this->error['warning'] = 'Warning: Resources directory needs to be writable for AbanteCart to work!';
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    public function configure($data)
    {
        if (!$data) {
            return false;
        }

        $install = new Install();
        $install->printStartTime = false;
        $install->printEndTime = false;
        $options = $data;
        $options['root_dir'] = ABC::env('DIR_ROOT');
        $options['app_dir'] = ABC::env('DIR_APP');
        $options['public_dir'] = ABC::env('DIR_PUBLIC');
        $options['cache_driver'] = 'file';
        $errors = $install->configure($options);
        if ($errors) {
            exit(implode("<br>", $errors));
        }

        //ok. let's publish assets
        $deploy = new Deploy();
        $deploy->printStartTime = false;
        $deploy->printEndTime = false;
        $ops = ['all' => 1, 'skip-caching' => 1];
        $errors = $deploy->run('all', $ops);
        if ($errors) {
            exit(implode("<br>", $errors));
        }

        return null;
    }

    public function RunSQL($data)
    {
        $install = new Install();
        $install->printStartTime = false;
        $install->printEndTime = false;
        $options = $data;
        $this->setADB($options);
        $errors = $install->runSQL($options);
        if ($errors) {
            exit(implode("<br>", $errors));
        }
    }

    /**
     * @param array $data
     *
     * @return null
     * @throws AException
     * @throws \DebugBar\DebugBarException
     */
    public function loadDemoData($data)
    {
        $install = new Install();
        $install->printStartTime = false;
        $install->printEndTime = false;
        $options = $data;
        $this->setADB($options);
        $errors = $install->loadDemoData($options);
        if ($errors) {
            exit(implode("<br>", $errors));
        }

        //clear earlier created cache by AConfig and ALanguage classes in previous step
        $cache = new AbcCache('file');
        $cache->flush();

        return null;
    }

    public function setADB($options)
    {
        if ($options) {
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
            return true;
        } else {
            if (is_file(ABC::env('DIR_CONFIG').'enabled.config.php')) {
                //load environment form config-file
                new ABC();
                return true;
            }
        }
        return false;
    }

}
