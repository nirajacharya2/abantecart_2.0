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

namespace abc\models\admin;

use abc\core\ABC;
use abc\core\engine\Model;
use abc\core\lib\ABackup;
use abc\core\lib\ADataset;
use abc\core\lib\AException;
use abc\core\lib\AFormManager;
use abc\core\lib\ALayoutManager;
use H;

/**
 * Class ModelToolBackup
 *
 * @package abc\models\admin
 */
class ModelToolBackup extends Model
{
    public $errors = [];
    public $backup_filename;

    /**
     * @param string $sql
     *
     * @throws \Exception
     */
    public function restore($sql)
    {
        $this->db->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'"); // to prevent auto increment for 0 value of id
        $qr = explode(";\n", $sql);
        foreach ($qr as $sql) {
            $sql = trim($sql);
            if ($sql) {
                $this->db->query($sql);
            }
        }
        $this->db->query("SET SQL_MODE = ''");
    }

    /**
     * @param string $xml_source - xml as string or full filename to xml-file
     * @param string $mode
     *
     * @return bool
     * @throws AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function load($xml_source, $mode = 'string')
    {
        $xml_obj = null;
        if ($mode == 'string') {
            $xml_obj = simplexml_load_string($xml_source);
        } elseif ($mode == 'file') {
            $xml_obj = simplexml_load_file($xml_source);
        }
        if ($xml_obj) {
            $xmlname = $xml_obj->getName();
            if ($xmlname == 'template_layouts') {
                $load = new ALayoutManager();
                $load->loadXML(['xml' => $xml_source]);
            } elseif ($xmlname == 'datasets') {
                $load = new ADataset();
                $load->loadXML(['xml' => $xml_source]);
            } elseif ($xmlname == 'forms') {
                $load = new AFormManager();
                $load->loadXML(['xml' => $xml_source]);
            } else {
                return false;
            }
        } else {
            return false;
        }
        return true;
    }

    /**
     * function returns table list of abantecart
     *
     * @return array|bool
     * @throws \Exception
     */
    public function getTables()
    {
        $table_data = [];
        $prefix_len = strlen($this->db->prefix());

        $query = $this->db->query("SHOW TABLES FROM `".$this->db->getDatabaseName()."`", true);
        if (!$query) {
            $sql = "SELECT TABLE_NAME
					FROM information_schema.TABLES
					WHERE information_schema.TABLES.table_schema = '".$this->db->getDatabaseName()."' ";
            $query = $this->db->query($sql, true);
        }

        if (!$query) {
            return false;
        }

        foreach ($query->rows as $result) {
            $table_name = $result['Tables_in_'.$this->db->getDatabaseName()];
            //if database prefix present - select only abantecart tables. If not - select all
            if ($this->db->prefix() && substr($table_name, 0, $prefix_len) != $this->db->prefix()) {
                continue;
            }
            $table_data[] = $result['Tables_in_'.$this->db->getDatabaseName()];
        }
        return $table_data;
    }

    /**
     * @param array $tables
     * @param bool|true $rl
     * @param bool|false $config
     * @param string $sql_dump_mode
     *
     * @return bool
     * @throws AException
     * @throws \DebugBar\DebugBarException
     * @throws \ReflectionException
     */
    public function backup($tables, $rl = true, $config = false, $sql_dump_mode = 'data_only')
    {

        $bkp = new ABackup('manual_backup'.'_'.date('Y-m-d-H-i-s'));

        if ($bkp->error) {
            return false;
        }

        // do sql dump
        if (!in_array($sql_dump_mode, ['data_only', 'recreate'])) {
            $sql_dump_mode = 'data_only';
        }
        $bkp->sql_dump_mode = $sql_dump_mode;
        $bkp->dumpTables($tables);

        if ($rl) {
            $bkp->backupDirectory(ABC::env('DIR_RESOURCES'), false);
        }
        if ($config) {
            $bkp->backupFile(ABC::env('DIR_ROOT').'/config/config.php', false);
        }
        $result = $bkp->archive(
            ABC::env('DIR_BACKUP').$bkp->getBackupName().'.tar.gz',
            ABC::env('DIR_BACKUP'), $bkp->getBackupName()
        );
        if (!$result) {
            $this->errors = array_merge($this->errors, $bkp->error);
        } else {
            $this->backup_filename = $bkp->getBackupName();
        }

        return $result;
    }

    /**
     * @param string $job_name
     * @param array  $data
     *
     * @return array|bool
     */
    public function createBackupJob($job_name, $data = [])
    {

        if (!$job_name) {
            $this->errors[] = 'Can not to create background job. Empty job name given';
        }

        $job_configuration = ['worker' =>
            [
              'file'   => ABC::env('DIR_WORKERS').'backup.php',
              'class'  => '\abc\modules\workers\ABackupWorker',
              'method' => 'backup'
            ]
        ];

        //create step for table backup
        if ($data['table_list']) {

            //calculate estimate time for dumping of tables
            // get sizes of tables
            $table_list = [];
            foreach ($data['table_list'] as $table) {
                if (!is_string($table)) {
                    continue;
                } // clean
                $table_list[] = $this->db->escape($table);
            }

            $job_configuration['worker']['parameters']['table_list'] = $data['table_list'];
            $job_configuration['worker']['parameters']['sql_dump_mode'] = $data['sql_dump_mode'];
            $job_configuration['worker']['parameters']['backup_name'] = "manual_backup_".date('Ymd_His');
        }

        if ($data['backup_code']) {
            $job_configuration['worker']['parameters']['backup_code'] = true;
        }

        if ($data['backup_content']) {
            $job_configuration['worker']['parameters']['backup_content'] = true;
        }

        //create last step for compressing backup
        if ($data['compress_backup']) {
            $job_configuration['worker']['parameters']['backup_content'] = true;
        }



        $job_info = ['errors' => []];
        try {
            $job_info = H::createJob(
                [
                    'name'          => $job_name,
                    // schedule it!
                    'status'        => 1,
                    'start_time'    => date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d') + 1, date('Y'))),
                    'last_time_run' => '0000-00-00 00:00:00',
                    'last_result'   => '0',
                    'configuration' => $job_configuration
                ]
            );
        } catch(AException $e){
            $this->log->error($e->getMessage().' File:'.$e->getFile().':'.$e->getLine().$e->getTraceAsString());
        }

        if (!$job_info['job_id']) {
            $this->errors = array_merge($this->errors, $job_info['errors']);
            return false;
        }
        return $job_info;

    }

    /**
     * @param array $table_list
     *
     * @return array
     * @throws \Exception
     */
    public function getTableSizes($table_list = [])
    {
        $tables = [];
        foreach ($table_list as $table) {
            if (!is_string($table)) {
                continue;
            } // clean
            $tables[] = $this->db->escape($table);
        }

        $sql = "SELECT TABLE_NAME AS 'table_name',
					table_rows AS 'num_rows', (data_length + index_length - data_free) AS 'size'
				FROM information_schema.TABLES
				WHERE information_schema.TABLES.table_schema = '".$this->db->getDatabaseName()."'
					AND TABLE_NAME IN ('".implode("','", $tables)."')	";
        $result = $this->db->query($sql);
        $output = [];
        foreach ($result->rows as $row) {
            if ($row['size'] > 1048576) {
                $text = round(($row['size'] / 1048576), 1).'Mb';
            } else {
                $text = round($row['size'] / 1024, 1).'Kb';
            }

            $output[$row['table_name']] = [
                'bytes' => $row['size'],
                'text'  => $text,
            ];
        }

        return $output;
    }

    /**
     * @return int
     */
    public function getCodeSize()
    {
        $all_dirs = scandir(ABC::env('DIR_ROOT'));
        $content_dirs = [ // black list
                          '.',
                          '..',
                          'resources',
                          'image',
                          'download',
        ];
        $dirs_size = 0;
        foreach ($all_dirs as $d) {

            //skip content directories
            if (in_array($d, $content_dirs)) {
                continue;
            }
            $item = ABC::env('DIR_ROOT').'/'.$d;
            if (is_dir($item)) {
                $dirs_size += $this->_get_directory_size($item);
            } elseif (is_file($item)) {
                $dirs_size += filesize($item);
            }
        }
        return $dirs_size;
    }

    /**
     * @return int
     */
    public function getContentSize()
    {
        $content_dirs = [ // white list
                          ABC::env('DIR_RESOURCES'),
                          ABC::env('DIR_IMAGES'),
                          ABC::env('DIR_DOWNLOADS'),
        ];
        $dirs_size = 0;
        foreach ($content_dirs as $d) {
            $dirs_size += $this->_get_directory_size($d);
        }
        return $dirs_size;
    }

    /**
     * @param string $dir
     *
     * @return int
     */
    private function _get_directory_size($dir)
    {
        $count_size = 0;
        $count = 0;
        $dir_array = scandir($dir);
        foreach ($dir_array as $key => $filename) {
            //skip backup, cache and logs
            if (is_int(strpos($dir."/".$filename, '/backup'))
                || is_int(strpos($dir."/".$filename, '/cache'))
                || is_int(strpos($dir."/".$filename, '/logs'))
            ) {
                continue;
            }

            if ($filename != ".." && $filename != ".") {
                if (is_dir($dir."/".$filename)) {
                    $new_dir_size = $this->_get_directory_size($dir."/".$filename);
                    $count_size = $count_size + $new_dir_size;
                } else {
                    if (is_file($dir."/".$filename)) {
                        $count_size = $count_size + filesize($dir."/".$filename);
                        $count++;
                    }
                }
            }
        }
        return $count_size;
    }

}
