<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2018 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\core\lib;

use abc\core\ABC;
use abc\core\engine\Registry;
use H;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;


/**
 * @property  AExtensionManager $extension_manager
 */
class APackageManager
{
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var ADB
     */
    protected $db;
    /**
     * @var \abc\core\engine\ExtensionsApi
     */
    protected $extensions;
    /**
     * @var AExtensionManager
     */
    protected $extension_manager;
    /**
     * @var \abc\core\lib\ALanguageManager
     */
    protected $language;
    public $errors = [];
    public $message_log = [];
    /**
     * size of data in bytes
     *
     * @var int
     */
    public $dataSize = 0;
    public $package_info = [];
    protected $package_config;

    public function __construct($package_info)
    {
        if (!ABC::env('IS_ADMIN')) { // forbid for non admin calls
            throw new AException ('Error: permission denied to access package manager', AC_ERR_LOAD);
        }
        /**
         * @var Registry
         */
        $this->registry = Registry::getInstance();
        $this->db = $this->registry->get('db');
        $this->extensions = $this->registry->get('extensions');
        $this->extension_manager = new AExtensionManager();
        $this->language = $this->registry->get('language');
        $this->package_info =& $package_info;
    }

    public function getPackageInfo()
    {
        return (array)$this->package_info;
    }

    /**
     * @param string $url
     *
     * @return bool
     * @throws AException
     */
    public function downloadPackageByURL($url)
    {
        if (!$url || parse_url($url) === false) {
            return false;
        }

        $connect = new AConnect();
        $headers = $this->getRemoteFileHeaders($url);

        if (!in_array($headers['Content-Type'], ['application/zip', 'application/x-gzip'])) {
            $this->errors[] = 'Unknown archive-type. Waiting for zip or tar.gz archive!';
            return false;
        }

        $package_name = str_replace("attachment; filename=", "", $headers['Content-Disposition']);
        $package_name = str_replace(['"', ';'], '', $package_name);
        if (!$package_name) {
            $package_name = parse_url($url);
            if (pathinfo($package_name['path'], PATHINFO_EXTENSION)) {
                $package_name = pathinfo($package_name['path'], PATHINFO_BASENAME);
            } else {
                $package_name = '';
            }
        }
        //if still don't know what the name of file
        if (!$package_name) {
            if ($headers['Content-Type'] == 'application/zip') {
                $package_name = 'package_'.time().".zip";
            } else {
                $package_name = 'package_'.time().".tar.gz";
            }
        }

        $result = $connect->getData($url, null, false, $this->getTempDir().$package_name);
        if (!$result) {
            $this->errors += $connect->errors;
            return false;
        }
        $this->package_info['package_name'] = $package_name;
        return true;
    }

    /**
     * @param string $url
     * @param boolean $save
     * @param string $new_file_name
     *
     * @return boolean|array
     * @throws AException
     */
    public function getRemoteFile($url, $save = true, $new_file_name = '')
    {
        if (!$url) {
            return false;
        }
        $file = new AConnect();
        if ($save) {
            $result = $file->getFile($url, $new_file_name); //download
        } else {
            $result = $file->getResponse($url); // just get data
        }
        if (!$result) {
            $this->errors[] = $file->errors;

            return false;
        }

        return $result;
    }

    /**
     * @param string $url
     *
     * @return bool|string
     */
    public function getRemoteFileHeaders($url)
    {
        if (!$url) {
            return false;
        }
        $file = new AConnect();
        $file->connect_method =
            'socket'; //use this method because curl returns no header 'Content-Disposition' with file name
        $url = $url.(!is_int(strpos($url, '?')) ? '?file_size=1' : '&file_size=1');
        $result = $file->getDataHeaders($url);
        if (!$result) {
            $this->errors[] = $file->errors;

            return false;
        }

        return $result;
    }

    /**
     * @param string $archive_filename
     * @param string $dest_dir
     *
     * @return boolean
     * @throws \ReflectionException
     */
    public function unpack($archive_filename, $dest_dir)
    {
        if (!file_exists($archive_filename)) {
            $error_text = 'Error: Cannot unpack file "'.$archive_filename.'" because it does not exists.';
            $this->errors[] = $error_text;
            $error = new AError($error_text);
            $error->toLog()->toDebug();

            return false;
        }
        if (!file_exists($dest_dir) || !is_dir($dest_dir)) {
            $error_text = 'Error: Cannot unpack file "'.$archive_filename.'" because destination directory "'.$dest_dir
                .'" does not exists.';
            $this->errors[] = $error_text;
            $error = new AError($error_text);
            $error->toLog()->toDebug();
            return false;
        }
        if (!is_writable($dest_dir)) {
            $error_text = 'Error: Cannot unpack file "'.$archive_filename.'" because destination directory "'.$dest_dir
                .'" have no write permission.';
            $this->errors[] = $error_text;
            $error = new AError($error_text);
            $error->toLog()->toDebug();
            return false;
        }

        //remove destination folder first. run pathinfo twice for tar.gz. files
        $package_dir = $dest_dir.pathinfo(pathinfo($archive_filename, PATHINFO_FILENAME), PATHINFO_FILENAME).DS;
        $package_subdir = '';
        $this->removeDir($package_dir);
        unset($this->package_info['package_dir']);

        $unpack_result = H::extractArchive($archive_filename, $package_dir);
        if ($unpack_result) {
            $this->chmod_R($dest_dir.$this->package_info['tmp_dir'], 0775, 0775);
            $dirs = glob($package_dir.'*', GLOB_ONLYDIR);
            $package_subdir = $dirs ? $dirs[0].DS : '';
        }
        if ($package_subdir) {
            $this->package_info['package_dir'] = $package_subdir;
            //add package-info
            $this->extractPackageInfo();
            return true;
        }
        return false;
    }

    public function extractPackageInfo()
    {
        /**
         * @var \SimpleXMLElement|\DOMDocument $config
         */
        $config = @simplexml_load_file($this->package_info['package_dir'].'package.xml');
        if (!$config) {
            $this->errors[] = 'Cannot to read file '.$this->package_info['package_dir'].'package.xml';
            return false;
        }
        $this->package_config = $config;
        $this->package_info['package_id'] = (string)$config->id;
        $this->package_info['package_type'] = (string)$config->type;
        $this->package_info['package_priority'] = (string)$config->priority;
        $this->package_info['package_version'] = (string)$config->version;
        $this->package_info['package_content'] = [];
        if ((string)$config->package_content->extensions) {
            $this->package_info['package_content']['extensions'] = [];
            foreach ($config->package_content->extensions->extension as $item) {
                if ((string)$item) {
                    $this->package_info['package_content']['extensions'][] = (string)$item;
                }
            }
            $this->package_info['package_content']['total'] =
                sizeof($this->package_info['package_content']['extensions']);
        }

        if ((string)$config->package_content->core) {
            $this->package_info['package_content']['core'] = [];
            foreach ($config->package_content->core->files->file as $item) {
                if ((string)$item) {
                    $this->package_info['package_content']['core'][] = (string)$item;
                }
            }
        }
        return true;
    }

    public function validateDestination()
    {
        if (!is_dir($this->package_info['package_dir'])) {
            $this->errors[] = 'Temporary directory of the package not found.';
            return false;
        }

        $package_dirs = $this->getDestinationDirectories();
        if (!$package_dirs) {
            $this->errors[] = 'No directories in the package!';
            return false;
        }
        $errors = [];
        foreach ($package_dirs as $dir) {
            $dir_path = '';
            if (substr($dir, 0, 3) == 'abc') {
                $dir_path = ABC::env('DIR_APP').substr($dir, 4);
                $rel_directory = ABC::env('DIR_APP');
            }
            if (substr($dir, 0, 6) == 'public') {
                $dir_path = ABC::env('DIR_PUBLIC').substr($dir, 7);
                $rel_directory = ABC::env('DIR_PUBLIC');
            }
            if (!$dir_path) {
                continue;
            }
            //try to change permissions
            if (is_dir($dir_path) && !is_writable($dir_path)) {
                @chmod($dir_path, 0775);
            }

            //create directories inside core only
            if (!is_dir($dir_path) && !is_int(strpos($dir_path, ABC::env('DIR_APP_EXTENSIONS')))) {
                @mkdir($dir_path, 0775, true);
            }
            if ((!is_dir($dir_path) || !is_writable($dir_path))
                && !is_int(strpos($dir_path, ABC::env('DIR_APP_EXTENSIONS')))) {
                $errors[] = $dir_path;
            } elseif (!is_writable(ABC::env('DIR_APP_EXTENSIONS'))) {
                $errors['dir_extensions'] = ABC::env('DIR_APP_EXTENSIONS');
            } //if we can write into directory - check files if it
            else {
                $files = glob($this->package_info['package_dir']."code".DS.$dir.'*');
                foreach ($files as $file) {
                    if (!is_file($file)) {
                        continue;
                    }
                    //ok compound filename in destination directory and check if is exists
                    $dest_filename = str_replace(
                        $this->package_info['package_dir']."code".DS.basename($rel_directory).DS,
                        $rel_directory,
                        $file
                    );
                    if (file_exists($dest_filename)) {
                        @chmod($dest_filename, 0644);
                        if (!is_writable($dest_filename)) {
                            $errors[] = $dest_filename;
                        }
                    }
                }
            }
        }

        $version_file = ABC::env('DIR_CORE').'init'.DS.'version.php';

        if ($this->isCorePackage() && !is_writable($version_file)) {
            $errors[] = $version_file;
        }

        if ($errors) {
            $errors = array_unique($errors);
            $this->errors += $errors;
            return false;
        }
        return true;
    }

    /**
     * Function make backup and move it into admin/system/backup/directory
     *
     * @param string $ext_txt_id
     *
     * @return bool
     * @throws AException
     * @throws \DebugBar\DebugBarException
     * @throws \ReflectionException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function backupPreviousExtension($ext_txt_id)
    {
        $ext_txt_id = (string)$ext_txt_id;
        if (!$ext_txt_id) {
            return false;
        }
        $ext_dir_path = ABC::env('DIR_APP_EXTENSIONS').$ext_txt_id.DS;
        //if directory does not exists thinks that's ok
        if (!is_dir($ext_dir_path)) {
            return true;
        }

        $backup = new ABackup($ext_txt_id.'_'.date('Y-m-d-H-i-s'));
        if ($backup->error) {
            $this->errors += $backup->error;
            return false;
        }
        $backup_dirname = $backup->getBackupName();
        if ($backup_dirname) {

            if (!$backup->backupDirectory($ext_dir_path, true)) {
                $this->errors += $backup->error;
                return false;
            }

            if (!$backup->dumpDatabase()) {
                return false;
            }
            if (!$backup->archive(ABC::env('DIR_BACKUP').$backup_dirname.'.tar.gz', ABC::env('DIR_BACKUP'),
                $backup_dirname)) {
                return false;
            }
        } else {
            return false;
        }

        $info = $this->extensions->getExtensionInfo($ext_txt_id);

        $install_upgrade_history = new ADataset('install_upgrade_history', 'admin');
        $install_upgrade_history->addRows([
            'date_added'  => date("Y-m-d H:i:s", time()),
            'name'        => $ext_txt_id,
            'version'     => $info['version'],
            'backup_file' => $backup_dirname.'.tar.gz',
            'backup_date' => date("Y-m-d H:i:s", time()),
            'type'        => 'backup',
            'user'        => (is_object($this->registry->get('user')) ? $this->registry->get('user')
                ->getUsername() : 'php-cli'),
        ]);

        //delete previous version
        $this->removeDir($ext_dir_path);
        return true;
    }

    /**
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function installPackageExtensions()
    {
        if (!sizeof($this->package_info['package_content']['extensions'])) {
            return;
        }
        $all_installed = $this->extensions->getInstalled('exts');
        //process for multi-package
        foreach ($this->package_info['package_content']['extensions'] as $ext_txt_id) {
            $config_file = $this->package_info['package_dir']
                ."code".DS
                ."abc".DS
                ."extensions"
                .DS
                .$ext_txt_id
                .DS
                ."config.xml";
            if (!is_file($config_file)) {
                $msg = "Extension ".$ext_txt_id." cannot be installed. "
                        ."Skipped. Cannot find config.xml file inside it.\n";
                $this->message_log[] = $msg;
                $this->errors[] = $msg;
                continue;
            }
            /**
             * @var  \DOMDocument $config
             */
            $config = @simplexml_load_file($config_file);
            if ($config === false) {
                $msg = "Extension ".$ext_txt_id." cannot be installed. Skipped. Invalid config.xml file.\n";
                $this->message_log[] = $msg;
                $this->errors[] = $msg;
                continue;
            }

            $version = (string)$config->version;
            $type = (string)$config->type;
            $type = !$type && $this->package_info['package_type'] ? $this->package_info['package_type'] : $type;
            $type = !$type ? 'extension' : $type;
            //if already installed
            if (in_array($ext_txt_id, $all_installed)) {
                $installed_info = $this->extensions->getExtensionInfo($ext_txt_id);
                $installed_version = $installed_info['version'];
                if (H::versionCompare($version, $installed_version, '<=')) {
                    // if installed version the same or higher - do nothing
                    $this->message_log[] =
                        "Extension ".$ext_txt_id." skipped. Same or higher version(".$installed_version
                        .") already installed.";
                    continue;
                }
                $installation_mode = 'upgrade';
            } else {
                $installation_mode = 'install';
            }

            //do backup anyway.
            $this->backupPreviousExtension($ext_txt_id);
            //move code from temporary into app-directory
            $old_path = $this->package_info['package_dir']
                ."code".DS
                ."abc".DS
                ."extensions".DS
                .$ext_txt_id;
            $new_path = ABC::env('DIR_APP_EXTENSIONS').$ext_txt_id;
            $result = @rename($old_path, $new_path);
            if ($result) {
                //this method requires permission set to be set
                $this->chmod_R(ABC::env('DIR_APP_EXTENSIONS').$ext_txt_id, 0775, 0775);

                $result = $this->installExtension($ext_txt_id, $type, $version, $installation_mode);
                if ($result) {
                    $this->package_info['installed'][] = $ext_txt_id;
                    $this->message_log[] = "Extension ".$ext_txt_id." has been installed successfully.\n";
                }
            } else {
                $this->errors[] =
                    "Cannot move directory ".$this->package_info['package_dir']."/code/abc/extensions/".$ext_txt_id
                    .' into '.ABC::env('DIR_APP_EXTENSIONS').$ext_txt_id."!\n";
            }
        }

    }

    public function replaceCoreFiles()
    {
        $run_errors = [];
        $core_files = $this->package_info['package_content']['core'];
        foreach ($core_files as $rel_file) {
            $src_filename = $this->package_info['package_dir'].'code'.DS.$rel_file;
            if (!is_file($src_filename)) {
                $run_errors[] = "Source file ".$src_filename." not found! Skipped. Please check package structure.";
                continue;
            }

            if (substr($rel_file, 0, 3) == 'abc') {
                $abs_file = ABC::env('DIR_APP').substr($rel_file, 4);
            } elseif (substr($rel_file, 0, 6) == 'public') {
                $abs_file = ABC::env('DIR_PUBLIC').substr($rel_file, 7);
            } else {
                $abs_file = '';
            }

            if (is_file($abs_file)) {
                @unlink($abs_file);
            }
            //check is target directory exists before copying
            $dir = dirname($abs_file);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            if (!is_dir($dir) || !is_writable($dir)) {
                $error_text = "Cannot upgrade file : '".$rel_file."\n Destination folder ".$dir
                    ." is not writable or does not exists";
                $run_errors[] = $error_text;
                $error = new AError($error_text);
                $error->toLog()->toDebug()->toMessages();
                continue;
            }

            $result = @rename($src_filename, $abs_file);
            if ($result) {
                // for index.php do not set 775 permissions because hosting providers will ban it
                $perms = basename($rel_file) == 'index.php' ? 0755 : 0775;
                chmod($abs_file, $perms);

                $install_upgrade_history = new ADataset('install_upgrade_history', 'admin');
                $install_upgrade_history->addRows(
                    [
                        'date_added'  => date("Y-m-d H:i:s", time()),
                        'name'        => 'Upgrade core file: '.$rel_file,
                        'version'     => $this->package_info['package_version'],
                        'backup_file' => '',
                        'backup_date' => '',
                        'type'        => 'upgrade',
                        'user'        => (is_object($this->registry->get('user')) ? $this->registry->get('user')
                            ->getUsername() : 'php-cli'),
                    ]);
            } else {
                $error_text = " Cannot upgrade file : '".$rel_file;
                $run_errors[] = $error_text;
                $error = new AError($error_text);
                $error->toLog()->toDebug();
            }
        }
        $this->errors += $run_errors;
        return $run_errors ? false : true;
    }

    /**
     * method removes non-empty directory (use it carefully)
     * TODO: add check for directory inside temp_dir and public/extension_id!
     *
     * @param string $dir
     *
     * @return boolean
     * @throws \ReflectionException
     */
    public function removeDir($dir = '')
    {
        if (!file_exists($dir)) {
            return false;
        }
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $obj) {
                if ($obj != "." && $obj != "..") {
                    @chmod($dir.DS.$obj, 0775);
                    $err = is_dir($dir.DS.$obj)
                        ? $this->removeDir($dir.DS.$obj)
                        : @unlink($dir.DS.$obj);
                    if (!$err) {
                        $error_text = "Package manager Error: Cannot delete file or directory: '".$dir.DS.$obj."'.";
                        $this->errors[] = $error_text;
                        $error = new AError($error_text);
                        $error->toLog()->toDebug()->toMessages();
                        return false;
                    }
                }
            }
            @reset($objects);
            return @rmdir($dir);
        } else {
            $result = @unlink($dir);
            if (!$result) {
                $error_text = "Package manager Error: Cannot delete ".$dir.".";
                $this->errors[] = $error_text;
                $error = new AError($error_text);
                $error->toLog()->toDebug()->toMessages();
            }
            return $result;
        }
     }

    /**
     * Method returns relative(!!!) paths of destination directories of package.
     * It looking for package_id in code directory of package
     *
     * @return bool|mixed
     */
    public function getDestinationDirectories()
    {
        $package_dirname = $this->package_info['package_dir'];
        $output = [];
        if (!file_exists($package_dirname."code")) {
            return false;
        } else {
            $base_dir = $package_dirname."code".DS;
            if ($this->package_info['package_content']['core']) {
                foreach ($this->package_info['package_content']['core'] as $rel_path) {
                    $rel_path = str_replace("/", DS, $rel_path);
                    if (is_file($base_dir.$rel_path)) {
                        $output[] = dirname($rel_path).DS;
                    } elseif (is_dir($rel_path)) {
                        $output[] = $rel_path;
                    }
                }
            } elseif ($this->package_info['package_content']['extensions']) {
                foreach ($this->package_info['package_content']['extensions'] as $ext_dir) {
                    $base_dir = $package_dirname."code".DS;
                    $iteration = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator(
                            $base_dir.'abc'.DS.'extensions'.DS.$ext_dir,
                            RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::SELF_FIRST,
                        RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
                    );

                    foreach ($iteration as $path => $dir) {
                        if ($dir->isDir()) {
                            $output[] = str_replace($base_dir, '', $path).DS;
                        }
                    }
                    unset($iteration);
                }
            }
        }

        return $output;
    }

    /**
     * @param string $ext_txt_id
     * @param string $type
     * @param string $version
     * @param string $install_mode
     *
     * @return bool
     * @throws AException
     * @throws \ReflectionException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function installExtension($ext_txt_id = '', $type = '', $version = '', $install_mode = 'install')
    {
        $type = !$type ? $this->package_info['package_type'] : $type;
        $version = !$version ? $this->package_info['package_version'] : $version;
        $ext_txt_id = !$ext_txt_id ? $this->package_info['package_id'] : $ext_txt_id;
        $package_dirname = $this->package_info['package_dir'];

        switch ($type) {
            case 'extension':
            case 'extensions':
            case 'template':
            case 'payment':
            case 'shipping':
            case 'language':
                // if extensions is not installed yet - install it
                if ($install_mode == 'install') {
                    $validate = $this->extension_manager->validate($ext_txt_id);
                    $validateErrors = $this->extension_manager->errors;
                    if (!$validate) {
                        $this->errors += $validateErrors;
                        $err = new AError(implode("\n", $validateErrors));
                        $err->toLog()->toDebug();
                        return false;
                    }

                    $result = $this->extension_manager->install($ext_txt_id,
                        H::getExtensionConfigXml($ext_txt_id));
                    if ($result === false) {
                        return false;
                    }

                } elseif ($install_mode == 'upgrade') {
                    $install_upgrade_history = new ADataset('install_upgrade_history', 'admin');
                    $install_upgrade_history->addRows([
                        'date_added'  => date("Y-m-d H:i:s", time()),
                        'name'        => $ext_txt_id,
                        'version'     => $version,
                        'backup_file' => '',
                        'backup_date' => '',
                        'type'        => 'upgrade',
                        'user'        => (is_object($this->registry->get('user')) ? $this->registry->get('user')
                            ->getUsername() : 'php-cli'),
                    ]);

                    $config = null;
                    $ext_conf_filename = $package_dirname
                        .'code'.DS
                        .'abc'.DS
                        .'extensions'.DS
                        .$ext_txt_id.DS.
                        'config.xml';
                    if (is_file($ext_conf_filename)) {
                        $config = simplexml_load_file($ext_conf_filename);
                    }
                    $config = !$config ? H::getExtensionConfigXml($ext_txt_id) : $config;
                    // running sql upgrade script if it exists
                    if ((string)$config->upgrade->sql) {
                        $file = $package_dirname
                            .'code'.DS
                            .'abc'.DS
                            .'extensions'.DS
                            .$ext_txt_id.DS
                            .(string)$config->upgrade->sql;
                        if (file_exists($file)) {
                            $this->db->performSql($file);
                        } else {
                            return false;
                        }
                    }
                    // running php install script if it exists
                    if ((string)$config->upgrade->trigger) {
                        $file = $package_dirname
                            .'code'.DS
                            .'abc'.DS
                            .'extensions'.DS
                            .$ext_txt_id.DS
                            .(string)$config->upgrade->trigger;
                        if (file_exists($file)) {
                            try {
                                require_once($file);
                            } catch (AException $e) {
                                $this->errors[] = $e->getMessage();
                            }
                        }
                    }

                    $this->extension_manager->editSetting($ext_txt_id, [
                        'license_key' => $this->package_info['extension_key'],
                        'version'     => $version,
                    ]);
                }
                break;
            default:
                $error_text = 'Unknown extension type: "'.$type.'"';
                $this->errors[] = $error_text;
                $err = new AError($error_text);
                $err->toLog()->toDebug();
                return false;
                break;
        }

        return true;
    }

    /**
     * @param string $ext_txt_id
     *
     * @return bool
     * @throws AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function uninstallExtension($ext_txt_id)
    {
        $ext_txt_id = (string)$ext_txt_id;
        if (!$ext_txt_id) {
            return false;
        }

        $validate = $this->extension_manager->checkDependantsBeforeUninstall($ext_txt_id);
        if (!$validate) {
            $this->errors += $this->extension_manager->errors;
            return false;
        }

        $result = $this->extension_manager->uninstall($ext_txt_id, H::getExtensionConfigXml($ext_txt_id));
        if ($result === false) {
            $this->errors += $this->extension_manager->errors;
            return false;
        }

        return true;
    }

    /**
     * @return bool
     * @throws AException
     */
    public function upgradeCore()
    {
        //clear all cache
        Registry::cache()->flush();

        $package_dirname = $this->package_info['package_dir'];
        $config_file = $package_dirname.'package.xml';
        if (!is_file($config_file)) {
            $this->errors[] = 'Cannot find package.xml!';
            return false;
        }
        $config = @simplexml_load_file($config_file);
        if ($config === false) {
            $this->errors[] = 'Invalid package.xml file!';
            return false;
        }
        // running sql upgrade script if it exists
        if ((string)$config->upgrade->sql) {
            $file = $package_dirname.(string)$config->upgrade->sql;
            if (is_file($file)) {
                $result = $this->db->performSql($file);
                if (!$result) {
                    $this->errors[] = $this->db->error;
                    return false;
                }
            }
        }
        // running php upgrade script if it exists
        if ((string)$config->upgrade->trigger) {
            $file = $package_dirname.(string)$config->upgrade->trigger;
            if (is_file($file)) {
                /** @noinspection PhpIncludeInspection */
                try {
                    include($file);
                } catch (\Exception $e) {
                    $this->errors[] = $e->getMessage();
                    return false;
                }
            }
        }

        $result = $this->replaceCoreFiles();
        if ($result) {
            $this->updateCoreVersion($this->package_info['package_version']);
            // write to history
            $install_upgrade_history = new ADataset('install_upgrade_history', 'admin');
            $install_upgrade_history->addRows([
                'date_added'  => date("Y-m-d H:i:s", time()),
                'name'        => 'Core upgrade',
                'version'     => $this->package_info['package_version'],
                'backup_file' => '',
                'backup_date' => '',
                'type'        => 'upgrade',
                'user'        => (is_object($this->registry->get('user')) ? $this->registry->get('user')
                    ->getUsername() : 'php-cli'),
            ]);

        } else {
            return false;
        }
    }

    /**
     * @param string $new_version
     *
     * @return bool
     */
    public function updateCoreVersion($new_version)
    {
        if (!$new_version) {
            return false;
        }

        $new_version = preg_replace('/[^0-9\.]/', '', $new_version);
        list($master, $minor, $built) = explode(".", $new_version);
        $content = "<?php\nuse abc\core\ABC;\n";
        $content .= "ABC::env('MASTER_VERSION', '".$master."');\n";
        $content .= "ABC::env('MINOR_VERSION', '".$minor."');\n";
        $content .= "ABC::env('VERSION_BUILT', '".$built."');\n";

        // upgrade APP VERSION
        @file_put_contents(ABC::env('DIR_CORE').'init'.DS.'version.php', $content);
        return true;
    }

    /**
     * Method change access mode recursively
     *
     * @param string $path path to directory or file
     * @param string $filemode
     * @param string $dirmode
     *
     * @return void
     */
    public function chmod_R($path, $filemode, $dirmode)
    {
        $path = (string)$path;
        if (is_dir($path)) {
            if (!chmod($path, $dirmode)) {
                $dirmode_str = decoct($dirmode);
                $error_text = "Notice: Failed applying filemode '".$dirmode_str."' on directory '".$path."'.\n";
                $error_text .= "  `-> the directory '".$path."' will be skipped from recursive chmod.\n";
                $this->registry->get('log')->write($error_text);
                return null;
            }
            $dh = opendir($path);
            while (($file = readdir($dh)) !== false) {
                if ($file != '.' && $file != '..') { // skip self and parent pointing directories
                    $fullpath = $path.'/'.$file;
                    $this->chmod_R($fullpath, $filemode, $dirmode);
                }
            }
            closedir($dh);
        } else {
            //skip if does not exists
            if (!file_exists($path)) {
                return null;
            }

            if (is_link($path)) {
                $this->registry->get('log')->write('Package manager Notice: Recursive chmod. Symlink '.$path
                    .' is skipped.');
                return null;
            }
            // for index.php do not set 775 permissions because hosting providers will ban it
            if (pathinfo($path, PATHINFO_BASENAME) == 'index.php' && $filemode == 775) {
                $filemode = 644;
            }
            if (!chmod($path, $filemode)) {
                $filemode_str = decoct($filemode);
                $this->registry->get('log')->write("Notice: Failed applying filemode ".$filemode_str." on file ".$path
                    ."\n");
                return null;
            }
        }
    }

    /**
     * Method of checks before installation process
     */
    public function validate()
    {
        $this->errors = [];
        //1.check is extension directory writable
        if (!is_writable(ABC::env('DIR_APP_EXTENSIONS'))) {
            $this->errors[] =
                'Directory '.ABC::env('DIR_APP_EXTENSIONS').' is not writable. Please change permissions for it.';
        }
        //2. check temporary directory. just call method
        $this->getTempDir();

        //3. run validation for backup-process before install
        $bkp = new ABackup('', false);
        if (!$bkp->validate()) {
            $this->errors += $bkp->error;
        }

        $this->extensions->hk_ValidateData($this);

        return ($this->errors ? false : true);

    }

    /**
     * Method returns absolute path to temporary directory for unpacking package
     * if system/temp is inaccessible - use php temp directory
     *
     * @return string
     */
    public function getTempDir()
    {
        $tmp_dir = ABC::env('DIR_SYSTEM').'temp';
        $tmp_install_dir = $tmp_dir.'/install';
        if (is_dir($tmp_dir) && !is_dir($tmp_install_dir)) {
            @mkdir($tmp_install_dir, 0755, true);
        }
        //try to create tmp dir if not yet created and install.
        if (H::is_writable_dir($tmp_dir) && H::is_writable_dir($tmp_install_dir)) {
            $dir = $tmp_install_dir."/";
        } else {
            if (!is_dir(sys_get_temp_dir().'/abantecart_install')) {
                @mkdir(sys_get_temp_dir().'/abantecart_install/', 0775);
            }
            $dir = sys_get_temp_dir().'/abantecart_install/';

            if (!is_writable($dir)) {
                $error_text = 'Error: php tried to use directory '
                    .ABC::env('DIR_SYSTEM')."temp/install".' but it is non-writable. Temporary php-directory '
                    .$dir.' is non-writable too! Please change permissions one of them.'."\n";
                $this->errors[] = $error_text;
                $this->registry->get('log')->write($error_text);
            }
        }

        return $dir;
    }

    // this method calls before installation of package
    public function cleanTempDir()
    {
        $temp_dir = $this->getTempDir();
        $files = glob($temp_dir.'*');
        if ($files) {
            foreach ($files as $file) {
                if (is_dir($file)) {
                    $this->removeDir($file);
                } else {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Validate full version to be greater and same minor version.
     *
     * @param \SimpleXMLElement|null $config_xml
     *
     * @return bool
     * @throws AException
     * @throws \ReflectionException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function checkCartVersion(\SimpleXMLElement $config_xml = null)
    {
        $config_xml = $config_xml === null ? $this->package_config : $config_xml;
        $full_check = false;
        $minor_check = false;
        $versions = [];
        foreach ($config_xml->cartversions->item as $item) {
            $version = (string)$item;
            $versions[] = $version;

            $split_versions = explode('.', preg_replace('/[^0-9\.]/', '', $version));
            $full_check = H::versionCompare(
                $version,
                ABC::env('VERSION'),
                '<='
            );
            $minor_check = H::versionCompare(
                $split_versions[0].'.'.$split_versions[1],
                ABC::env('MASTER_VERSION').'.'.ABC::env('MINOR_VERSION'),
                '=='
            );

            if ($full_check && $minor_check) {
                break;
            }
        }

        if (!$full_check || !$minor_check) {
            $this->package_info['confirm_version_incompatibility'] = false;
            $this->package_info['version_incompatibility_text'] = sprintf(
                $this->language->get('confirm_version_incompatibility'),
                (ABC::env('VERSION')),
                implode(', ', $versions)
            );
        }
        $this->package_info['supported_cart_versions'] = $versions;
        return $full_check && $minor_check;
    }

    /**
     * @param string $extension_key
     *
     * @return bool
     */
    public function isCorePackage($extension_key = '')
    {
        if ($this->package_info['package_content']['core'] && !$this->package_info['extension_key']) {
            return true;
        }

        if (!$extension_key) {
            $extension_key = $this->package_info['extension_key'];
        }
        return (strpos($extension_key, 'abantecart_') === 0);
    }
}