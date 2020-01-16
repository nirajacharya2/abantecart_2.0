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

namespace abc\core\lib;

use abc\core\ABC;
use abc\commands\Publish;
use abc\core\engine\ExtensionUtils;
use abc\core\helper\AHelperUtils;
use abc\core\engine\Registry;
use Exception;
use H;

/**
 * @property \abc\core\engine\ExtensionsApi      $extensions
 * @property ADB                                 $db
 * @property \abc\core\lib\AbcCache              $cache
 * @property AConfig                             $config
 * @property \abc\core\engine\ALanguage          $language
 * @property \abc\core\engine\ALoader            $load
 * @property \abc\models\admin\ModelToolUpdater  $model_tool_updater
 * @property \abc\core\engine\AHtml              $html
 * @property AUser                               $user
 * @property ALog                                $log
 * @property AMessage                            $messages
 * @property \abc\models\admin\ModelSettingStore $model_setting_store
 * */
class AExtensionManager
{
    /**
     * @var \abc\core\engine\Registry
     */
    protected $registry;
    /**
     * @var array
     */
    public $errors = [];
    /**
     * @var array extension type list that manager can to install-uninstall
     */
    protected $extension_types = ['extension', 'extensions', 'payment', 'shipping', 'template'];

    public function __construct()
    {
        if (!ABC::env('IS_ADMIN')) { // forbid for non admin calls
            throw new AException (
                'Error: permission denied to access extension manager',
                AC_ERR_LOAD
            );
        }
        $this->registry = Registry::getInstance();
    }

    public function __get($key)
    {
        return $this->registry->get($key);
    }

    public function __set($key, $value)
    {
        $this->registry->set($key, $value);
    }

    public function getInstalled($type = '')
    {
        return $this->extensions->getInstalled($type);
    }

    public function getExtensionInfo($key = '')
    {
        return $this->extensions->getExtensionInfo($key);
    }

    public function getExtensionsList($data = [], $mode = '')
    {
        return $this->extensions->getExtensionsList($data, $mode);
    }

    /**
     * @param array $data
     *
     * @return int extension_id
     * @throws Exception
     */
    public function add($data)
    {
        if (is_array($data)) {
            // check collision
            $data['type'] = $data['type'] == 'extension' ? 'extensions' : $data['type'];
            $type = ($data['type'] ? $data['type'] : 'extensions');
            $key = $data['key'];
            $status = $data['status'];
            $priority = $data['priority'];
            $version = $data['version'];
            $license_key = $data['license_key'];
            $category = $data['category'];

        } else {
            $key = $data;
            $type = 'extensions';
            $category = $status = $priority = $version = $license_key = '';
        }
        if (!$key) {
            return false;
        }
        $sql = "SELECT extension_id 
                FROM ".$this->db->table_name("extensions")." 
                WHERE `key`= '".$this->db->escape($key)."'";
        $res = $this->db->query($sql);
        if ($res->num_rows) {
            return $res->row['extension_id'];
        }

        $this->db->query("INSERT INTO ".$this->db->table_name("extensions")." 
                         SET `type` = '".$this->db->escape($type)."',
                             `key` = '".$this->db->escape($key)."',
                             `category` = '".$this->db->escape($category)."',
                             `status` = '".$this->db->escape($status)."',
                             `priority` = '".$this->db->escape($priority)."',
                             `version` = '".$this->db->escape($version)."',
                             `license_key` = '".$this->db->escape($license_key)."',
                             `date_added` = NOW()");

        $this->cache->flush('extensions');

        return $this->db->getLastId();
    }

    /**
     * Function gets parent extensions id and text id from extension dependencies table
     *
     * @param string $extension_txt_id
     *
     * @return array
     * @throws Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getParentsExtensionTextId($extension_txt_id)
    {
        $info = $this->extensions->getExtensionInfo($extension_txt_id);
        $extension_id = (int)$info['extension_id'];
        if (!$extension_id) {
            return [];
        }

        $result = $this->db->query(
            "SELECT e.key, ed.extension_parent_id, e.status
            FROM ".$this->db->table_name("extension_dependencies")." ed
            LEFT JOIN ".$this->db->table_name("extensions")." e 
                ON ed.extension_parent_id = e.extension_id
            WHERE ed.extension_id = '".$extension_id."'");

        return $result->rows;
    }

    /**
     * @param string $parent_extension_txt_id
     *
     * @return array
     * @throws Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getChildrenExtensions($parent_extension_txt_id)
    {
        $info = $this->extensions->getExtensionInfo($parent_extension_txt_id);
        $extension_id = (int)$info['extension_id'];
        if (!$extension_id) {
            return [];
        }

        $result = $this->db->query(
            "SELECT e.*
            FROM ".$this->db->table_name("extension_dependencies")." ed
            LEFT JOIN ".$this->db->table_name("extensions")." e 
                ON ed.extension_id = e.extension_id
            WHERE ed.extension_parent_id = '".$extension_id."'"
        );

        return $result->rows;
    }

    /**
     * @param string $extension_txt_id
     * @param string $extension_parent_txt_id
     *
     * @return bool
     * @throws Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function addDependant($extension_txt_id, $extension_parent_txt_id)
    {
        $info = $this->extensions->getExtensionInfo($extension_parent_txt_id);
        $extension_parent_id = (int)$info['extension_id'];
        $info = $this->extensions->getExtensionInfo($extension_txt_id);
        $extension_id = (int)$info['extension_id'];
        if (!$extension_id || !$extension_parent_id) {
            return false;
        }

        $result = $this->db->query("SELECT *
                                    FROM ".$this->db->table_name("extension_dependencies")." 
                                    WHERE extension_id = '".$extension_id."' 
                                        AND extension_parent_id = '".$extension_parent_id."'");
        if (!$result->num_rows) {
            $sql = "INSERT INTO ".$this->db->table_name("extension_dependencies")." 
                        (extension_id, extension_parent_id )
                    VALUES ('".$extension_id."', '".$extension_parent_id."')";
            $this->db->query($sql);
        }
        $this->cache->flush('extensions');
        return true;
    }

    /**
     * function delete extension dependants from table by given id's
     *
     * @param string $extension_txt_id
     * @param string $extension_parent_txt_id
     *
     * @return bool
     * @throws Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteDependant($extension_txt_id = '', $extension_parent_txt_id = '')
    {
        $info = $this->extensions->getExtensionInfo($extension_parent_txt_id);
        $extension_parent_id = $info ? (int)$info['extension_id'] : 0;

        $info = $this->extensions->getExtensionInfo($extension_txt_id);
        $extension_id = $info ? (int)$info['extension_id'] : 0;

        if (!$extension_id && !$extension_parent_id) {
            return false;
        }

        $sql = "DELETE FROM ".$this->db->table_name("extension_dependencies")." 
                WHERE ";
        $where = [];
        if ($extension_id) {
            $where[] = "extension_id = '".$extension_id."'";
        }
        if ($extension_parent_id) {
            $where[] = "extension_parent_id = '".$extension_parent_id."'";
        }
        $sql .= implode(' AND ', $where);
        $this->db->query($sql);

        $this->cache->flush('extensions');

        return true;
    }

    /**
     * Save extension settings into database
     *
     * @param string $extension_txt_id
     * @param array $data
     *
     * @return bool
     * @throws AException
     * @throws \ReflectionException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function editSetting($extension_txt_id, $data)
    {

        if (empty($data)) {
            return false;
        }
        if (empty($extension_txt_id)) {
            $error =
                new AError ("Error: Can't edit setting because field \"extension_txt_id\" is empty. Settings array: "
                    .implode(",", array_keys($data)));
            $error->toLog()->toDebug();
            return false;
        }
        // parameters that placed in extension table
        $masks = ['status', 'version', 'date_installed', 'priority', 'license_key'];

        $keys = array_keys($data);
        unset($keys['store_id']);
        // check if settings required and it is not status
        $ext = new ExtensionUtils($extension_txt_id, (int)$data['store_id']);
        if (isset($data['one_field']) && !isset($data[$extension_txt_id."_status"])) {
            $validate = $ext->validateSettings($data);
            if (!$validate['result']) { // check is all required settings are set
                if (!isset($validate['errors'])) {
                    $this->errors[] = "Can't save setting because value is empty. ";
                } else {
                    $this->load->language($extension_txt_id.'/'.$extension_txt_id);
                    foreach ($validate['errors'] as $field_id => $error_text) {
                        $this->errors[] =
                            $error_text ? $error_text : $this->language->get($field_id.'_validation_error');
                    }
                }

                return false;
            }
        }
        //remove sign to prevent writing into settings table
        unset($data['one_field']);

        $this->db->query("DELETE FROM ".$this->db->table_name("settings")." 
                          WHERE `group` = '".$this->db->escape($extension_txt_id)."'
                                AND `key` IN ('".implode("', '", $keys)."')
                                AND `store_id` = '".(int)$data['store_id']."' ");

        foreach ($data as $key => $value) {
            $setting_name = str_replace($extension_txt_id."_", '', $key);
            //check if setting is multi-value (array) and save serialized value.
            if (is_array($value)) {
                //validate values in array. If setting is array of all members = 0 save only single value of 0
                //This is to match standard post format in regular form submit
                foreach ($value as &$v) {
                    //remove empty value from multiselectbox with empty set of options
                    $v = $v == "''" ? null : $v;
                }
                $concat = implode('', $value);
                if (preg_match('/[^0]/', $concat)) {
                    $value = serialize($value);
                } else {
                    $value = 0;
                }
            }
            // status check
            if ($setting_name == 'status') {
                //when try to enable extension
                if ($value == 1) { // check is parent extension enabled
                    $validate = $ext->validateSettings($data); // check is all required settings are set and valid
                    if (!$validate['result']) {
                        $value = 0; // disable extension
                        if (!isset($validate['errors'])) {
                            $error = "Cannot enable extension \"".$extension_txt_id
                                ."\". Please fill all required fields on settings edit page. ";
                            $this->errors[] = $error;
                            $error = new AError ($error);
                            $error->toLog()->toDebug();
                        } else {
                            $this->load->language($extension_txt_id.'/'.$extension_txt_id);
                            foreach ($validate['errors'] as $field_id => $error_text) {
                                $error =
                                    $error_text ? $error_text : $this->language->get($field_id.'_validation_error');
                                $this->errors[] = $error;
                                $error = new AError ($error);
                                $error->toLog()->toDebug();
                            }
                        }
                    } else {
                        // if all fine with required fields - check children
                        $parents = $this->getParentsExtensionTextId($extension_txt_id);
                        $enabled = $this->extensions->getEnabledExtensions();
                        foreach ($parents as $parent) {
                            if (!in_array($parent['key'], $enabled)) {
                                $error =
                                    "Cannot enable extension \"".$extension_txt_id."\". It's depends on extension \""
                                    .$parent['key']."\" which not enabled. ";
                                $this->errors[] = $error;
                                $error = new AError ($error);
                                $error->toLog()->toDebug();
                                //prevents enabling
                                $value = 0;
                                break;
                            }
                        }
                    }

                } else { // When try to disable disable dependants too
                    if ($this->isExtensionInstalled($extension_txt_id)) {
                        $children_keys = [];
                        $children = $this->getChildrenExtensions($extension_txt_id);

                        foreach ($children as $child) {
                            if ($this->config->get($child['key']."_status") == 1) {
                                $children_keys[] = $this->db->escape($child['key']);
                            }
                        }
                        if ($children_keys) {
                            foreach ($children_keys as $child) {
                                $sql = "UPDATE ".$this->db->table_name("settings")." 
                                        SET `value` = 0
                                        WHERE `group` = '".$child."'
                                            AND `key`= '".$child."_status'";
                                $this->db->query($sql);
                            }
                            $sql = "UPDATE ".$this->db->table_name("extensions")." 
                                    SET `".$setting_name."` = '".$this->db->escape($value)."'
                                    WHERE  `key` IN ('".implode("','", $children_keys)."')";
                            $this->db->query($sql);
                        }
                    }
                }
            }

            //Special case.
            //Check that we have single mode RL with ID
            if (H::has_value($data[$key."_resource_id"]) && !H::has_value($value)) {
                //save ID if resource path is missing
                $value = $data[$key."_resource_id"];
            }
            //skip saving ???

            // now re-insert settings
            $this->db->query("INSERT INTO ".$this->db->table_name("settings")." 
                              SET `store_id` = '".(int)$data['store_id']."',
                                  `group` = '".$this->db->escape($extension_txt_id)."',
                                  `key` = '".$this->db->escape($key)."',
                                  `value` = '".$this->db->escape($value)."'");
            if (in_array($setting_name, $masks)) {
                $sql = "UPDATE ".$this->db->table_name("extensions")." 
                        SET `".$setting_name."` = '".$this->db->escape($value)."'
                        WHERE  `key` = '".$this->db->escape($extension_txt_id)."'";
                $this->db->query($sql);
            }

            if($setting_name == 'status') {
                //update enabled.config.php if presents
                $enabled_filename = ABC::env('DIR_APP_EXTENSIONS')
                                    .$extension_txt_id.DS
                                    .'config'.DS
                                    .'enabled.config.php';
                if (is_file($enabled_filename)) {
                    $stage_name = $value == 1 ? ABC::$stage_name : '';
                    $content = '<?php return \''.$stage_name.'\';';
                    file_put_contents($enabled_filename, $content);
                }
            }
        }
        // update date of changes in extension list
        $sql = "UPDATE ".$this->db->table_name("extensions")." 
                SET `date_modified` = NOW()
                WHERE  `key` = '".$this->db->escape($extension_txt_id)."'";
        $this->db->query($sql);


        $this->cache->flush('admin_menu');
        $this->cache->flush('settings');
        $this->cache->flush('extensions');

        return true;
    }

    /**
     * method deletes all settings of extension with language definitions
     *
     * @param string $group - extension text id
     *
     * @throws Exception
     */
    public function deleteSetting($group)
    {
        $this->db->query("DELETE FROM ".$this->db->table_name("settings")." WHERE `group` = '".$this->db->escape($group)
            ."';");
        $this->db->query("DELETE FROM ".$this->db->table_name("language_definitions")." WHERE `block` = '"
            .$this->db->escape($group)."_".$this->db->escape($group)."';");
        $this->cache->flush('settings');
        $this->cache->flush('extensions');
        $this->cache->flush('localization');
    }

    /**
     * extension install actions, db queries, copying files etc
     *
     * @param string $name
     * @param \DomNode| \SimpleXMLElement $config
     *
     * @return bool
     * @throws AException
     * @throws \ReflectionException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function install($name, $config)
    {

        $ext = new ExtensionUtils($name);
        // gets extension_id for install.php
        $extension_info = $this->getExtensionsList(['search' => $name]);
        if(!$extension_info->row){
            throw new Exception('Extension '.$name .' not found!');
        }

        $validate = $this->validateCoreVersion($extension_info->row['key'], $config);
        $this->errors += $ext->getError();

        if (!$validate) {
            $error = new AError (implode("\n", $this->errors));
            $error->toLog()->toDebug();
            return false;
        }

        //install default settings
        $default_settings = $ext->getDefaultSettings();
        $settings = [
            $name.'_status'         => 0,
            $name.'_layout'         => (string)$config->layout,
            $name.'_priority'       => (string)$config->priority,
            $name.'_date_installed' => date("Y-m-d H:i:s", time()),
        ];

        $settings = array_merge($settings, $default_settings);

        // add dependencies into database for required extensions only
        if (isset($config->dependencies->item)) {
            foreach ($config->dependencies->item as $item) {
                if ((boolean)$item['required']) {
                    $this->addDependant($name, (string)$item);
                }
            }
        }

        // running php install script if it exists
        if (isset($config->install->trigger)) {
            $file = ABC::env('DIR_APP_EXTENSIONS')
                    .str_replace('../', '', $name)
                    .'/'
                    .(string)$config->install->trigger;

            if (is_file($file)) {
                try {
                    include($file);
                } catch (\Exception $e) {
                    $this->errors[] = $e->getMessage();
                    return false;
                }
            }
        }

        //update composer if needed
        //In this process main composer.json will grab composer.json files inside abc/extensions subdirectories
        //to install all 3d-party code into abc/vendor folder

        if(is_file(ABC::env('DIR_APP_EXTENSIONS').DS.$name.DS.'composer.json')){
            $composer_phar = ABC::env('DIR_SYSTEM').DS.'temp'.DS.'composer.phar';
            if(!is_file($composer_phar) && !copy( 'https://getcomposer.org/composer.phar', $composer_phar)){
                exit('Error! Cannot to download composer.phar file into '.dirname($composer_phar)
                    . ' directory from https://getcomposer.org/composer.phar !'."\n"
                    .'Please download it manually to proceed installation'."\n");
            }
            $command = 'php '.$composer_phar.' update --no-interaction --ansi';
            system($command, $exit_code);
            if($exit_code){
                exit('Error during executing of command'."\n".$command."\n");
            }
        }

        //publish assets
        require_once ABC::env('DIR_APP').'commands'.DS.'publish.php';
        $publish = new Publish();
        $publish->run('extensions', ['extension' => $name]);

        // refresh data about updates
        $this->load->model('tool/updater');
        $this->model_tool_updater->check4updates();

        //save default settings for all stores
        $this->load->model('setting/store');
        $stores = $this->model_setting_store->getStores();
        foreach ($stores as $store) {
            $settings['store_id'] = $store['store_id'];
            $this->editSetting($name, $settings);
        }

        //write info about install into install log
        $install_upgrade_history = new ADataset('install_upgrade_history', 'admin');
        $install_upgrade_history->addRows([
            'date_added'  => date("Y-m-d H:i:s", time()),
            'name'        => $name,
            'version'     => $settings[$name.'_version'],
            'backup_file' => '',
            'backup_date' => '',
            'type'        => 'install',
            'user'        => (is_object($this->user) ? $this->user->getUsername() : 'php-cli'),
        ]);

        return true;
    }

    /**
     * @param string $name
     * @param \DOMNode | \SimpleXMLElement $config
     *
     * @return bool|null
     * @throws AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function uninstall($name, $config)
    {
        if (!$name) {
            return false;
        }

        // check dependencies
        $validate = $this->checkDependantsBeforeUninstall($name);
        if (!$validate) {
            return false;
        }

        //write info about install into install log
        $info = $this->extensions->getExtensionInfo($name);

        if ($info['type'] == 'payment' && $this->config->get($name.'_status')) {
            $this->load->language('extension/extensions');
            $this->errors = [$this->language->get('error_payment_uninstall')];
            return false;
        }

        // running php uninstall script if it exists
        if (isset($config->uninstall->trigger)) {
            $file = ABC::env('DIR_APP_EXTENSIONS')
                .$name
                .DS
                .(string)$config->uninstall->trigger;

            if (is_file($file)) {
                try {
                    include($file);
                } catch (\Exception $e) {
                    $this->errors[] = $e->getMessage();
                    return false;
                }
            }
        }

        //set status to off
        $this->editSetting($name, ['status' => 0]);
        //uninstall settings
        $this->deleteSetting($name);

        $install_upgrade_history = new ADataset('install_upgrade_history', 'admin');
        $install_upgrade_history->addRows([
            'date_added'  => date("Y-m-d H:i:s", time()),
            'name'        => $name,
            'version'     => $info['version'],
            'backup_file' => '',
            'backup_date' => '',
            'type'        => 'uninstall',
            'user'        => (is_object($this->user) ? $this->user->getUsername() : 'php-cli'),
        ]);

        return true;
    }

    /**
     * @param string $extension_txt_id
     *
     * @return bool
     * @throws AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function delete($extension_txt_id)
    {
        if (!trim($extension_txt_id)) {
            $this->log->write('Error! Abantecart tried to delete by empty extension_txt_id');
            return false;
        }

        $info = $this->extensions->getExtensionInfo($extension_txt_id);
        $install_upgrade_history = new ADataset('install_upgrade_history', 'admin');
        $install_upgrade_history->addRows([
            'date_added'  => date("Y-m-d H:i:s", time()),
            'name'        => $extension_txt_id,
            'version'     => $info['version'],
            'backup_file' => '',
            'backup_date' => '',
            'type'        => 'delete',
            'user'        => (is_object($this->user) ? $this->user->getUsername() : 'php-cli'),
        ]);
        $this->db->query("DELETE FROM ".$this->db->table_name("extensions")." 
                           WHERE `type` = '".$info['type']."' 
                                AND `key` = '".$this->db->escape($extension_txt_id)."'");
        $this->deleteDependant($extension_txt_id);

        $pmanager = new APackageManager([]);
        $result = $pmanager->removeDir(ABC::env('DIR_APP_EXTENSIONS').$extension_txt_id);
        if (!$result) {
            $this->errors[] =
                "Error: Cannot to delete file or directory: '".ABC::env('DIR_APP_EXTENSIONS').$extension_txt_id."'."
                ."No file permissions, change permissions to 777 with your FTP access";
            $this->errors += $pmanager->errors;
        }

        //remove assets
        $extension_public_dir = ABC::env('DIR_PUBLIC').ABC::env('DIRNAME_EXTENSIONS').$extension_txt_id;
        if (is_dir($extension_public_dir)) {
            $pmanager = new APackageManager([]);
            $pmanager->removeDir($extension_public_dir);
        }

        // refresh data about updates
        $this->load->model('tool/updater');
        $this->model_tool_updater->check4updates();
        $this->cache->flush('extensions');
        return true;
    }

    /**
     * @param string $extension_txt_id
     *
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function validate($extension_txt_id)
    {
        $result = $this->isExtensionInstalled($extension_txt_id);
        if (!$result) {
            return false;
        }
        // get config.xml
        $config = H::getExtensionConfigXml($extension_txt_id);
        $result = $this->validateCoreVersion($extension_txt_id, $config);
        if (!$result) {
            return false;
        }
        $result = $this->validatePhpModules($extension_txt_id, $config);
        if (!$result) {
            return false;
        }
        $result = $this->validateDependencies($extension_txt_id, $config);
        if (!$result) {
            return false;
        }
        $enabled_filename = ABC::env('DIR_APP_EXTENSIONS')
                                            .$extension_txt_id.DS
                                            .'config'.DS
                                            .'enabled.config.php';
        if (is_file($enabled_filename) && !is_writable($enabled_filename)) {
            $this->errors[] = 'File '
                .$enabled_filename
                .' is not writable for php. Please change permissions ang try again.';
        }

        return true;
    }

    /**
     *  is dependencies present
     *
     * @param string $extension_txt_id
     * @param \DOMNode | \SimpleXMLElement $config
     *
     * @return bool
     * @throws Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function validateDependencies($extension_txt_id, $config)
    {
        $extensions = $this->extensions->getEnabledExtensions();
        $all_extensions = $this->extensions->getExtensionsList();
        $versions = [];
        foreach ($all_extensions->rows as $ext) {
            $versions[$ext['key']] = $ext['version'];
        }
        if (!isset($config->dependencies->item)) {
            return true;
        }
        foreach ($config->dependencies->item as $item) {
            $required = (boolean)$item['required'];
            $version = (string)$item['version'];
            $prior_version = (string)$item['prior_version'];

            $item = (string)$item;
            // check existing of required
            if ($required && !in_array($item, $extensions)) {
                $this->errors[] =
                    sprintf('%s extension cannot be installed: %s extension required and must be installed and enabled!',
                        $extension_txt_id, $item);
            }
            // if extension installed - check version that need
            if ($version) {
                if ($required
                    && (!H::versionCompare($version, $versions[$item], '>=')
                        || !H::versionCompare($prior_version, $versions[$item], '<='))) {
                    $this->errors[] =
                        sprintf('%s extension cannot be installed: %s extension versions '.$prior_version.' - '.$version
                            .' are required', $extension_txt_id, $item);
                }
            }
            if (sizeof($this->errors) > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     *  is dependants installed?
     *
     * @param string $extension_txt_id
     *
     * @return bool
     * @throws Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function checkDependantsBeforeUninstall($extension_txt_id)
    {
        $extensions = $this->extensions->getInstalled('exts');
        foreach ($extensions as $extension) {
            if ($extension == $extension_txt_id) {
                continue;
            }
            /**
             * @var \DOMNode $config
             */
            $config = H::getExtensionConfigXml($extension);
            if (!isset($config->dependencies->item)) {
                continue;
            }
            foreach ($config->dependencies->item as $item) {
                $required = (boolean)$item['required'];
                $item = (string)$item;
                if ($item == $extension_txt_id && $required) {
                    $this->errors[] =
                        sprintf('"%s" extension cannot be uninstalled: "%s" extension depends on it. Please uninstall it first.',
                            $extension_txt_id, $extension);

                    return false;
                }
            }
        }

        return true;
    }

    /**
     *  is extension already installed ( extension upgrade )
     *
     * @param string $extension_txt_id
     *
     * @return bool
     */
    public function isExtensionInstalled($extension_txt_id)
    {
        $installed = $this->config->get($extension_txt_id.'_status');
        if ($installed !== null) {
            return false;
        }
        return true;
    }

    /**
     *  is extension support current core version
     *
     * @param string                       $extension_txt_id
     * @param \DOMNode | \SimpleXMLElement $config
     *
     * @return bool
     */
    public function validateCoreVersion(string $extension_txt_id, \DOMNode $config)
    {
        $this->errors = [];
        if (!isset($config->cartversions->item)) {
            $this->errors[] = 'Error: config file of extension does not contain any information'
                            .' about versions of AbanteCart where it can be run.';
            return false;
        }
        $cart_versions = [];
        foreach ($config->cartversions->item as $item) {
            $version = (string)$item;
            $cart_versions[] = $version;
        }
        // check is cart version presents on extension cart version list
        foreach ($cart_versions as $version) {
            $result = H::versionCompare(ABC::env('VERSION'), $version, '>=')
                && version_compare($version, '2.0.0', '>');
            if ($result) {
                return true;
            }
        }
        // if not - seek cart earlier version then current cart version in the list
        foreach ($cart_versions as $version) {
            $result = (H::versionCompare($version, ABC::env('VERSION'), '<=')
                && version_compare($version, '2.0.0', '<='));
            if ($result) {
                $error_text = 'Extension "%s" written for earlier version of Abantecart (v.%s) lower that you have. ';
                $error_text .= 'Probably all will be OK.';
                $error_text = sprintf($error_text, $extension_txt_id, implode(', ', $cart_versions));
                $this->errors[] = $error_text;
                return true;
            }
        }
        $error_text =
            '%s extension cannot be installed. AbanteCart version incompatibility. Extension designed for version(s) %s.';
        $this->errors[] = sprintf($error_text, $extension_txt_id, implode(', ', $cart_versions));
        return false;
    }


    /**
     *  is hosting support all php modules used by extension
     */
    /**
     * @param string            $extension_txt_id
     * @param \SimpleXMLElement $config
     *
     * @return bool
     */
    public function validatePhpModules($extension_txt_id, $config)
    {
        if (!isset($config->phpmodules->item)) {
            return true;
        }
        foreach ($config->phpmodules->item as $item) {
            $item = (string)$item;
            if (!extension_loaded($item)) {
                $this->errors[] =
                    sprintf('%s extension cannot be installed: %s php module required', $extension_txt_id, $item);
                return false;
            }
        }
        return true;
    }

}