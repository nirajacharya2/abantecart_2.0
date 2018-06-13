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

use abc\core\helper\AHelperUtils;
use abc\core\engine\Registry;

include_once __DIR__.DS.'job_manager_interface.php';

/**
 * Class AJobManager
 *
 * @link http://docs.abantecart.com/pages/developer/jobs_processing.html
 * @property ADB  $db
 * @property ALog $log
 */
class AJobManager implements AJobManagerInterface
{
    protected $registry;
    public $errors = array(); // errors during process
    protected $starter;
    /**
     * @var ALog
     */
    protected $job_log;

    protected $run_log = array();
    /**
     * @var string can be 'simple' or 'detailed'
     */
    protected $log_level = 'simple';
    const STATUS_DISABLED = 0;
    const STATUS_READY = 1;
    const STATUS_RUNNING = 2;
    const STATUS_FAILED = 3;
    const STATUS_SCHEDULED = 4;
    const STATUS_COMPLETED = 5;
    const STATUS_INCOMPLETE = 6;
    /**
     * @const time from job start timestamp we decides job is stuck
     */
    const MAX_EXECUTION_TIME = 86400;

    /**
     * AJobManager constructor.
     *
     * @param Registry $registry
     *
     * @throws \ReflectionException
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
        // who is initiator of process, admin or storefront
        $this->starter = ABC::env('IS_ADMIN') === true ? 1 : 0;
        $this->job_log = ABC::getObjectByAlias('ALog', [['job_log.txt']]);
        $this->db = $registry->get('db');
    }


    /**
     * @param int $job_id
     *
     * @return bool
     */
    /*   public function runJob($job_id)
       {

           $job_id = (int)$job_id;
           $job_info = $this->getJobById($job_id);
           if (!$job_info) {
               $this->toLog('Skipped: job ID '.$job_id.' not found.');

               return false;
           }
           if ($job_info['status'] != self::STATUS_READY) {
               $this->toLog('Skipped: job ID '.$job_id.' it not ready to start. Please change job status.');

               return false;
           }

           $this->updateJobState($job_id, ['status'=>self::STATUS_RUNNING]);
           $this->toLog('Job ID '.$job_id.': state - running.');

           $job_result_status = $this->run($job_info['configuration']);

           $this->toLog('Job ID '.$job_id.': state - finished. Status - '.$job_result_status);

           return $job_result_status;
       }

       protected function run($configuration)
       {
           try {
               require_once(ABC::env(DIR_MODULES) . $configuration['module_filename']);
               $class_name = $configuration['module_class'];
               $module = new $class_name();
               $module_method = $configuration['module_method'];
               $module->{$module_method}($configuration);
               $status = self::STATUS_COMPLETED;
           } catch (AException $e) {
               $this->toLog('Job Run Failed', 0);
               $status = self::STATUS_FAILED;
           }
           return $status;
       }
   */
    /**
     * @param int   $job_id
     * @param array $state
     *
     * @return bool
     */
    protected function updateJobState($job_id, array $state = [])
    {
        $job_id = (int)$job_id;
        if (!$job_id) {
            return false;
        }

        $upd_flds = array(
            'last_result',
            'last_time_run',
            'status',
        );
        $data = array();
        foreach ($upd_flds as $fld_name) {
            if (AHelperUtils::has_value($state[$fld_name])) {
                $data[$fld_name] = $state[$fld_name];
            }
        }

        return $this->updateJob($job_id, $data);
    }

    /**
     * @param string $message
     * @param int    $msg_code - can be 0 - fail, 1 -success
     *
     * @return null
     */
    public function toLog($message, $msg_code = 1)
    {
        if (!$message) {
            return false;
        }

        $this->run_log[] = $message;
        if ($this->job_log) {
            $method = $msg_code ? 'warning' : 'error';
            $this->job_log->{$method}($message);
        }

        return true;
    }

    /**
     * @param array $data                       - array must contains key:
     *                                          'worker' with array as value.
     *                                          for example: 'worker' => [
     *                                          'file'       => $filename,
     *                                          'class'      => $full_class_name,
     *                                          'method'     => $method_name,
     *                                          'parameters' => $mixed_value],
     *                                          'misc' => $some_additional_values
     *
     * @return int
     */
    public function addJob(array $data = array())
    {
        if (!$data) {
            $this->errors[] = 'Error: Can not to create a new background job. Empty data given.';
            return false;
        }

        // check

        $sql = "SELECT *
                FROM ".$this->db->table_name('jobs')."
                WHERE job_name = '".$this->db->escape($data['name'])."'";
        $res = $this->db->query($sql);
        if ($res->num_rows) {
            $this->deleteJob($res->row['job_id']);
            $this->toLog('Error: Job with name "'.$data['name'].'" is already exists. Override!');
        }

        $user_info = AHelperUtils::recognizeUser();
        $user_type = $user_info['user_type'];
        $user_id = $user_info['user_id'];
        $user_name = $user_info['user_name'];

        $configuration = $this->serialize($data['configuration']);
        $sql = "INSERT INTO ".$this->db->table_name('jobs')."
                (`job_name`,
                `actor_type`,
                `actor_id`,
                `actor_name`,
                `status`,
                `configuration`,
                `start_time`,
                `last_time_run`,
                `last_result`,
                `date_added`,
                `date_modified`)
                VALUES ('".$this->db->escape($data['name'])."',
                        '".(int)$user_type."',
                        '".(int)$user_id."',
                        '".$this->db->escape($user_name)."',
                        '".(int)$data['status']."',
                        '".$this->db->escape($configuration)."',
                        '".$this->db->escape($data['start_time'])."',
                        '".$this->db->escape($data['last_time_run'])."',
                        '".(int)$data['last_result']."',
                        NOW(),
                        NOW())";
        $this->db->query($sql);
        $job_id = $this->db->getLastId();
        return $job_id;
    }

    /**
     * @param int   $job_id
     * @param array $data
     *
     * @return bool
     * @throws \Exception
     */
    public function updateJob($job_id, array $data = [])
    {
        $job_id = (int)$job_id;
        if (!$job_id) {
            return false;
        }

        $upd_flds = array(
            'job_name'      => 'string',
            'status'        => 'int',
            'actor_type'    => 'int',
            'actor_id'      => 'int',
            'actor_name'    => 'string',
            'configuration' => 'string',
            'start_time'    => 'timestamp',
            'last_time_run' => 'timestamp',
            'last_result'   => 'int',
        );
        if (!isset($data['actor_name'])) {

            $actor = AHelperUtils::recognizeUser();
            $data['actor_type'] = $actor['user_type'];
            $data['actor_id'] = $actor['user_id'];
            $data['actor_name'] = $actor['user_name'];
        }

        $update = array();
        foreach ($upd_flds as $fld_name => $fld_type) {
            if ($fld_name == 'configuration' && is_array($data[$fld_name])) {
                $data[$fld_name] = $this->serialize($data[$fld_name]);
            }

            if (AHelperUtils::has_value($data[$fld_name])) {
                switch ($fld_type) {
                    case 'int':
                        $value = (int)$data[$fld_name];
                        break;
                    case 'string':
                    case 'timestamp':
                        $value = $this->db->escape($data[$fld_name]);
                        break;
                    default:
                        $value = $this->db->escape($data[$fld_name]);
                }
                $update[] = $fld_name." = '".$value."'";
            }
        }
        if (!$update) { //if nothing to update
            return false;
        }

        $sql = "UPDATE ".$this->db->table_name('jobs')."
                SET ".implode(', ', $update)."
                WHERE job_id = ".(int)$job_id;
        $this->db->query($sql);
        return true;
    }

    /**
     * @param int $job_id
     *
     * @return bool
     */
    public function deleteJob($job_id)
    {
        $job_id = (int)$job_id;
        if (!$job_id) {
            return false;
        }

        $this->db->query(
            "DELETE 
            FROM ".$this->db->table_name('jobs')." 
            WHERE job_id = '".(int)$job_id."'");
        return true;
    }

    /**
     * @param int $job_id
     *
     * @return array
     */
    public function getJobById($job_id)
    {
        $job_id = (int)$job_id;
        if (!$job_id) {
            return array();
        }
        $sql = "SELECT *
                FROM ".$this->db->table_name('jobs')." 
                WHERE job_id = '".$job_id."'";
        $result = $this->db->query($sql);
        $output = $result->row;

        if ($output['configuration']) {
            $output['configuration'] = $this->unserialize($output['configuration']);
        }

        return $output;
    }

    /**
     * @param string $job_name
     *
     * @return array
     */
    public function getJobByName($job_name)
    {
        $job_name = $this->db->escape($job_name);
        if (!$job_name) {
            return array();
        }

        $sql = "SELECT *
                FROM ".$this->db->table_name('jobs')." 
                WHERE job_name = '".$job_name."'";
        $result = $this->db->query($sql);
        $output = $result->row;

        if ($output['configuration']) {
            $output['configuration'] = $this->unserialize($output['configuration']);
        }

        return $output;
    }

    /**
     * @param array $data
     *
     * @return int
     */
    public function getTotalJobs($data = array())
    {
        $sql = "SELECT COUNT(*) as total
                FROM ".$this->db->table_name('jobs');
        $sql .= ' WHERE 1=1 ';

        if (!empty($data['subsql_filter'])) {
            $sql .= " AND ".$data['subsql_filter'];
        }

        if (AHelperUtils::has_value($data['filter']['job_name'])) {
            $sql .= " AND (LCASE(job_name) LIKE '%".$this->db->escape(mb_strtolower($data['filter']['job_name']))."%'";
        }

        $result = $this->db->query($sql);

        return (int)$result->row['total'];
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function getJobs(array $data = [])
    {

        $sql = "SELECT *
                FROM ".$this->db->table_name('jobs')." 
                WHERE 1=1 ";

        if (!empty($data['status'])) {
            $sql .= " AND status = ".(int)$data['status'];
        }

        if (!empty($data['subsql_filter'])) {
            $sql .= " AND ".$data['subsql_filter'];
        }

        if (AHelperUtils::has_value($data['filter']['job_name'])) {
            $sql .= " AND (LCASE(job_name) LIKE '%".$this->db->escape(mb_strtolower($data['filter']['job_name']))."%')";
        }

        $sort_data = array(
            'job_name'      => 'job_name',
            'status'        => 'status',
            'start_time'    => 'start_time',
            'date_modified' => 'date_modified',
        );

        if (isset($data['sort']) && array_key_exists($data['sort'], $sort_data)) {
            $sql .= " ORDER BY ".$sort_data[$data['sort']];
        } else {
            $sql .= " ORDER BY date_modified";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT ".(int)$data['start'].",".(int)$data['limit'];
        }

        $result = $this->db->query($sql);
        $output = $result->rows;
        foreach ($output as &$row) {
            if ($row['configuration']) {
                $row['configuration'] = $this->unserialize($row['configuration']);
            }

            //check is task stuck
            if ($row['status'] == self::STATUS_RUNNING
                && (time() - AHelperUtils::dateISO2Int($row['last_time_run'])) > self::MAX_EXECUTION_TIME
            ) {
                //mark task as stuck
                $row['status'] = -1;
            }
        }
        unset($row);

        return $output;
    }

    /**
     * @return array|bool
     */
    public function getReadyJob()
    {
        $output = [];
        $jobs = $this->getJobs(['status' => self::STATUS_READY, 'limit' => 1]);
        if ($jobs) {
            $output = $jobs[0];
        }

        return $output ? $output : false;
    }

    protected function serialize($value)
    {
        $class_name = ABC::getFullClassName('AJson');
        /**
         * @var AJson $json_lib
         */
        $json_lib = AHelperUtils::getInstance($class_name);
        return $json_lib->encode($value);
    }

    protected function unserialize($value)
    {
        $class_name = ABC::getFullClassName('AJson');
        /**
         * @var AJson $json_lib
         */
        $json_lib = AHelperUtils::getInstance($class_name);
        return $json_lib->decode($value, true);
    }

}
