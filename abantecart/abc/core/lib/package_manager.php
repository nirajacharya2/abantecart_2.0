<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2017 Belavier Commerce LLC

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
use abc\core\helper\AHelperUtils;
use abc\core\engine\Registry;

if ( ! class_exists('abc\core\ABC')) {
    header('Location: static_pages/?forbidden='.basename(__FILE__));
}

/**
 * @property  AExtensionManager              $extension_manager
 * @property  \abc\core\engine\ALoader       $load
 * @property  \abc\core\engine\ExtensionsApi $extensions
 * @property  AUser                          $user
 * @property  \abc\core\lib\ALanguageManager $language
 * @property  ALog                           $log
 * @property  \abc\core\cache\ACache         $cache
 * @property  ADB                            $db
 */
class APackageManager
{
    /**
     * @var Registry
     */
    protected $registry;
    public $error = '';
    public $message_log = [];
    /**
     * size of data in bytes
     *
     * @var int
     */
    public $dataSize = 0;
    public $package_info = [];

    public function __construct( array $package_info )
    {
        if ( ! ABC::env('IS_ADMIN')) { // forbid for non admin calls
            throw new AException (AC_ERR_LOAD, 'Error: permission denied to access package manager');
        }
        /**
         * @var Registry
         */
        $this->registry = Registry::getInstance();
        $this->package_info =& $package_info;
    }

    public function __get($key)
    {
        return $this->registry->get($key);
    }

    public function __set($key, $value)
    {
        $this->registry->set($key, $value);
    }

    /**
     * @param string  $url
     * @param boolean $save
     * @param string  $new_file_name
     *
     * @return boolean|array
     */
    public function getRemoteFile($url, $save = true, $new_file_name = '')
    {
        if ( ! $url) {
            return false;
        }
        $file = new AConnect();
        if ($save) {
            $result = $file->getFile($url, $new_file_name); //download
        } else {
            $result = $file->getResponse($url); // just get data
        }
        if ( ! $result) {
            $this->error = $file->error;

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
        if ( ! $url) {
            return false;
        }
        $file = new AConnect();
        $file->connect_method = 'socket'; //use this method because curl returns no header 'Content-Disposition' with file name
        $url = $url.(! is_int(strpos($url, '?')) ? '?file_size=1' : '&file_size=1');
        $result = $file->getDataHeaders($url);
        if ( ! $result) {
            $this->error = $file->error;

            return false;
        }

        return $result;
    }

    /**
     * @param string $archive_filename
     * @param string $dest_dir
     *
     * @return boolean
     */
    public function unpack($archive_filename, $dest_dir)
    {
        if ( ! file_exists($archive_filename)) {
            $this->error = 'Error: Cannot unpack file "'.$archive_filename.'" because it does not exists.';
            $error = new AError ($this->error);
            $error->toLog()->toDebug();

            return false;
        }
        if ( ! file_exists($dest_dir) || ! is_dir($dest_dir)) {
            $this->error = 'Error: Cannot unpack file "'.$archive_filename.'" because destination directory "'.$dest_dir.'" does not exists.';
            $error = new AError ($this->error);
            $error->toLog()->toDebug();

            return false;
        }
        if ( ! is_writable($dest_dir)) {
            $this->error = 'Error: Cannot unpack file "'.$archive_filename.'" because destination directory "'.$dest_dir.'" have no write permission.';
            $error = new AError ($this->error);
            $error->toLog()->toDebug();
            return false;
        }

        //remove destination folder first. run pathinfo twice for tar.gz. files
        $package_dir = $dest_dir.pathinfo(pathinfo($archive_filename, PATHINFO_FILENAME), PATHINFO_FILENAME);
        $this->removeDir( $package_dir );
        unset($this->package_info['package_dir']);

        $unpack_result = AHelperUtils::extractArchive($archive_filename, $dest_dir);
        if($unpack_result){
            $this->chmod_R($dest_dir.$this->package_info['tmp_dir'], 0775, 0775);
        }
        if( $unpack_result){
            $this->package_info['package_dir'] = $package_dir;
            return true;
        }
        return false;
    }

    public function extractPackageInfo(){
        /**
         * @var \SimpleXMLElement $config
         */
        $config = simplexml_load_string(file_get_contents($this->package_info['package_dir'].'package.xml'));
        if(!$config){
            $this->error = 'Cannot to read file '.$this->package_info['package_dir'].'package.xml';
            return false;
        }
        $this->package_info['config'] = $config;
        $this->package_info['package_id'] = (string)$config->id;
        $this->package_info['package_type'] = (string)$config->type;
        $this->package_info['package_priority'] = (string)$config->priority;
        $this->package_info['package_version'] = (string)$config->version;
        $this->package_info['package_content'] = array();
        if ((string)$config->package_content->extensions) {
            $this->package_info['package_content']['extensions'] = array();
            foreach ($config->package_content->extensions->extension as $item) {
                if ((string)$item) {
                    $this->package_info['package_content']['extensions'][] = (string)$item;
                }
            }
            $this->package_info['package_content']['total'] = sizeof($this->package_info['package_content']['extensions']);
        }

        if ((string)$config->package_content->core) {
            $this->package_info['package_content']['core'] = array();
            foreach ($config->package_content->core->files->file as $item) {
                if ((string)$item) {
                    $this->package_info['package_content']['core'][] = (string)$item;
                }
            }
        }
        return true;
    }

    public function validateDestination(){
        if( !is_dir($this->package_info['package_dir']) ){
            $this->error = 'Temporary directory of the package not found.';
            return false;
        }

        $package_dirs = $this->getDestinationDirectories();
        if(!$package_dirs){
            $this->error = 'No directories in the package!';
            return false;
        }
        $errors = [];
        foreach($package_dirs as $dir){
            if(substr($dir,0,3) == 'abc'){
                $dir_path = ABC::env('DIR_APP').substr($dir,4);
                $rel_directory = ABC::env('DIR_APP');
            }
            if(substr($dir,0,6) == 'public'){
                $dir_path = ABC::env('DIR_PUBLIC').substr($dir,7);
                $rel_directory = ABC::env('DIR_PUBLIC');
            }
            //try to change permissions
            if( is_dir($dir_path) && !is_writable($dir_path)){
                @chmod( $dir_path,0775 );
            }
            //if directory absent - try to create
            if( !is_dir($dir_path) ) {
                @mkdir( $dir_path, 0775, true);
            }
            if( !is_dir($dir_path) || !is_writable($dir_path) ){
                $errors[] = $dir_path;
            }
            //if we can write into directory - check files if it
            else{
                $files = glob($this->package_info['package_dir']."code".DIRECTORY_SEPARATOR.$dir.'*');
                foreach($files as $file){
                    if( !is_file($file) ){ continue; }
                    //ok compound filename in destination directory and check if is exists
                    $dest_filename = str_replace(
                        $this->package_info['package_dir']."code".DIRECTORY_SEPARATOR.basename($rel_directory).DIRECTORY_SEPARATOR,
                        $rel_directory,
                        $file
                        );
                    if( file_exists($dest_filename)){
                        @chmod($dest_filename, 0644);
                        if( !is_writable($dest_filename) ){
                            $errors[] = $dest_filename;
                        }
                    }
                }
            }
        }

        if( $errors ){
            $this->error = implode("\n", $errors);
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
     */
    public function backupPreviousExtension( $ext_txt_id )
    {
        $ext_txt_id = (string)$ext_txt_id;
        if ( !$ext_txt_id) {
            return false;
        }
        $ext_dir_path = ABC::env('DIR_APP_EXTENSIONS').$ext_txt_id.DIRECTORY_SEPARATOR;
        //if directory does not exists thinks that's ok
        if( !is_dir($ext_dir_path) ){
            return true;
        }

        $backup = new ABackup($ext_txt_id.'_'.date('Y-m-d-H-i-s'));
        if ($backup->error) {
            $this->error = implode("\n", $backup->error)."\n";
            return false;
        }
        $backup_dirname = $backup->getBackupName();
        if ($backup_dirname) {

            if ( ! $backup->backupDirectory($ext_dir_path, true)) {
                $this->error = implode("\n", $backup->error)."\n";
                return false;
            }

            if ( ! $backup->dumpDatabase()) {
                return false;
            }
            if ( ! $backup->archive(ABC::env('DIR_BACKUP').$backup_dirname.'.tar.gz', ABC::env('DIR_BACKUP'), $backup_dirname)) {
                return false;
            }
        } else {
            return false;
        }

        $info = $this->extensions->getExtensionInfo($ext_txt_id);

        $install_upgrade_history = new ADataset('install_upgrade_history', 'admin');
        $install_upgrade_history->addRows(array(
            'date_added'  => date("Y-m-d H:i:s", time()),
            'name'        => $ext_txt_id,
            'version'     => $info['version'],
            'backup_file' => $backup_dirname.'.tar.gz',
            'backup_date' => date("Y-m-d H:i:s", time()),
            'type'        => 'backup',
            'user'        => (is_object( $this->user ) ? $this->user->getUsername() : 'php-cli'),
        ));

        //delete previous version
        $this->removeDir($ext_dir_path);
        return true;
    }

    public function installPackageExtensions(){
        if ( !sizeof($this->package_info['package_content']['extensions'])){
            return true;
        }
        $all_installed = $this->extensions->getInstalled('exts');
        //process for multi-package
        foreach ($this->package_info['package_content']['extensions'] as $ext_txt_id) {
            $config_file = $this->package_info['package_dir']
                            ."code".DIRECTORY_SEPARATOR
                            ."abc".DIRECTORY_SEPARATOR
                            ."extensions"
                            .DIRECTORY_SEPARATOR
                            .$ext_txt_id
                            .DIRECTORY_SEPARATOR
                            ."config.xml";
            if( !is_file( $config_file )){
                $msg = "Extension ".$ext_txt_id." cannot be installed. Skipped. Cannot find config.xml file inside it.\n";
                $this->message_log[] = $msg;
                $this->error .= $msg;
                continue;
            }
            /**
            * @var  \DOMDocument $config
             */
            $config = simplexml_load_file( $config_file );
            if( $config === false ){
                $msg = "Extension ".$ext_txt_id." cannot be installed. Skipped. Invalid config.xml file.\n";
                $this->message_log[] = $msg;
                $this->error .= $msg;
                continue;
            }

            $version = (string)$config->version;
            $type = (string)$config->type;
            $type = !$type && $this->package_info['package_type'] ? $this->package_info['package_type'] : $type;
            $type = !$type ? 'extension' : $type;
            //if already installed
            if (in_array($ext_txt_id, $all_installed)) {
                $installed_info = $this->extensions->getExtensionInfo( $ext_txt_id );
                $installed_version = $installed_info['version'];
                if (AHelperUtils::versionCompare($version, $installed_version, '<=')) {
                    // if installed version the same or higher - do nothing
                    $this->message_log[] = "Extension ".$ext_txt_id." skipped. Same or higher version(".$installed_version.") already installed.";
                    continue;
                }
                $installation_mode = 'upgrade';
            }else{
                $installation_mode = 'install';
            }

            //do backup anyway.
            $this->backupPreviousExtension($ext_txt_id);
            //move code from temporary into app-directory
            $old_path = $this->package_info['package_dir']
                        ."code".DIRECTORY_SEPARATOR
                        ."abc".DIRECTORY_SEPARATOR
                        ."extensions".DIRECTORY_SEPARATOR
                        .$ext_txt_id;
            $new_path = ABC::env('DIR_APP_EXTENSIONS').$ext_txt_id;
            $result = @rename($old_path, $new_path);
            if($result) {
                //this method requires permission set to be set
                $this->chmod_R( ABC::env( 'DIR_APP_EXTENSIONS' ).$ext_txt_id, 0775, 0775 );

                $result = $this->installExtension( $ext_txt_id, $type, $version, $installation_mode );
                if ( $result ) {
                    $this->message_log[] = "Extension ".$ext_txt_id." has been installed successfully.\n";
                }
            }else{
                $this->error .= "Cannot move directory ".$this->package_info['package_dir']."/code/abc/extensions/".$ext_txt_id .' into '.ABC::env('DIR_APP_EXTENSIONS').$ext_txt_id."!\n";
            }
        }

    }


    public function replaceCoreFiles()
    {
        $core_files = $this->package_info['package_content']['core'];
        if ($this->package_info['ftp']) {
            $ftp_user = $this->package_info['ftp_user'];
            $ftp_password = $this->package_info['ftp_password'];
            $ftp_port = $this->package_info['ftp_port'];
            $ftp_host = $this->package_info['ftp_host'];

            $fconnect = ftp_connect($ftp_host, $ftp_port);
            ftp_login($fconnect, $ftp_user, $ftp_password);
            ftp_pasv($fconnect, true);

            foreach ($core_files as $core_filename) {
                $remote_file = pathinfo($this->package_info['ftp_path'].$core_filename, PATHINFO_BASENAME);
                $remote_dir = pathinfo($this->package_info['ftp_path'].$core_filename, PATHINFO_DIRNAME).'/';
                $src_dir = (string)$this->package_info['tmp_dir'].$this->package_info['package_dir'].'/code/'.$core_filename;
                $result = $this->ftp_move($fconnect, $src_dir, $remote_file, $remote_dir);
                if ($result) {
                    $install_upgrade_history = new ADataset('install_upgrade_history', 'admin');
                    $install_upgrade_history->addRows(array(
                        'date_added'  => date("Y-m-d H:i:s", time()),
                        'name'        => 'Upgrade core file: '.$remote_file,
                        'version'     => $this->package_info['package_version'],
                        'backup_file' => '',
                        'backup_date' => '',
                        'type'        => 'upgrade',
                        'user'        => (is_object( $this->user ) ? $this->user->getUsername() : 'php-cli'),
                    ));
                } else {
                    $this->error .= " Error: Cannot upgrade file : '".$core_filename."\n";
                    $error = new AError ($this->error);
                    $error->toLog()->toDebug();
                }
            }// end of loop
            ftp_close($fconnect);
        } else {
            foreach ($core_files as $core_filename) {
                if (is_file(ABC::env('DIR_ROOT').'/'.$core_filename)) {
                    unlink(ABC::env('DIR_ROOT').'/'.$core_filename);
                }
                //check is target directory exists before copying
                $dir = pathinfo(ABC::env('DIR_ROOT').'/'.$core_filename, PATHINFO_DIRNAME);
                if ( ! is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }

                if ( ! is_dir($dir) || ! is_writable($dir)) {
                    $this->error .= " Error: Cannot upgrade file : '".$core_filename."\n Destination folder ".$dir." is not writable or does not exists";
                    $error = new AError ($this->error);
                    $error->toLog()->toDebug()->toMessages();
                    continue;
                }

                $result = rename($this->package_info['tmp_dir'].$this->package_info['package_dir'].'/code/'.$core_filename, ABC::env('DIR_ROOT').'/'.$core_filename);
                if ($result) {
                    // for index.php do not set 775 permissions because hosting providers will ban it
                    if (pathinfo($core_filename, PATHINFO_BASENAME) == 'index.php') {
                        chmod(ABC::env('DIR_ROOT').'/'.$core_filename, 0755);
                    } else {
                        chmod(ABC::env('DIR_ROOT').'/'.$core_filename, 0775);
                    }

                    $install_upgrade_history = new ADataset('install_upgrade_history', 'admin');
                    $install_upgrade_history->addRows(array(
                        'date_added'  => date("Y-m-d H:i:s", time()),
                        'name'        => 'Upgrade core file: '.$core_filename,
                        'version'     => $this->package_info['package_version'],
                        'backup_file' => '',
                        'backup_date' => '',
                        'type'        => 'upgrade',
                        'user'        => (is_object( $this->user ) ? $this->user->getUsername() : 'php-cli'),
                    ));
                } else {
                    $this->error .= " Error: Cannot upgrade file : '".$core_filename."\n";
                    $error = new AError ($this->error);
                    $error->toLog()->toDebug();
                }
            }
        }
    }

    /**
     * method removes non-empty directory (use it carefully)
     * TODO: add check for directory inside temp_dir and public/extension_id!
     *
     * @param string $dir
     *
     * @return boolean
     */
    public function removeDir($dir = '')
    {
        if( !file_exists($dir) ){
            return false;
        }
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $obj) {
                if ($obj != "." && $obj != "..") {
                    @chmod($dir.DIRECTORY_SEPARATOR.$obj, 0775);
                    $err = is_dir($dir.DIRECTORY_SEPARATOR.$obj)
                            ? $this->removeDir($dir.DIRECTORY_SEPARATOR.$obj)
                            : @unlink($dir.DIRECTORY_SEPARATOR.$obj);
                    if ( ! $err) {
                        $this->error = "Package manager Error: Cannot delete file or directory: '". $dir . DIRECTORY_SEPARATOR . $obj ."'.";
                        $error = new AError ($this->error);
                        $error->toLog()->toDebug()->toMessages();
                        return false;
                    }
                }
            }
            @reset($objects);
            return @rmdir($dir);
        } else {
            $this->error = "Package manager Error: Cannot delete ". $dir .". It is not a directory!";
            $error = new AError ($this->error);
            $error->toLog()->toDebug()->toMessages();
            return false;
        }
        return true;
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
        $output = array();
        if ( ! file_exists($package_dirname."code")) {
            return false;
        } else {
            $dir = $package_dirname."code";
            $d = array();
            while ($dirs = glob($dir.'/*', GLOB_ONLYDIR)) {
                $dir .= '/*';
                if ( ! $d) {
                    $d = $dirs;
                } else {
                    $d = array_merge($d, $dirs);
                }
            }
        }

        if ($d) {
            foreach ($d as $dir) {
                $dir = str_replace($package_dirname."code".DIRECTORY_SEPARATOR, "", $dir);
                $output[] = $dir.DIRECTORY_SEPARATOR;
            }
        }

        return $output;
    }

    /**
     * @param string $ftp_user
     * @param string $ftp_password
     * @param string $ftp_host
     * @param string $ftp_path
     * @param int    $ftp_port
     *
     * @return bool
     */
    public function checkFTP($ftp_user, $ftp_password = '', $ftp_host = '', $ftp_path = '', $ftp_port = 21)
    {
        $this->load->language('tool/package_installer');
        if ( ! $ftp_host) {
            $ftp_host = 'localhost';
        } else {
            // looking for port number in the host
            $ftp_host = explode(':', $ftp_host);
            $ftp_port = (int)$ftp_host[1];
            $ftp_host = $ftp_host[0];
        }
        $ftp_port = ! $ftp_port ? 21 : $ftp_port;

        if ( ! $ftp_user) {
            $this->error = $this->language->get('error_ftp_user');

            return false;
        }
        if ( ! $ftp_password) {
            $this->error = $this->language->get('error_ftp_password');

            return false;
        }

        $fconnect = ftp_connect($ftp_host, $ftp_port);
        if ( ! $fconnect && $ftp_host == 'localhost') {
            //check dns perversion :-)
            $ftp_host = '127.0.0.1';
            $fconnect = ftp_connect($ftp_host, $ftp_port);
        }

        if ($fconnect) {
            $login = ftp_login($fconnect, $ftp_user, $ftp_password);
            if ( ! $login) {
                $this->error = $this->language->get('error_ftp_login').$ftp_host.':'.$ftp_port;

                return false;
            }

            $ftp_path = ! $ftp_path ? $this->_ftp_find_app_root($fconnect) : $ftp_path;
            // if all fine  - write ftp parameters into session
            $this->package_info['ftp'] = true;
            $this->package_info['ftp_user'] = $ftp_user;
            $this->package_info['ftp_password'] = $ftp_password;
            $this->package_info['ftp_host'] = $ftp_host;
            $this->package_info['ftp_port'] = $ftp_port;
            $this->package_info['ftp_path'] = $ftp_path;

            ftp_close($fconnect);
        } else {
            $this->error = $this->language->get('error_ftp_connect');

            return false;
        }

        return true;
    }

    /**
     * Try to guess an installation location in the server via FTP
     *
     * @param resource $fconnect
     *
     * @return string|bool
     */
    protected function _ftp_find_app_root($fconnect)
    {
        if ( ! $fconnect) {
            return false;
        }

        // Turn passive mode on
        if (@ftp_pasv($fconnect, true) === false) {
            return false;
        }

        $abs_path = pathinfo($_SERVER['DOCUMENT_ROOT'].$_SERVER['PHP_SELF'], PATHINFO_DIRNAME);

        $ftp_dir_list = array();

        // first fo all try to change directory to absolute server path
        //(for case when ftp-user does not locked in ftp root directory)
        if (@ftp_chdir($fconnect, $abs_path) === true) {
            return $abs_path.'/';
        } else {
            //for ftp chrooted users
            //get list of directories
            if ($files = @ftp_nlist($fconnect, '.')) {
                //get only directories
                foreach ($files as $file) {
                    if (ftp_size($fconnect, $file) == "-1") {
                        $ftp_dir_list[] = $file;
                    }
                }
                //find ftp-directory name inside absolute server path
                $target_dir = null;
                if ($ftp_dir_list) {
                    foreach ($ftp_dir_list as $dir) {
                        if (is_int($pos = strpos($abs_path, $dir))) {
                            $target_dir = substr($abs_path, $pos);
                            break;
                        }
                    }
                    if ($target_dir) {
                        return '/'.trim($target_dir, '/').'/';
                    }
                }
            }
        }

        return false;
    }

    /**
     * Function for moving directory or file via ftp-connection
     *
     * @param        $fconnect
     * @param string $local       local path to file or directory
     * @param string $remote_file remote file  or directory name
     * @param string $remote_dir
     *
     * @return bool
     */
    public function ftp_move($fconnect, $local, $remote_file, $remote_dir)
    {
        $local = (string)$local;
        $remote_file = (string)$remote_file;
        $remote_dir = (string)$remote_dir;

        if ( ! $this->package_info['ftp']) {
            return false;
        }

        // if destination folder does not exists - try to create
        if (@ftp_chdir($fconnect, $remote_dir) === false) {
            $basedir = $this->package_info['ftp_path'];
            //relative subdirs
            $sub_dirs = str_replace($basedir, '', $remote_dir);
            if (substr($sub_dirs, 0, 1) == '/') {
                $sub_dirs = substr($sub_dirs, 1);
            }
            $result = $this->_ftp_make_nested_dirs($fconnect, $basedir, $sub_dirs);
            if ( ! $result) {
                $this->error .= "\nCannot create directory ".$remote_dir." via ftp. ";

                return false;
            }
            if ( ! ftp_chmod($fconnect, 0755, $remote_dir)) {
                $error = new AError('Cannot change mode for directory '.$remote_dir);
                $error->toLog()->toDebug();
            }
            //change current directory to newly created
            @ftp_chdir($fconnect, $remote_dir);
        }

        if (is_dir($local)) {
            $this->_ftp_put_dir($fconnect, $local, $remote_dir);
        } else {
            if ( ! ftp_put($fconnect, $remote_file, $local, FTP_BINARY)) {
                $this->error .= "\nCannot put file ".$remote_file." via ftp.";

                return false;
            }
            $remote_file = $remote_dir.pathinfo($local, PATHINFO_BASENAME);
            $chmod_result = ftp_chmod($fconnect, 0755, $remote_file);
            if ( ! $chmod_result) {
                $error = new AError('Cannot change mode for file '.$remote_file);
                $error->toLog()->toDebug();
            }
        }

        return true;
    }

    /**
     * @param $fconnect
     * @param $ftp_base_dir
     * @param $ftp_path
     *
     * @return bool
     */
    protected function _ftp_make_nested_dirs($fconnect, $ftp_base_dir, $ftp_path)
    {
        @ftp_chdir($fconnect, $ftp_base_dir); // /var/www/uploads
        $parts = explode('/', $ftp_path); // 2013/06/11/username
        foreach ($parts as $part) {
            if ( ! @ftp_chdir($fconnect, $part)) {
                ftp_mkdir($fconnect, $part);
                $result = ftp_chdir($fconnect, $part);
                if ( ! $result) {
                    return false;
                }
                ftp_chmod($fconnect, 0755, $part);
            }
        }

        return true;
    }

    /**
     * method for moving directory via ftp connection
     *
     * @param resource $conn_id
     * @param string   $src_dir
     * @param string   $dst_dir
     */
    protected function _ftp_put_dir($conn_id, $src_dir, $dst_dir)
    {
        $d = dir($src_dir);
        // do this for each file in the directory
        while ($file = $d->read()) {
            // Stay only with in current directory
            if ($file != "." && $file != "..") {
                // do the following if it is a directory
                if (is_dir($src_dir."/".$file)) {
                    if ( ! @ftp_chdir($conn_id, $dst_dir."/".$file)) {
                        // create directories that do not yet exist
                        ftp_mkdir($conn_id, $dst_dir."/".$file);
                        ftp_chmod($conn_id, 0755, $dst_dir."/".$file);
                    }
                    // recursive part
                    $this->_ftp_put_dir($conn_id, $src_dir."/".$file, $dst_dir."/".$file);
                } else {
                    // put the files
                    ftp_put($conn_id, $dst_dir."/".$file, $src_dir."/".$file, FTP_BINARY);
                    ftp_chmod($conn_id, 0755, $dst_dir."/".$file);
                }
            }
        }
        $d->close();
    }

    /**
     * @param resource $conn
     * @param string   $dir
     *
     * @return bool
     */
    protected function delete_ftp_dir($conn, $dir)
    {
        $files = ftp_nlist($conn, $dir);
        if ( ! $files) {
            return ftp_rmdir($conn, $dir);
        }
        foreach ($files as $file) {
            $is_dir = ftp_chdir($conn, $file);
            if ($is_dir) {
                $this->delete_ftp_dir($conn, $file);
            } else {
                ftp_delete($conn, $file);
            }
        }
        ftp_rmdir($conn, $dir);

        return true;
    }

    /**
     * @param string $ext_txt_id
     * @param string $type
     * @param string $version
     * @param string $install_mode
     *
     * @return bool
     */
    public function installExtension($ext_txt_id = '', $type = '', $version = '', $install_mode = 'install')
    {
        $type = ! $type ? $this->package_info['package_type'] : $type;
        $version = ! $version ? $this->package_info['package_version'] : $version;
        $ext_txt_id = ! $ext_txt_id ? $this->package_info['package_id'] : $ext_txt_id;
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
                    if ( ! $validate) {
                        $this->error = implode('<br>', $validateErrors);
                        $err = new AError($this->error);
                        $err->toLog()->toDebug();
                        return false;
                    }

                    $result = $this->extension_manager->install($ext_txt_id, AHelperUtils::getExtensionConfigXml($ext_txt_id));
                    if ($result === false) {
                        return false;
                    }

                } elseif ($install_mode == 'upgrade') {
                    $install_upgrade_history = new ADataset('install_upgrade_history', 'admin');
                    $install_upgrade_history->addRows(array(
                        'date_added'  => date("Y-m-d H:i:s", time()),
                        'name'        => $ext_txt_id,
                        'version'     => $version,
                        'backup_file' => '',
                        'backup_date' => '',
                        'type'        => 'upgrade',
                        'user'        => (is_object( $this->user ) ?  $this->user->getUsername() : 'php-cli'),
                    ));

                    $config = null;
                    $ext_conf_filename = $package_dirname
                                        .'code'.DIRECTORY_SEPARATOR
                                        .'abc'.DIRECTORY_SEPARATOR
                                        .'extensions'.DIRECTORY_SEPARATOR
                                        .$ext_txt_id.DIRECTORY_SEPARATOR.
                                        'config.xml';
                    if (is_file($ext_conf_filename)) {
                        $config = simplexml_load_file($ext_conf_filename);
                    }
                    $config = ! $config ? AHelperUtils::getExtensionConfigXml($ext_txt_id) : $config;
                    // running sql upgrade script if it exists
                    if ( (string)$config->upgrade->sql ) {
                        $file = $package_dirname
                                .'code'.DIRECTORY_SEPARATOR
                                .'abc'.DIRECTORY_SEPARATOR
                                .'extensions'.DIRECTORY_SEPARATOR
                                .$ext_txt_id.DIRECTORY_SEPARATOR
                                .(string)$config->upgrade->sql;
                        if (file_exists($file)) {
                            $this->db->performSql($file);
                        }else{
                            return false;
                        }
                    }
                    // running php install script if it exists
                    if ( (string)$config->upgrade->trigger ) {
                        $file = $package_dirname
                                .'code'.DIRECTORY_SEPARATOR
                                .'abc'.DIRECTORY_SEPARATOR
                                .'extensions'.DIRECTORY_SEPARATOR
                                .$ext_txt_id.DIRECTORY_SEPARATOR
                                .(string)$config->upgrade->trigger;
                        if (file_exists($file)) {
                            require_once($file);
                        }
                    }

                    $this->extension_manager->editSetting($ext_txt_id, array(
                        'license_key' => $this->package_info['extension_key'],
                        'version'     => $version,
                    ));
                }
                break;
            default:
                $this->error = 'Unknown extension type: "'.$type.'"';
                $err = new AError($this->error);
                $err->toLog()->toDebug();
                return false;
                break;
        }

        return true;
    }
    /**
     * @param string $ext_txt_id
     * @return bool
     */
    public function uninstallExtension( $ext_txt_id )
    {
        $ext_txt_id = (string) $ext_txt_id;
        if( !$ext_txt_id ){
            return false;
        }

        $validate = $this->extension_manager->checkDependantsBeforeUninstall( $ext_txt_id );
        if(!$validate){
            $this->error = implode("\n", $this->extension_manager->errors);
            return false;
        }

        $result = $this->extension_manager->uninstall($ext_txt_id, AHelperUtils::getExtensionConfigXml($ext_txt_id));
        if ($result === false) {
            $this->error = implode("\n", $this->extension_manager->errors);
            return false;
        }

        return true;
    }

    /**
     * @param \SimpleXmlElement $config
     */
    public function upgradeCore($config)
    {
        //clear all cache
        $this->cache->remove('*');

        $package_dirname = $this->package_info['package_dir'];
        // running sql upgrade script if it exists
        if ( (string)$config->upgrade->sql ) {
            $file = $package_dirname.'/'.(string)$config->upgrade->sql;
            if (is_file($file)) {
                $this->db->performSql($file);
            }
        }
        // running php upgrade script if it exists
        if ( (string)$config->upgrade->trigger ) {
            $file = $package_dirname.'/'.(string)$config->upgrade->trigger;
            if (is_file($file)) {
                /** @noinspection PhpIncludeInspection */
                include($file);
            }
        }

        // write to history
        $install_upgrade_history = new ADataset('install_upgrade_history', 'admin');
        $install_upgrade_history->addRows(array(
            'date_added'  => date("Y-m-d H:i:s", time()),
            'name'        => 'Core upgrade',
            'version'     => $this->package_info['package_version'],
            'backup_file' => '',
            'backup_date' => '',
            'type'        => 'upgrade',
            'user'        => (is_object( $this->user ) ? $this->user->getUsername() : 'php-cli'),
        ));
    }

    /**
     * @param string $new_version
     *
     * @return bool
     */
    public function updateCoreVersion($new_version)
    {
        if ( ! $new_version) {
            return false;
        }

        $new_version = preg_replace('/[^0-9\.]/', '', $new_version);
        list($master, $minor, $built) = explode(".", $new_version);
        $content = "<?php\nuse abc\core\ABC;\n";
        $content .= "ABC::env('MASTER_VERSION', '".$master."');\n";
        $content .= "ABC::env('MINOR_VERSION', '".$minor."');\n";
        $content .= "ABC::env('VERSION_BUILT', '".$built."');\n";

        if ( ! $this->package_info['ftp']) {
            file_put_contents(ABC::env('DIR_CORE').'version.php', $content);
        } else {
            file_put_contents($this->package_info['tmp_dir'].'version.php', $content);
            $ftp_user = $this->package_info['ftp_user'];
            $ftp_password = $this->package_info['ftp_password'];
            $ftp_port = $this->package_info['ftp_port'];
            $ftp_host = $this->package_info['ftp_host'];

            $fconnect = ftp_connect($ftp_host, $ftp_port);
            ftp_login($fconnect, $ftp_user, $ftp_password);
            ftp_pasv($fconnect, true);

            $this->ftp_move($fconnect,
                $this->package_info['tmp_dir'].'version.php',
                'version.php',
                $this->package_info['ftp_path'].'core/');
            ftp_close($fconnect);
        }

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
            if ( ! chmod($path, $dirmode)) {
                $dirmode_str = decoct($dirmode);
                $error_text = "Notice: Failed applying filemode '".$dirmode_str."' on directory '".$path."'.\n";
                $error_text .= "  `-> the directory '".$path."' will be skipped from recursive chmod.\n";
                $this->log->write($error_text);

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
            if ( ! file_exists($path)) {
                return null;
            }

            if (is_link($path)) {
                $this->log->write('Package manager Notice: Recursive chmod. Symlink '.$path.' is skipped.');
                return null;
            }
            // for index.php do not set 775 permissions because hosting providers will ban it
            if (pathinfo($path, PATHINFO_BASENAME) == 'index.php' && $filemode == 775) {
                $filemode = 644;
            }
            if ( ! chmod($path, $filemode)) {
                $filemode_str = decoct($filemode);
                $this->log->write("Notice: Failed applying filemode ".$filemode_str." on file ".$path."\n");

                return null;
            }
        }
    }

    /**
     * Method of checks before installation process
     */
    public function validate()
    {
        $this->error = '';
        //1.check is extension directory writable
        if ( ! is_writable(ABC::env('DIR_APP_EXTENSIONS'))) {
            $this->error .= 'Directory '.ABC::env('DIR_APP_EXTENSIONS').' is not writable. Please change permissions for it.'."\n";
        }
        //2. check temporary directory. just call method
        $this->getTempDir();

        //3. run validation for backup-process before install
        $bkp = new ABackup('', false);
        if ( ! $bkp->validate()) {
            $this->error .= implode("\n", $bkp->error);
        }

        $this->extensions->hk_ValidateData($this);

        return ($this->error ? false : true);

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
        if (is_dir($tmp_dir) && ! is_dir($tmp_install_dir)) {
            @mkdir($tmp_install_dir, 0755, true);
        }
        //try to create tmp dir if not yet created and install.
        if (AHelperUtils::is_writable_dir($tmp_dir) && AHelperUtils::is_writable_dir($tmp_install_dir)) {
            $dir = $tmp_install_dir."/";
        } else {
            if ( ! is_dir(sys_get_temp_dir().'/abantecart_install')) {
                mkdir(sys_get_temp_dir().'/abantecart_install/', 0775);
            }
            $dir = sys_get_temp_dir().'/abantecart_install/';

            if ( ! is_writable($dir)) {
                $error_text = 'Error: php tried to use directory '
                        .ABC::env('DIR_SYSTEM')."temp/install".' but it is non-writable. Temporary php-directory '
                        .$dir.' is non-writable too! Please change permissions one of them.'."\n";
                $this->error .= $error_text;
                $this->log->write($error_text);
            }
        }

        return $dir;
    }
    // this method calls before installation of package
    public function CleanTempDir()
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
     * @param \SimpleXMLElement|null $config_xml
     *
     * @return bool
     */
    public function checkCartVersion(\SimpleXMLElement $config_xml = null)
    {
        $config_xml = $config_xml === null ? $this->package_info['config'] : $config_xml;
        $full_check = false;
        $minor_check = false;
        $versions = array();
        foreach ($config_xml->cartversions->item as $item) {
            $version = (string)$item;
            if( version_compare($version, '2.0.0', '<') ){
                return false;
            }
            $versions[] = $version;
            $subv_arr = explode('.', preg_replace('/[^0-9\.]/', '', $version));
            $full_check = AHelperUtils::versionCompare(
                                                        $version,
                                                        ABC::env('VERSION'),
                                                        '<='
            );
            $minor_check = AHelperUtils::versionCompare(
                                                        $subv_arr[0].'.'.$subv_arr[1],
                                                        ABC::env('MASTER_VERSION').'.'.ABC::env('MINOR_VERSION'),
                                                        '=='
            );

            if ($full_check && $minor_check) {
                break;
            }
        }

        if ( ! $full_check || ! $minor_check) {
            $this->package_info['confirm_version_incompatibility'] = false;
            $this->package_info['version_incompatibility_text'] = sprintf(
                                                                        $this->language->get('confirm_version_incompatibility'),
                                                                        (ABC::env('VERSION')),
                                                                        implode(', ', $versions)
            );
            $this->package_info['supported_cart_versions'] = $versions;
        }

        return $full_check && $minor_check;
    }

    public function isCorePackage($extension_key = '')
    {
        if ( ! $extension_key) {
            $extension_key = $this->package_info['extension_key'];
        }
        return (strpos($extension_key, 'abantecart_') === 0);
    }
}