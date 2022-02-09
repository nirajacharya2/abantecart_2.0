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
use abc\core\engine\ADispatcher;
use abc\core\engine\Registry;
use abc\modules\events\ABaseEvent;
use Exception;
use H;
use ReflectionException;

/**
 * Class ATaskManager
 *
 * @link http://docs.abantecart.com/pages/developer/tasks_processing.html
 * @property ADB  $db
 * @property ALog $log
 */
class ATaskManager
{
    protected $registry;
    public $errors = []; // errors during process
    protected $starter;
    /**
     * @var ALog
     */
    protected $task_log;
    /**
     * @var string - can be 'html' for running task.php directly from browser,
     *                      'ajax' - for running task by ajax-requests and
     *                      'cli' - shell run
     */
    private $mode = 'html';

    protected $run_log = [];
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
     * @param string $mode Can be html or cli. Needed for run log format
     *
     */
    public function __construct($mode = 'html')
    {
        $this->mode = in_array($mode, ['html', 'ajax', 'cli']) ? $mode : 'html';
        $this->registry = Registry::getInstance();
        // who is initiator of process, admin or storefront
        $this->starter = ABC::env('IS_ADMIN') === true ? 1 : 0;
        $this->task_log = ABC::getObjectByAlias('ALog', [['app' => 'task.log']]);
    }

    public function __get($key)
    {
        return $this->registry->get($key);
    }

    public function __set($key, $value)
    {
        $this->registry->set($key, $value);
    }

    public function setRunLogLevel($level = 'simple')
    {
        $this->log_level = $level;
    }

    public function runTasks()
    {
        $this->run_log = [];
        $task_list = $this->getReadyTasks();
        // run loop tasks
        foreach ($task_list as $task) {
            //check interval and skip task
            $this->toLog('Task_id: '.$task['task_id']." state - running.");
            if ($task['interval'] > 0
                && (time() - H::dateISO2Int($task['last_time_run']) >= $task['interval']
                    || is_null($task['last_time_run']))) {
                $this->toLog('Task_id: '.$task['task_id'].' skipped.');
                continue;
            }
            $task_settings = unserialize($task['settings']);

            $this->runSteps($task['task_id'], $task_settings);
            $this->detectAndSetTaskStatus($task['task_id']);
            $this->toLog('Task_id: '.$task['task_id'].' state - finished.');
        }
    }

    /**
     * @param int $task_id
     *
     * @return bool
     * @throws Exception
     */
    public function runTask($task_id)
    {

        $task_id = (int)$task_id;
        $task = $this->getReadyTasks($task_id);
        if (!$task) {
            return false;
        }

        $this->toLog('Task_id: '.$task_id.' state - running.');

        //check interval and skip task
        //check if task ran first time or
        if ($task['interval'] > 0
            && (is_null($task['last_time_run']
                || time() - H::dateISO2Int($task['last_time_run']) >= $task['interval']))
        ) {
            $this->toLog('Warning: task_id '.$task_id.' skipped. Task interval.');
            return false;
        }

        $task_settings = unserialize($task['settings']);
        //call event
        H::event(__CLASS__.'@runTaskPre', [new ABaseEvent($task_id, $task_settings)]);

        $task_result = $this->runSteps($task_id, $task_settings);
        $this->detectAndSetTaskStatus($task_id);
        //call event
        H::event(__CLASS__.'@runTaskPost', [new ABaseEvent($task_id, $task_settings)]);
        $this->toLog('Task_id: '.$task_id.' state - finished.');
        return $task_result;
    }

    /**
     * @param int $task_id
     *
     * @return array
     * @throws Exception
     */
    private function getReadyTasks($task_id = 0)
    {
        $task_id = (int)$task_id;
        //get list only ready tasks for needed start-side (sf, admin or both)
        $sql = "SELECT *
                FROM ".$this->db->table_name('tasks')." t
                WHERE t.status = ".self::STATUS_READY."
                    AND t.starter IN ('".$this->starter."','2')
                    ".($task_id ? " AND t.task_id = ".$task_id : '');
        $result = $this->db->query($sql);
        return $task_id ? $result->row : $result->rows;
    }

    /**
     * @param int $task_id
     * @param int $step_id
     *
     * @return bool
     * @throws Exception
     */
    public function canStepRun($task_id, $step_id)
    {
        $task_id = (int)$task_id;
        $step_id = (int)$step_id;
        if (!$step_id || !$task_id) {
            return false;
        }

        $all_steps = $this->getTaskSteps($task_id);
        if (!$all_steps) {
            return false;
        }
        foreach ($all_steps as $step) {
            if ($step['step_id'] == $step_id) {
                break;
            }
            //do not allow run step if previous step failed and interrupted task
            if ($step['status'] == self::STATUS_FAILED && $step['settings']['interrupt_on_step_fault'] === true) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $task_id
     * @param $step_id
     *
     * @return bool
     * @throws Exception
     */
    public function isLastStep($task_id, $step_id)
    {
        $task_id = (int)$task_id;
        $step_id = (int)$step_id;
        if (!$step_id || !$task_id) {
            $this->toLog('Error: Tried to check is step_id: '.$step_id.' of task_id: '.$task_id." last, but fail!");
            return false;
        }

        $all_steps = $this->getTaskSteps($task_id);
        if (!$all_steps) {
            $this->toLog('Error: Tried to check is step_id: '.$step_id.' of task_id: '.$task_id
                ." last, but steps list empty!");
            return false;
        }

        $last_step = array_pop($all_steps);
        if ($last_step['step_id'] == $step_id && $last_step['task_id'] == $task_id) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param array $step_details
     *
     * @return bool
     * @throws ReflectionException
     * @throws AException
     */
    public function runStep($step_details)
    {

        $task_id = (int)$step_details['task_id'];
        $step_id = (int)$step_details['step_id'];
        if (!$step_id || !$task_id) {
            return false;
        }

        //change status to active
        $this->updateStepState(
            $step_id,
            [
                'last_time_run' => date('Y-m-d H:i:s'),
                //change status of step to "running" while it run
                'status'        => self::STATUS_READY,
            ]
        );

        //call event
        H::event(__CLASS__.'@runStepPre', [new ABaseEvent($task_id, $step_id)]);

        $response_message = '';
        try {
            $dd = new ADispatcher(
                $step_details['controller'],
                [
                  'task_id' => $task_id,
                  'step_id' => $step_id,
                  'settings' => $step_details['settings']
                ]
            );

            // waiting for result array from step's controller
            $response = $dd->dispatchGetOutput();

            //check is result have json-formatted string
            $json = json_decode($response, true);
            if (json_last_error() == JSON_ERROR_NONE) {
                $response = $json;
            }
            $result = (isset($response['result']) && $response['result']);
            if ($result) {
                $response_message = $response['message'] ?? '';
            } else {
                $response_message = $response['error_text'] ?? '';
            }
        } catch (AException $e) {
            $this->log->write($e);
            $result = false;
        }

        $this->updateStepState(
            $step_id,
            [
                'result' => (int)$result,
                'status' => ($result ? self::STATUS_COMPLETED : self::STATUS_FAILED),
            ]
        );

        //call event
        H::event(__CLASS__.'@runStepPost', [new ABaseEvent($task_id, $step_id, $result, $step_details)]);

        if (!$result) {
            //write to AbanteCart log
            $error_msg = 'Task_id: '.$task_id.' : step_id: '.$step_id.' - Failed. '.$response_message;
            $this->log->write($error_msg."\n step details:\n".var_export($step_details, true));
            //write to task log
            $this->toLog($error_msg, 0);
        } else {
            //write to task log
            $this->toLog('Task_id: '.$task_id.' : step_id: '.$step_id.'. '.$response_message, 1);
        }
        return $result;
    }

    /**
     * @param int $task_id
     *
     * @throws Exception
     */
    public function detectAndSetTaskStatus($task_id)
    {
        $all_steps = $this->getTaskSteps($task_id);
        $completed_cnt = 0;
        $task_status = 0;
        foreach ($all_steps as $step) {
            if (!$step['status']) {
                continue;
            }
            //if one step failed - task failed
            if ($step['status'] == self::STATUS_FAILED) {
                $task_status = self::STATUS_FAILED;
                break;
            }
            if ($step['status'] == self::STATUS_COMPLETED) {
                $completed_cnt++;
            }
        }

        if (!$task_status) {
            if ($completed_cnt == sizeof($all_steps)) {
                $task_status = self::STATUS_COMPLETED;
            } else {
                $task_status = self::STATUS_INCOMPLETE;
            }
        }

        $this->updateTask($task_id, ['status' => $task_status]);
    }

    /**
     * @param int $task_id
     * @param array $task_settings - for future. it can be "reference" for callback
     *
     * @return bool
     * @throws ReflectionException
     * @throws AException
     */
    private function runSteps($task_id, $task_settings)
    {
        $task_id = (int)$task_id;
        if (!$task_id) {
            return false;
        }

        $this->updateTaskState(
            $task_id,
            [
                'status'        => self::STATUS_RUNNING,
                'last_time_run' => date('Y-m-d H:i:s'),
            ]
        );

        //get steps
        $steps = $this->getReadyTaskSteps($task_id);
        $task_result = true;
        // total count of steps to calculate percentage (for future)
        $steps_count = sizeof($steps);
        $k = 0;
        foreach ($steps as $step_details) {
            $step_result = $this->runStep($step_details);
            if (!$step_result) {
                $task_result = false;
                //interrupt task process when step failed
                if ($step_details['interrupt_on_step_fault'] === true) {
                    break;
                }
            }
            $this->updateTaskState($task_id, ['progress' => ceil($k * 100 / $steps_count)]);
            $k++;
        }

        return $task_result;
    }

    /**
     * @param int $task_id
     * @param array $state
     *
     * @return bool
     * @throws Exception
     */
    protected function updateTaskState($task_id, $state = [])
    {
        $task_id = (int)$task_id;
        if (!$task_id) {
            return false;
        }

        $upd_flds = [
            'last_result',
            'last_time_run',
            'status',
            'progress',
        ];
        $data = [];
        foreach ($upd_flds as $fld_name) {
            if (H::has_value($state[$fld_name])) {
                $data[$fld_name] = $state[$fld_name];
            }
        }
        return $this->updateTask($task_id, $data);
    }

    /**
     * @param int $step_id
     * @param array $state
     *
     * @return bool
     * @throws Exception
     */
    protected function updateStepState($step_id, $state = [])
    {
        $upd_flds = [
            'task_id',
            'last_result',
            'last_time_run',
            'status',
        ];
        $data = [];
        foreach ($upd_flds as $fld_name) {
            if (H::has_value($state[$fld_name])) {
                $data[$fld_name] = $state[$fld_name];
            }
        }
        return $this->updateStep($step_id, $data);
    }

    /**
     * @param     $message
     * @param int $msg_code - can be 0 - fail, 1 -success
     *
     * @return null
     */
    public function toLog($message, $msg_code = 1)
    {
        if (!$message) {
            return false;
        }
        if ($this->mode == 'html') {
            $this->run_log[] = '<i style="color: '.($msg_code ? 'green' : 'red').'">'.$message."</i>";
        } else {
            $this->run_log[] = $message;
        }
        $this->task_log?->write($message);

        return true;
    }

    /**
     * @param array $data
     *
     * @return int
     * @throws Exception
     */
    public function addTask($data = [])
    {
        if (!$data) {
            $this->errors[] = 'Error: Can not to create task. Empty data given.';
            return false;
        }
        // check
        $sql = "SELECT *
                FROM ".$this->db->table_name('tasks')."
                WHERE name = '".$this->db->escape($data['name'])."'";
        $res = $this->db->query($sql);
        if ($res->num_rows) {
            $this->deleteTask($res->row['task_id']);
            $this->toLog('Error: Task with name "'.$data['name'].'" is already exists. Override!');
        }

        $sql = "INSERT INTO ".$this->db->table_name('tasks')."
                (`name`,
                `starter`,
                `status`,
                `start_time`,
                `last_time_run`,
                `progress`,
                `last_result`,
                `run_interval`,
                `max_execution_time`,
                `date_added`)
                VALUES ('".$this->db->escape($data['name'])."',
                        '".(int)$data['starter']."',
                        '".(int)$data['status']."',
                        '".$this->db->escape($data['start_time'])."',
                        '".$this->db->escape($data['last_time_run'])."',
                        '".(int)$data['progress']."',
                        '".(int)$data['last_result']."',
                        '".(int)$data['run_interval']."',
                        '".(int)$data['max_execution_time']."',
                        NOW())";
        $this->db->query($sql);
        $task_id = $this->db->getLastId();
        if (H::has_value($data['created_by']) || H::has_value($data['settings'])) {
            $this->updateTaskDetails($task_id, $data);
        }
        return $task_id;
    }

    /**
     * @param       $task_id
     * @param array $data
     *
     * @return bool
     * @throws Exception
     */
    public function updateTask($task_id, $data = [])
    {
        $task_id = (int)$task_id;
        if (!$task_id) {
            return false;
        }

        $upd_flds = [
            'name'               => 'string',
            'starter'            => 'int',
            'status'             => 'int',
            'start_time'         => 'timestamp',
            'last_time_run'      => 'timestamp',
            'progress'           => 'int',
            'last_result'        => 'int',
            'run_interval'       => 'int',
            'max_execution_time' => 'int',
            'date_modified'      => 'timestamp',
        ];
        $update = [];
        foreach ($upd_flds as $fld_name => $fld_type) {
            if (H::has_value($data[$fld_name])) {
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

        $sql = "UPDATE ".$this->db->table_name('tasks')."
                SET ".implode(', ', $update)."
                WHERE task_id = ".(int)$task_id;
        $this->db->query($sql);

        if (H::has_value($data['created_by']) || H::has_value($data['settings'])) {
            $this->updateTaskDetails($task_id, $data);
        }
        return true;
    }

    /**
     * function insert or update task details
     *
     * @param int $task_id
     * @param array $data
     *
     * @return bool
     * @throws Exception
     */
    public function updateTaskDetails($task_id, $data = [])
    {
        $task_id = (int)$task_id;
        if (!$task_id) {
            return false;
        }

        if (gettype($data['settings']) != 'string') {
            $data['settings'] = serialize($data['settings']);
        }

        $sql = "SELECT *
                FROM ".$this->db->table_name('task_details')."
                WHERE task_id = ".$task_id;
        $result = $this->db->query($sql);
        if ($result->num_rows) {
            foreach ($result->row as $k => $ov) {
                if (!H::has_value($data[$k])) {
                    $data[$k] = $ov;
                }
            }
            $sql = "UPDATE ".$this->db->table_name('task_details')."
                    SET settings = '".$this->db->escape($data['settings'])."'
                    WHERE task_id = ".$task_id;
        } else {
            $data['created_by'] = $data['created_by'] ?? 1;
            $sql = "INSERT INTO ".$this->db->table_name('task_details')."
                    (task_id, created_by, settings, date_modified)
                     VALUES (   '".$task_id."',
                                '".$this->db->escape($data['created_by'])."',
                                '".$this->db->escape($data['settings'])."',
                                NOW())";
        }
        $this->db->query($sql);
        return true;
    }

    /**
     * @param array $data
     *
     * @return bool|int
     * @throws Exception
     */
    public function addStep($data = [])
    {
        if (!$data) {
            $this->errors[] = "Error: Can not to create task's step. Empty data given.";
            return false;
        }
        $data['settings'] = !is_string($data['settings']) ? serialize($data['settings']) : $data['settings'];
        $sql = "INSERT INTO ".$this->db->table_name('task_steps')."
                (`task_id`,
                `sort_order`,
                `status`,
                `last_time_run`,
                `last_result`,
                `max_execution_time`,
                `controller`,
                `settings`,
                `date_modified`)
                VALUES (
                        '".(int)$data['task_id']."',
                        '".(int)$data['sort_order']."',
                        '".(int)$data['status']."',
                        '".$this->db->escape($data['last_time_run'])."',
                        '".(int)$data['last_result']."',
                        '".(int)$data['max_execution_time']."',
                        '".$this->db->escape($data['controller'])."',
                        '".$this->db->escape($data['settings'])."',
                        NOW())";
        $this->db->query($sql);
        return $this->db->getLastId();
    }

    /**
     * @param int $step_id
     * @param array $data
     *
     * @return bool
     * @throws Exception
     */
    public function updateStep($step_id, $data = [])
    {
        $step_id = (int)$step_id;
        if (!$step_id) {
            return false;
        }

        $upd_flds = [
            'task_id'            => 'int',
            'starter'            => 'int',
            'status'             => 'int',
            'sort_order'         => 'int',
            'last_time_run'      => 'timestamp',
            'last_result'        => 'int',
            'max_execution_time' => 'int',
            'controller'         => 'string',
            'settings'           => 'string',
            'date_modified'      => 'timestamp',
        ];
        $update = [];
        foreach ($upd_flds as $fld_name => $fld_type) {
            if (H::has_value($data[$fld_name])) {
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

        $sql = "UPDATE ".$this->db->table_name('task_steps')."
                SET ".implode(', ', $update)."
                WHERE step_id = ".(int)$step_id;
        $this->db->query($sql);
        return true;
    }

    /**
     * @param int $task_id
     *
     * @throws Exception
     */
    public function deleteTask($task_id)
    {
        $sql = [];
        $sql[] = "DELETE FROM ".$this->db->table_name('task_steps')." WHERE task_id = '".(int)$task_id."'";
        $sql[] = "DELETE FROM ".$this->db->table_name('task_details')." WHERE task_id = '".(int)$task_id."'";
        $sql[] = "DELETE FROM ".$this->db->table_name('tasks')." WHERE task_id = '".(int)$task_id."'";
        foreach ($sql as $q) {
            $this->db->query($q);
        }
        //call event
        H::event(__CLASS__.'@deleteTask', [new ABaseEvent($task_id)]);
    }

    /**
     * @param int $step_id
     *
     * @throws Exception
     */
    public function deleteStep($step_id)
    {
        $sql = "DELETE FROM ".$this->db->table_name('task_steps')." WHERE step_id = '".(int)$step_id."'";
        $this->db->query($sql);
    }

    /**
     * @param int $task_id
     *
     * @return array
     * @throws Exception
     */
    public function getTaskById($task_id)
    {
        $task_id = (int)$task_id;
        if (!$task_id) {
            return [];
        }
        $sql = "SELECT *
                FROM ".$this->db->table_name('tasks')." t
                LEFT JOIN ".$this->db->table_name('task_details')." td 
                    ON td.task_id = t.task_id
                WHERE t.task_id = '".$task_id."'";
        $result = $this->db->query($sql);
        $output = $result->row;
        if ($output) {
            $output['steps'] = $this->getTaskSteps($output['task_id']);
        }

        if ($output['settings']) {
            $output['settings'] = unserialize($output['settings']);
        }

        return $output;
    }

    /**
     * @param string $task_name
     *
     * @return array
     * @throws Exception
     */
    public function getTaskByName($task_name)
    {
        $task_name = $this->db->escape($task_name);
        if (!$task_name) {
            return [];
        }

        $sql = "SELECT *
                FROM ".$this->db->table_name('tasks')." t
                LEFT JOIN ".$this->db->table_name('task_details')." td 
                    ON td.task_id = t.task_id
                WHERE t.name = '".$task_name."'";
        $result = $this->db->query($sql);
        $output = $result->row;
        if ($output) {
            $output['steps'] = $this->getReadyTaskSteps($output['task_id']);
        }

        if ($output['settings']) {
            $output['settings'] = unserialize($output['settings']);
        }

        return $output;
    }

    /**
     * @param int $task_id
     *
     * @return array
     * @throws Exception
     */
    public function getTaskSteps($task_id)
    {
        $task_id = (int)$task_id;
        if (!$task_id) {
            return [];
        }
        $output = [];
        try {
            $sql = "SELECT *
                FROM ".$this->db->table_name('task_steps')."
                WHERE task_id = ".$task_id."
                ORDER BY sort_order";
            $result = $this->db->query($sql);
            $memory_limit = H::getMemoryLimitInBytes();
            foreach ($result->rows as $row) {
                $used = memory_get_usage();
                if ($memory_limit - $used <= 204800) {
                    $this->log->write(
                        'Error: Task Manager Memory overflow! To Get all Steps of '
                        .'Task you should to increase memory_limit_size in your php.ini'
                    );
                }
                $row['settings'] = $row['settings'] ? unserialize($row['settings']) : '';
                $output[(string)$row['step_id']] = $row;
            }
        } catch (AException $e) {
            $this->log->write(
                'Error: Task Manager Memory overflow! To Get all Steps of Task '
                .'you should to increase memory_limit_size in your php.ini'
            );
        }
        return $output;
    }

    /**
     * @param int $task_id
     * @param int $step_id
     *
     * @return array
     * @throws Exception
     */
    public function getTaskStep($task_id, $step_id)
    {
        $task_id = (int)$task_id;
        $step_id = (int)$step_id;
        if (!$task_id || !$step_id) {
            return [];
        }

        $sql = "SELECT *
                FROM ".$this->db->table_name('task_steps')."
                WHERE task_id = ".$task_id." AND step_id = ".$step_id;
        $result = $this->db->query($sql);
        $output = $result->row;
        if ($output) {
            $output['settings'] = $output['settings'] ? unserialize($output['settings']) : '';
        }
        return $output;
    }

    /**
     * @param int $task_id
     *
     * @return array
     * @throws Exception
     */
    public function getReadyTaskSteps($task_id)
    {
        $task_id = (int)$task_id;
        if (!$task_id) {
            return [];
        }

        $all_steps = $this->getTaskSteps($task_id);
        $steps = [];
        foreach ($all_steps as $step) {
            //skip all steps that not scheduled
            if ($step['status'] != self::STATUS_READY) {
                continue;
            }
            $steps[$step['step_id']] = $step;
        }
        return $steps;
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws Exception
     */
    public function getTotalTasks($data = [])
    {
        $sql = "SELECT COUNT(*) as total
                FROM ".$this->db->table_name('tasks');
        $sql .= ' WHERE 1=1 ';

        if (!empty($data['subsql_filter'])) {
            $sql .= " AND ".$data['subsql_filter'];
        }

        if (H::has_value($data['filter']['name'])) {
            $sql .= " AND (LCASE(t.name) LIKE '%".$this->db->escape(mb_strtolower($data['filter']['name']))."%'";
        }

        $result = $this->db->query($sql);
        return $result->row['total'];
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws Exception
     */
    public function getTasks($data = [])
    {

        $sql = "SELECT td.*, t.*
                FROM ".$this->db->table_name('tasks')." t
                LEFT JOIN ".$this->db->table_name('task_details')." td 
                    ON td.task_id = t.task_id
                WHERE 1=1 ";

        if (!empty($data['subsql_filter'])) {
            $sql .= " AND ".$data['subsql_filter'];
        }

        if (H::has_value($data['filter']['name'])) {
            $sql .= " AND (LCASE(t.name) LIKE '%".$this->db->escape(mb_strtolower($data['filter']['name']))."%')";
        }

        $sort_data = [
            'name'          => 't.name',
            'status'        => 't.status',
            'start_time'    => 't.start_time',
            'date_modified' => 't.date_modified',
        ];

        if (isset($data['sort']) && array_key_exists($data['sort'], $sort_data)) {
            $sql .= " ORDER BY ".$sort_data[$data['sort']];
        } else {
            $sql .= " ORDER BY t.date_modified";
        }

        if ($data['order'] == 'DESC') {
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
            if ($row['settings']) {
                $row['settings'] = unserialize($row['settings']);
            }

            //check is task stuck
            if ($row['status'] == self::STATUS_RUNNING
                && $row['max_execution_time'] > 0
                && (time() - H::dateISO2Int($row['last_time_run'])) > $row['max_execution_time']
            ) {
                //mark task as stuck
                $row['status'] = -1;
            }
        }
        unset($row);

        return $output;
    }

    public function getRunLog()
    {
        return $this->run_log;
    }
}