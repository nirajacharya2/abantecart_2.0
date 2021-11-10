<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2021 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\core\cache;

use abc\core\helper\AHelperUtils;
use abc\core\lib\AError;
use ReflectionException;

if (!class_exists('abc\core\ABC')) {
    header('Location: static_pages/?forbidden='.basename(__FILE__));
}

/**
 * File cache driver (default)
 *
 */
class ACacheDriverFile extends ACacheDriver implements ACacheDriverInterface
{
    /**
     * Cache directory path
     *
     * @var    string
     */
    protected $path;

    /**
     * Cache security code
     *
     * @var    string
     */
    protected $security_code = "<?php die('Restricted Access!'); ?>#AbanteCart#";

    /**
     * Constructor
     *
     * @param array $config
     * @param int $expiration
     * @param int $lock_time
     *
     */
    public function __construct(array $config, $expiration, $lock_time = 0)
    {
        if (!$lock_time) {
            $lock_time = 10;
        }
        parent::__construct($expiration, $lock_time);
        // note: path with slash at the end!
        $this->path = $config['DIR_CACHE'];
    }

    /**
     * Test to see if the cache directory is writable.
     *
     * @return  boolean
     *
     */
    public function isSupported()
    {
        return is_writable($this->path);
    }

    /**
     * Get cached data from a file by key and group
     *
     * @param string $key The cache data key
     * @param string $group The cache data group
     * @param boolean $check_expire True to verify cache time expiration
     *
     * @return  mixed|false  Boolean false on failure or a cached data string
     *
     * @throws ReflectionException
     */
    public function get($key, $group, $check_expire = true)
    {
        $data = false;
        $path = $this->buildFilePath($key, $group);

        if ($check_expire === false || ($check_expire === true && $this->checkExpire($key, $group) === true)) {
            if (file_exists($path)) {
                $data = @file_get_contents($path);
                if ($data) {
                    // Remove security code line
                    $data = str_replace($this->security_code, '', $data);
                }
            }

            return $data;
        } else {
            return false;
        }
    }

    /**
     * Save data to a file by key and group
     *
     * @param string $key The cache data key
     * @param string $group The cache data group
     * @param string $data The data to store in cache
     *
     * @return  boolean
     * @throws ReflectionException
     */
    public function put($key, $group, $data)
    {
        $path = $this->buildFilePath($key, $group);
        if ($path === false) {
            $err_text = sprintf(
                'Error: Cannot build cache file path for key %s and group %s!',
                $key,
                $group
            );
            $error = new AError($err_text);
            $error->toLog()->toDebug();
            return false;
        }
        $saved = false;

        $data = $this->security_code.$data;
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0775);
            //change permissions by separate call. can be problems with it on some systems
            chmod(dirname($path), 0775);
        }
        $fileopen = @fopen($path, "wb");
        if ($fileopen) {
            $len = strlen($data);
            if (@fwrite($fileopen, $data, $len) !== false) {
                $saved = true;
                //update modification time
                touch($path);
            }
            @fclose($fileopen);
            chmod($path, 0664);
        }

        if ($saved) {
            return true;
        } else {
            //something happen and data was not saved completely, need to remove file and fail.
            if (file_exists($path)) {
                unlink($path);
            }

            return false;
        }
    }

    /**
     * Remove a cached data file by key and group
     *
     * @param string $key The cache data key
     * @param string $group The cache data group
     *
     * @return  boolean
     * @throws ReflectionException
     */
    public function remove($key, $group)
    {
        $path = $this->buildFilePath($key, $group);
        if ($path && is_file($path) && !@unlink($path)) {
            return false;
        }

        return true;
    }

    /**
     * Clean cache for a group provided.
     *
     * @param string $group The cache data group, passed '*' indicate all cache removal
     *
     * @return  boolean
     *
     * @throws ReflectionException
     */
    public function clean($group)
    {
        $return = true;

        if (trim($group) == '*') {
            $dirs = $this->getDirectories($this->path);
            for ($i = 0, $n = count($dirs); $i < $n; $i++) {
                $return |= $this->deleteDirectory($dirs[$i]);
            }
        } else {
            if ($group) {
                if (is_dir($this->path.$group)) {
                    $return = $this->deleteDirectory($this->path.$group);
                }
            }
        }

        return $return;
    }

    /**
     * Delete expired cache data
     *
     * @return  boolean  True on success, false otherwise.
     *
     */
    public function gc()
    {
        $result = true;

        // Files older than lifeTime get deleted from cache
        $files = $this->getFiles($this->path, true, ['.svn', 'CVS', '.DS_Store', '__MACOSX', 'index.php']);
        foreach ($files as $file) {
            $time = @filemtime($file);
            if (($time + $this->expire) < $this->now || empty($time)) {
                if (file_exists($file)) {
                    $result |= @unlink($file);
                }
            }
        }

        return $result;
    }

    /**
     * Lock cached item
     *
     * @param string $key The cache data key
     * @param string $group The cache data group
     * @param integer $locktime Cached item max lock time
     *
     * @return  array
     *
     * @throws ReflectionException
     */
    public function lock($key, $group, $locktime)
    {
        $ret = [];
        $ret['waited'] = false;

        $loops = $this->lock_time * 10;
        if ($locktime) {
            $loops = $locktime * 10;
        }

        $path = $this->buildFilePath($key, $group);
        //consider locked if file does not exist yet
        if (!file_exists($path)) {
            $ret['locked'] = true;
            return $ret;
        }

        $fileopen = @fopen($path, "r+b");
        if ($fileopen) {
            $data_lock = @flock($fileopen, LOCK_EX);
        } else {
            $data_lock = false;
        }

        if ($data_lock === false) {
            $lock_counter = 0;
            // Loop until lock has been released. Limit is set to lock time * 10
            while ($data_lock === false) {
                if ($lock_counter > $loops) {
                    $ret['locked'] = false;
                    $ret['waited'] = true;
                    break;
                }
                usleep(100);
                $data_lock = @flock($fileopen, LOCK_EX);
                $lock_counter++;
            }
        }
        $ret['locked'] = $data_lock;
        return $ret;
    }

    /**
     * Unlock cached item
     *
     * @param string $key The cache data key
     * @param string $group The cache data group
     *
     * @return  boolean
     * @throws ReflectionException
     */
    public function unlock($key, $group = null)
    {
        $path = $this->buildFilePath($key, $group);
        if (!is_file($path)) {
            return true;
        }
        $fileopen = @fopen($path, "r+b");
        if ($fileopen) {
            $ret = @flock($fileopen, LOCK_UN);
        } else {
            // Expect true if $fileopen is false.
            $ret = true;
        }

        return $ret;
    }

    /**
     * Check to make sure cache is still valid, if not, delete it.
     *
     * @param string $key Cache key to expire.
     * @param string $group The cache data group.
     *
     * @return  boolean
     *
     * @throws ReflectionException
     */
    protected function checkExpire($key, $group)
    {
        $path = $this->buildFilePath($key, $group);

        if (file_exists($path)) {
            $time = @filemtime($path);
            if (($time + $this->expire) < $this->now || empty($time)) {
                @unlink($path);
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Get a cache file path from a key/group pair
     *
     * @param string $key The cache data key
     * @param string $group The cache data group
     *
     * @return  string|false   False / The cache file path
     *
     * @throws ReflectionException
     */
    protected function buildFilePath($key, $group)
    {
        $name = $this->hashCacheKey($key, $group);
        $dir = $this->path.$group;

        // If the folder doesn't exist try to create it
        if (!file_exists($dir)) {
            // Make sure the index file is there
            $indexFile = $dir.'/index.php';
            if (mkdir($dir, 0775)) {
                file_put_contents($indexFile, "<?php die('Restricted Access!'); ?>");
                //change permissions by separate call. can be problems with it on some systems
                chmod($dir, 0775);
            }
        }

        // Double check that folder now exists
        if (!file_exists($dir)) {
            $err_text = sprintf(
                    'Error: Cannot create cache folder: %s! ',
                    $dir
                )
                .$this->getPermsAndUserInfo($dir);
            $error = new AError($err_text);
            $error->toLog()->toDebug();
            return false;
        }

        return $dir.'/'.$name.'.php';
    }

    /**
     * Fast delete of a folder with content files
     *
     * @param string $path Full path to the folder to delete.
     *
     * @return  boolean
     * @throws ReflectionException
     */
    protected function deleteDirectory($path)
    {
        if (!$path || !is_dir($path) || empty($this->path)) {
            $err_text = sprintf(
                    'Error: Cannot delete cache folder: %s! Specified folder does not exist.',
                    $path
                )
                .$this->getPermsAndUserInfo($path);
            $error = new AError($err_text);
            $error->toLog()->toDebug();
            return false;
        }

        // Check to make sure path is inside cache folder
        $match = strpos($path, $this->path);
        if ($match === false || $match > 0) {
            $err_text = sprintf(
                    'Error: Cannot delete cache folder: %s! Specified path in not within cache folder.',
                    $path
                )
                .$this->getPermsAndUserInfo($path);
            $error = new AError($err_text);
            $error->toLog()->toDebug();
            return false;
        }

        //check permissions before rename
        if (!AHelperUtils::is_writable_dir($path)) {
            $err_text = sprintf('Error: Cannot delete cache folder: %s! Permission denied.', $path)
                .$this->getPermsAndUserInfo($path);
            $error = new AError($err_text);
            $error->toLog()->toDebug();
            return false;
        }
        //rename folder to prevent recreation by other process
        $new_path = $path.'_trash';
        $renamed = false;
        if (!is_dir($new_path)) {
            if (rename($path, $new_path)) {
                $path = $new_path;
                $renamed = true;
            }
        }

        // Remove all the files in folder if they exist; disable all filtering
        $files = $this->getFiles($path, false, [], []);
        if ($files === false) {
            return false;
        } else {
            foreach ($files as $file) {
                if (@unlink($file) !== true) {
                    //no permissions to delete
                    $filename = basename($file);
                    $err_text = sprintf(
                            'Error: Cannot delete cache file: %s! No permissions to delete.',
                            $filename
                        )
                        .$this->getPermsAndUserInfo($path);
                    $error = new AError($err_text);
                    $error->toLog()->toDebug();
                    return false;
                }
            }
        }

        //one level directories
        $folders = $this->getDirectories($path);
        foreach ($folders as $folder) {
            if (is_link($folder)) {
                //Delete links
                if (@unlink($folder) !== true) {
                    return false;
                }
                //Remove inner folders with recursion
            } else {
                if ($this->deleteDirectory($folder) !== true) {
                    return false;
                }
            }
        }
        $ret = true;
        if ($renamed) {
            if (@rmdir($path)) {
                $ret = true;
            } else {
                $err_text = sprintf(
                        'Error: Cannot delete cache directory: %s! No permissions to delete.',
                        $path
                    )
                    .$this->getPermsAndUserInfo($path);
                $error = new AError($err_text);
                $error->toLog()->toDebug();
                $ret = false;
            }
        }

        return $ret;
    }

    /**
     * Fast files read in provided directory.
     *
     * @param string $path The path of the folder to read.
     * @param mixed $recurse True to recursively search into sub-folders, or an
     *                                   integer to specify the maximum depth.
     * @param array $exclude Array with names of files which should be skipped
     * @param array $exclude_filter Array of folder names to skip
     *
     * @return  array|false    Files in the given folder.
     */
    protected function getFiles(
        $path,
        $recurse = false,
        $exclude = ['.svn', 'CVS', '.DS_Store', '__MACOSX'],
        $exclude_filter = ['^\..*', '.*~']
    ) {
        $ret_arr = [];
        if (!is_dir($path)) {
            return false;
        }

        if (!($handle = @opendir($path))) {
            return [];
        }

        if (count($exclude_filter)) {
            $exclude_filter = '/('.implode('|', $exclude_filter).')/';
        } else {
            $exclude_filter = '';
        }

        while (($file = readdir($handle)) !== false) {
            if (($file != '.') && ($file != '..')
                && (!in_array($file, $exclude))
                && (!$exclude_filter || !preg_match($exclude_filter, $file))
            ) {
                $dir = $path.'/'.$file;
                if (is_dir($dir)) {
                    //process directory
                    if ($recurse) {
                        if (is_int($recurse)) {
                            $arr = $this->getFiles($dir, $recurse - 1);
                        } else {
                            $arr = $this->getFiles($dir, $recurse);
                        }
                        $ret_arr = array_merge($ret_arr, $arr);
                    }
                } else {
                    $ret_arr[] = $path.'/'.$file;
                }
            }
        }
        closedir($handle);

        return $ret_arr;
    }

    /**
     * Read the folders in a directory path.
     *
     * @param string $path The path to directory.
     * @param mixed $recurse True to recursively search into sub-folders, or an integer to specify the maximum depth.
     * @param array $exclude Array with names of folders which should not be shown in the result.
     * @param array $exclude_filter Array with regular expressions
     *                                  matching folders which should not be shown in the result.
     *
     * @return  array|false  with full path sub-directories.
     *
     */
    protected function getDirectories(
        $path,
        $recurse = false,
        $exclude = ['.svn', 'CVS', '.DS_Store', '__MACOSX'],
        $exclude_filter = ['^\..*']
    ) {
        $ret_arr = [];

        if (!is_dir($path)) {
            return false;
        }

        if (!($handle = @opendir($path))) {
            //return nothing
            return $ret_arr;
        }

        if (count($exclude_filter)) {
            $excludefilter_string = '/('.implode('|', $exclude_filter).')/';
        } else {
            $excludefilter_string = '';
        }

        while (($file = readdir($handle)) !== false) {
            if (($file != '.') && ($file != '..')
                && (!in_array($file, $exclude))
                && (empty($excludefilter_string) || !preg_match($excludefilter_string, $file))) {
                $dir = $path.'/'.$file;
                if (is_dir($dir)) {
                    $ret_arr[] = $dir;
                    //recurse if needed
                    if ($recurse) {
                        if (is_int($recurse)) {
                            $arr = $this->getDirectories($dir, $recurse - 1, $exclude, $exclude_filter);
                        } else {
                            $arr = $this->getDirectories($dir, $recurse, $exclude, $exclude_filter);
                        }
                        $ret_arr = array_merge($ret_arr, $arr);
                    }
                }
            }
        }
        closedir($handle);
        return $ret_arr;
    }

    protected function getPermsAndUserInfo($path)
    {
        $posixUser = posix_getpwuid(posix_geteuid());
        return "\n ".substr(sprintf('%o', fileperms($path)), -4)
            .'   '
            .$posixUser['name']
            .":"
            .posix_getgrgid($posixUser['gid'])['name'];
    }
}