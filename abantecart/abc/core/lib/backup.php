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
use abc\core\engine\ALoader;
use abc\core\engine\ExtensionsApi;
use abc\core\engine\Registry;
use abc\models\admin\ModelToolBackup;
use DirectoryIterator;
use FilesystemIterator;
use H;

/**
 * Class ABackup
 *
 * @package abc\core\lib
 */
class ABackup
{
    public $errors = [];
    /**
     * @var string - mode of sql dump. can be "data_only" and "recreate"
     */
    public $sql_dump_mode = 'data_only';
    private $backup_name;
    private $backup_dir;
    private $slash;
    /**
     * @var ALog
     */
    private $log;
    /**
     * @var ADB
     */
    private $db;
    /**
     * @var ALoader
     */
    private $load;
    /**
     * @var ModelToolBackup
     */
    private $model_tool_backup;
    /**
     * @var ExtensionsApi
     */
    private $extensions;
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var array
     */
    public $error = [];

    /**
     * @param string $name
     * @param bool   $create_subdirs - sign for creating temp folder for backup. set false if only validate
     */
    public function __construct($name = '', $create_subdirs = true)
    {
        /**
         * @var Registry
         */
        $this->registry = Registry::getInstance();
        $this->log = $this->registry->get('log');
        $this->db = $this->registry->get('db');
        $this->extensions = $this->registry->get('extensions');
        $this->slash = DS;
        if ($name) {
            $result = $this->setBackupName($name);
            if (!$result) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return string
     */
    public function getBackupName()
    {
        return $this->backup_name;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function setBackupName($name)
    {
        $this->backup_name = $name;
        $this->createTempBackupDir();
        return $this->errors ? false : true;
    }

    protected function createTempBackupDir($create_subdirs = true)
    {
        //first of all check backup directory create or set writable permissions
        // Before backup process need to call validate() method! (see below)
        $app_backup_dir = ABC::env('DIR_BACKUP');
        if (!H::is_writable_dir($app_backup_dir)) {
            $this->error[] = 'Backup-directory "'
                .$app_backup_dir
                .'" is not writable or cannot be created! Backup operation is not possible';
        }
        //Add [date] snapshot to the name and validate if archive is already used.
        //Return error if archive can not be created
        //Create a tmp directory with backup name
        //Create subdirectory /files and  /data
        $this->backup_dir = $app_backup_dir.$this->backup_name.'/';

        if (!is_dir($this->backup_dir) && $create_subdirs) {
            $result = mkdir($this->backup_dir, 0775, true);

            if (!$result) {
                $error_text = "Error: Can't create directory ".$this->backup_dir." during backup.";
                $this->log->error($error_text);
                $this->error[] = $error_text;
                $this->backup_dir = $this->backup_name = null;
            }
            chmod($this->backup_dir, 0775);
        }

        if ($this->backup_dir && $create_subdirs) {
            if (!is_dir($this->backup_dir.'files')) {
                mkdir($this->backup_dir.'files');
                chmod($this->backup_dir.'files', 0775);
            }

            if (!is_dir($this->backup_dir.'data')) {
                mkdir($this->backup_dir.'data');
                chmod($this->backup_dir.'data', 0775);
            }
        }
    }

    /**
     * @param array $tables - tables list
     * @param string $dump_file - path of file with sql dump
     *
     * @return bool|string - path of dump file or false
     * @throws AException
     * @throws \DebugBar\DebugBarException
     */
    public function dumpTables($tables = [], $dump_file = '')
    {
        if (!$tables || !is_array($tables) || !$this->backup_dir) {
            $error_text = 'Error: Cannot to dump of tables during sql-dumping. '
                .'Empty table list or unknown destination folder.';
            $error = new AError($error_text);
            $error->toLog()->toDebug();

            return false;
        }

        $table_list = [];
        foreach ($tables as $table) {
            if (!is_string($table)) {
                continue;
            } // clean
            $table_list[] = $this->db->escape($table);
        }

        // use driver directly to exclude hooks calls
        $db_config = ABC::env('DATABASES');
        $db = new ADB($db_config[ABC::env('DB_CURRENT_DRIVER')]);
        $prefix_len = strlen(ABC::env('DB_PREFIX'));
        // get sizes of tables

        $sql = "SELECT TABLE_NAME AS 'table_name',
                    table_rows AS 'num_rows', 
                    (data_length + index_length - data_free) AS 'size'
                FROM information_schema.TABLES
                WHERE information_schema.TABLES.table_schema = '".$this->db->getDatabaseName()."'
                    AND TABLE_NAME IN ('".implode("','", $table_list)."')";
        if ($prefix_len) {
            $sql .= " AND TABLE_NAME like '".$this->db->prefix()."%'";
        }

        $result = $this->db->query($sql);
        $memory_limit = (H::getMemoryLimitInBytes() - memory_get_usage()) / 4;

        // sql-file for small tables

        if (!$dump_file) {
            $dump_file = $this->backup_dir
                .'data'.$this->slash
                .'dump_'.$this->db->getDatabaseName()
                .'_'.date('Y-m-d-His').'.sql';
        }
        $file = fopen($dump_file, 'w');
        if (!$file) {
            $error_text = 'Error: Cannot create file as "'.$dump_file.'" during sql-dumping. Check is it writable.';
            $error = new AError($error_text);
            $error->toLog()->toDebug();

            return false;
        }

        foreach ($result->rows as $table_info) {
            $table_name = $table_info['table_name'];
            if ($this->sql_dump_mode == 'data_only') {
                fwrite($file, "TRUNCATE TABLE `".$table_name."`;\n\n");
            } elseif ($this->sql_dump_mode == 'recreate') {
                $sql = "SHOW CREATE TABLE `".$table_name."`;";
                $r = $db->query($sql);
                $ddl = $r->row['Create Table'];
                fwrite($file, "DROP TABLE IF EXISTS `".$table_name."`;\n\n");
                fwrite($file, $ddl."\n\n");
            }

            //then try to get table data by pagination.
            // to split data by pages use range of values of column that have PRIMARY KEY. NOT LIMIT-OFFSET!!!
            // 1. - get column name with primary key and data type integer
            $sql = "SELECT COLUMN_NAME
                    FROM information_schema.COLUMNS c
                    WHERE c.`TABLE_SCHEMA` = '".$this->db->getDatabaseName()."'
                        AND c.`TABLE_NAME` = '".$table_name."'
                        AND c.`COLUMN_KEY` = 'PRI'
                        AND c.`DATA_TYPE`='int'
                    LIMIT 0,1;";
            $r = $db->query($sql);
            $column_name = $r->row['COLUMN_NAME'];

            $small_table = false;

            if ($column_name) {
                $sql = "SELECT MAX(`".$column_name."`) as max, MIN(`".$column_name."`) as min
                        FROM `".$table_name."`";
                $r = $db->query($sql);
                $column_max = $r->row['max'];
                $column_min = $r->row['min'];
            } else { // if table have no PRIMARY KEY - try to dump it by one pass
                $column_max = $table_info['num_rows'];
                $column_min = 0;
                $small_table = true;
            }
            unset($r);
            // for tables greater than $memory_limit (for ex. if php memory limit 64mb $memory_limit equal 10mb)
            if ($table_info['size'] > $memory_limit && !$small_table) {// for tables greater than 20 MB
                //max allowed rows count for safe fetching
                $limit = 10000;
                //break apart export to prevent memory overflow
                $stop = $column_min + $limit;
                $small_table = false;
            } else { // for small table get data by one pass
                $column_max = $limit = $table_info['num_rows'];
                $stop = $column_min = 0;
                $small_table = true;
            }

            $start = $column_min;

            while ($start < $column_max) {
                if (!$small_table) {
                    $sql = "SELECT *
                             FROM `".$table_name."`
                             WHERE `".$column_name."` >= '".$start."' AND `".$column_name."`< '".$stop."'";
                } else {
                    $sql = "SELECT * FROM `".$table_name."`";
                }
                // dump data with using "INSERT"
                $r = $db->query($sql, true);
                foreach ($r->rows as $row) {
                    $fields = '';
                    $arr_keys = array_keys($row);
                    foreach ($arr_keys as $value) {
                        $fields .= '`'.$value.'`, ';
                    }
                    $values = '';
                    foreach ($row as $value) {
                        $value = str_replace(
                            ["\x00", "\x0a", "\x0d", "\x1a"],
                            ['\0', '\n', '\r', '\Z'],
                            $value
                        );
                        $value = str_replace(["\n", "\r", "\t"], ['\n', '\r', '\t'], $value);
                        $value = str_replace('\\', '\\\\', $value);
                        $value = str_replace('\'', '\\\'', $value);
                        $value = str_replace('\\\n', '\n', $value);
                        $value = str_replace('\\\r', '\r', $value);
                        $value = str_replace('\\\t', '\t', $value);
                        $values .= '\''.$value.'\', ';
                    }
                    fwrite(
                        $file,
                        'INSERT INTO `'.$table_name.'` '
                        .'('.preg_replace('/, $/', '', $fields).') '
                        .'VALUES ('.preg_replace('/, $/', '', $values).');'."\n"
                    );
                }
                unset($r, $sql);
                $start += $limit;
                $stop += $limit;
                if ($small_table) {
                    break;
                }
            }
        }

        fwrite($file, "\n\n");
        fclose($file);
        chmod($dump_file, 0644);

        return $dump_file;
    }

    /**
     * @return bool
     * @throws AException
     * @throws \DebugBar\DebugBarException
     */
    public function dumpDatabase()
    {
        if (!$this->backup_dir) {
            return false;
        }

        $this->load->model('tool/backup');
        $table_list = $this->model_tool_backup->getTables();
        if (!$table_list) {
            $error_text = "Error: Can't create sql dump of database during backup. Cannot obtain table list. ";
            $this->log->error($error_text);
            $this->error[] = $error_text;

            return false;
        }

        if (!$this->dumpTables($table_list)) {
            $error_text = "Error: Can't create sql dump of tables during backup.";
            $this->log->error($error_text);
            $this->error[] = $error_text;

            return false;
        }

        return true;
    }

    /**
     * @param string $table_name
     *
     * @return bool
     * @throws AException
     * @throws \DebugBar\DebugBarException
     */
    public function dumpTable($table_name)
    {
        if (!$this->backup_dir || trim($table_name)) {
            return false;
        }

        $table_name = $this->registry->get('db')->escape($table_name); // for any case

        $backupFile = $this->backup_dir
            .'data/'
            .$this->db->getDatabaseName()
            .'_'.$table_name
            .'_dump_'.date("Y-m-d-H-i-s").'.sql';

        $result = $this->dumpTables($tables = [$table_name], $backupFile);

        if (!$result) {
            $error_text = "Error: Can't create sql dump of database table during backup";
            $this->log->error($error_text);
            $this->error[] = $error_text;

            return false;
        }

        return true;
    }

    /**
     * @param string     $dir_path
     * @param bool|false $remove
     *
     * @return bool
     */
    public function backupDirectory($dir_path, $remove = false)
    {
        if (!$this->backup_dir) {
            return false;
        }

        if (!is_dir($dir_path)) {
            $error_text = "Error: Can't backup directory ".$dir_path.' because is not a directory!';
            $this->log->error($error_text);
            $this->error[] = $error_text;

            return false;
        }

        $path = pathinfo($dir_path, PATHINFO_BASENAME);

        if (!is_dir($this->backup_dir.'files'.$this->slash.$path)) {
            // it need for nested dirs, for example files/extensions
            mkdir($this->backup_dir.'files'.$this->slash.$path, 0775, true);
        }

        if (file_exists($this->backup_dir.'files'.$this->slash.$path)) {
            if ($path) {
                $this->removeDir($this->backup_dir.'files'.$this->slash.$path);  // delete stuck dir
            }
        }

        //check for backup-loop. Do NOT backup of backup-directory!!!
        if (is_int(strpos($dir_path, $this->backup_dir))) {
            return true;
        }
        // also skip cache & logs dir
        if (is_int(strpos($dir_path, ABC::env('CACHE')['stores']['file']['path']))
            || is_int(strpos($dir_path, ABC::env('DIR_LOGS')))
        ) {
            return true;
        }

        if ($remove) {
            $result = rename($dir_path, $this->backup_dir.'files'.$this->slash.$path);
        } else {
            $result = $this->copyDir($dir_path, $this->backup_dir.'files'.$this->slash.$path);
        }

        if (!$result) {
            $error_text = "Error: Can't move directory \""
                .$dir_path
                ." to backup folder \"".$this->backup_dir
                ."files".$this->slash.$path."\" during backup\n";
            if (!is_writable($dir_path)) {
                $error_text .= "Check write permission for directory \"".$dir_path."";
            }
            $this->log->error($error_text);
            $this->error[] = $error_text;

            return false;
        }

        //Copy directory with content to the directory(s) with the same path starting from $this->backup_dir . '/files/'
        // Call $this->backupFile if needed.
        //generate errors: No space on device (log to message as error too), No permissions, Others
        //return Success or failed.
        return true;
    }

    /**
     * @param string    $file_path
     * @param bool|true $remove
     *
     * @return bool
     */
    public function backupFile($file_path, $remove = true)
    {
        if (!$this->backup_dir || !$file_path) {
            return false;
        }
        $base_name = pathinfo($file_path, PATHINFO_BASENAME);
        $path = str_replace(ABC::env('DIR_ROOT'), '', $file_path);
        $path = str_replace($base_name, '', $path);

        if ($path) { //if nested folders presents in path
            if (!file_exists($this->backup_dir.'files'.$this->slash.$path)) {
                // create dir with nested folders
                $result = mkdir($this->backup_dir.'files'.$this->slash.$path, 0775, true);
            } else {
                $result = true;
            }
            if (!$result) {
                $error_text = "Error: Can't create directory "
                    .$this->backup_dir.'files'.$this->slash.$path." during backup";
                $this->log->error($error_text);
                $this->error[] = $error_text;
                return false;
            }
            if (!is_writable($this->backup_dir.'files'.$this->slash.$path)) {
                $error_text = "Error: Directory "
                    .$this->backup_dir.'files'.$this->slash.$path.' is not writable for backup.';
                $this->log->error($error_text);
                $this->error[] = $error_text;
                return false;
            }
        }
        // move file
        if (file_exists($this->backup_dir.'files'.$this->slash.$path.$base_name)) {
            @unlink($this->backup_dir.'files'.$this->slash.$path.$base_name); // delete stuck file
        }
        if ($remove) {
            $result = rename($file_path, $this->backup_dir.'files'.$this->slash.$path.$base_name);
        } else {
            $result = copy($file_path, $this->backup_dir.'files'.$this->slash.$path.$base_name);
        }
        if (!$result) {
            $error_text = "Error: Can't move file "
                .$file_path.' into '
                .$this->backup_dir.'files'.$this->slash.$path.'during backup.';
            $this->log->error($error_text);
            $this->error[] = $error_text;
            return false;
        }
        return true;
    }

    /**
     * @param string $archive_filename
     * @param string $src_dir
     * @param string $filename
     *
     * @return bool
     */
    public function archive($archive_filename, $src_dir, $filename)
    {
        //Archive the backup to ABC::env('DIR_BACKUP'), delete tmp files in directory $this->backup_dir
        //And create record in the database for created archive.
        //generate errors: No space on device (log to message as error too), No permissions, Others
        //return Success or failed.

        H::compressTarGZ($archive_filename, $src_dir.$filename, 1);

        if (!file_exists($archive_filename)) {
            $error_text = 'Error: cannot to pack '.$archive_filename."\n Please see error log for details.";
            $this->error[] = $error_text;
            $log_text = 'Error: cannot to pack '.$archive_filename."\n";
            $this->log->error($log_text);

            return false;
        } else {
            @chmod($archive_filename, 0644);
        }
        //remove source folder after compress
        $this->removeDir($src_dir.$filename);

        return true;
    }

    public function removeBackupDirectory()
    {
        $this->removeDir($this->backup_dir);
    }

    // Future:  1. We will add methods to brows and restore backup.
    //          2. Incremental backup for the database changes.

    /**
     * method removes non-empty directory (use it carefully)
     *
     * @param string $dir
     *
     * @return boolean
     */
    public function removeDir($dir = '')
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $obj) {
                if ($obj != "." && $obj != "..") {
                    @chmod($dir.$this->slash.$obj, 0777);
                    $err = is_dir($dir.$this->slash.$obj)
                        ? $this->removeDir($dir.$this->slash.$obj)
                        : unlink($dir.$this->slash.$obj);
                    if (!$err) {
                        $error_text = "Error: Can't to delete file or directory: '".$dir.$this->slash.$obj."'.";
                        $this->log->error($error_text);
                        $this->error[] = $error_text;

                        return false;
                    }
                }
            }
            reset($objects);
            rmdir($dir);

            return true;
        } else {
            return $dir;
        }
    }

    /**
     * Recursive function for coping of directory with nested
     *
     * @param string $src
     * @param string $dest
     *
     * @return bool
     */
    public function copyDir($src, $dest)
    {
        // If source is not a directory stop processing
        if (!is_dir($src)) {
            return false;
        }
        //prevent recursive copying
        if ($src == $this->backup_dir) {
            return false;
        }

        // If the destination directory does not exist create it
        if (!is_dir($dest)) {
            if (!mkdir($dest)) {
                // If the destination directory could not be created stop processing
                return false;
            }
        }

        // Open the source directory to read in files
        $i = new DirectoryIterator($src);
        foreach ($i as $f) {
            $real_path = $f->getRealPath();
            //skip backup, cache and logs
            if (is_int(strpos($real_path, $this->slash.'backup'))
                || is_int(strpos($real_path, $this->slash.'cache'))
                || is_int(strpos($real_path, $this->slash.'thumbnails'))
                || is_int(strpos($real_path, $this->slash.'logs'))) {
                continue;
            }
            /**
             * @var $f DirectoryIterator
             */
            if ($f->isFile()) {
                copy($real_path, $dest.$this->slash.$f->getFilename());
            } else {
                if (!$f->isDot() && $f->isDir()) {
                    $this->copyDir($real_path, $dest.$this->slash.$f);
                    $this->addEmptyIndexFile($dest.$this->slash.$f);
                }
            }
        }

        $this->addEmptyIndexFile($dest);

        return true;
    }

    private function addEmptyIndexFile($dir)
    {
        //if empty directory - creates new empty file to prevent
        // excluding directory during tar.gz compression via PharData class
        $fi = new FilesystemIterator($dir, FilesystemIterator::SKIP_DOTS);
        $files_count = iterator_count($fi);
        if (!$files_count) {
            touch($dir.$this->slash."index.html");
        }
    }

    /**
     * Method for checks before backup
     */
    public function validate()
    {
        //reset errors array before validation
        $this->error = [];
        //1. check is backup directory is writable
        if (!is_writable(ABC::env('DIR_BACKUP'))) {
            $this->error[] = 'Directory '.ABC::env('DIR_BACKUP')
                .' is non-writable. It is recommended to set write mode for it.';
        }

        //2. check mysql driver
        $sql = "SELECT TABLE_NAME AS 'table_name',
                    table_rows AS 'num_rows', (data_length + index_length - data_free) AS 'size'
                FROM information_schema.TABLES
                WHERE information_schema.TABLES.table_schema = '".$this->db->getDatabaseName()."'";
        $result = $this->db->query($sql, true);
        if ($result === false) {
            $this->error[] = 'Cannot get tables list. Please check privileges of mysql database user.';
        }

        //3. check already created backup directories
        $arr = [
            $this->backup_dir,
            $this->backup_dir."files".$this->slash,
            $this->backup_dir."data".$this->slash,
        ];
        foreach ($arr as $dir) {
            if (is_dir($dir) && !is_writable($dir)) {
                $this->error[] = 'Directory '.$dir.' already exists and it is non-writable.'
                    .' It is recommended to set write mode for it.';
            }
        }

        $this->extensions->hk_ValidateData($this);

        return ($this->error ? false : true);
    }
}
