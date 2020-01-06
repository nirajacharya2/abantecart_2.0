<?php

namespace abc\controllers\admin;

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\engine\ADispatcher;
use abc\core\engine\Registry;
use abc\core\lib\AError;
use abc\core\lib\AJson;
use abc\core\lib\ATaskManager;
use H;
use ReflectionClass;

class ControllerResponsesCommonExportTask extends AController
{
    public $data = [];
    protected $errors;
    /**
     * @var string
     */
    protected $exportTaskController;

    public function main()
    {
    }

    public function buildTask()
    {
        $this->loadLanguage('common/export_task');
        $this->data['output'] = array();
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $task_api_key = $this->config->get('task_api_key');

        if ($this->validate()) {
            $this->exportTaskController = $this->request->get['controller'];
            $task_details = $this->addTask();

            if (!$task_details) {
                $error = new AError("Create export error: \n ".implode(' ', $this->errors));
                return $error->toJSONResponse('APP_ERROR_402',
                    array(
                        'error_text'  => implode(' ', $this->errors),
                        'reset_value' => true,
                    ));
            } else {
                $task_details['task_api_key'] = $task_api_key;
                $task_details['url'] = ABC::env('HTTPS_SERVER').'task.php';
                $this->data['output']['task_details'] = $task_details;
            }

        } else {
            $error = new AError(implode('<br>', $this->errors));
            return $error->toJSONResponse('VALIDATION_ERROR_406',
                array(
                    'error_text'  => implode('<br>', $this->errors),
                    'reset_value' => true,
                ));
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode($this->data['output']));
    }

    private function addTask()
    {
        $tm = new ATaskManager();
        $timePerItem = 10;

        try {
            $dd = new ADispatcher($this->exportTaskController.'/getCount', [$this->request->get]);
            $response = $dd->dispatchGetOutput();
            $json = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $response = $json;
            }
            $itemsCount = 0;
            $result = isset($response['result']) && $response['result'] ? true : false;
            if ($result) {
                $itemsCount = isset($response['count']) ? $response['count'] : 0;
            } else {
                $this->errors[] = isset($response['error_text']) ? $response['error_text'] : '';
            }
        } catch (\Exception $exception)
        {
            Registry::log()->write($exception->getMessage());
        }

        if ($itemsCount === 0) {
            return true;
        }

        //1. create new task
        $task_id = $tm->addTask(
            array(
                'name'               => 'Export to CSV '.date('Y-m-d H:i:s'),
                //admin-side is starter
                'starter'            => 1,
                'created_by'         => $this->user->getId(),
                // schedule it!
                'status'             => 1,
                'start_time'         => date('Y-m-d H:i:s'),
                'last_time_run'      => '0000-00-00 00:00:00',
                'progress'           => '0',
                'last_result'        => '0',
                'run_interval'       => '0',
                'max_execution_time' => $timePerItem * $itemsCount,
            )
        );
        if (!$task_id) {
            $this->errors = array_merge($this->errors, $tm->errors);
            return false;
        }

        $limit = (int)$this->request->get['limit'];
        $stepCount = ceil($itemsCount / $limit);

        for ($i = 1; $i <= $stepCount; $i++) {
            $step_id = $tm->addStep(array(
                'task_id'            => $task_id,
                'sort_order'         => 1,
                'status'             => 1,
                'last_time_run'      => '0000-00-00 00:00:00',
                'last_result'        => '0',
                'max_execution_time' => $timePerItem * $limit,
                'controller'         => $this->exportTaskController.'/export',
                'settings'           => [
                    'start' => $i * $limit - $limit,
                    'limit' => $limit,
                    'file' => $this->getExportFile($task_id),
                    'request' => $this->request->get
                ],
            ));

            if (!$step_id) {
                $this->errors = array_merge($this->errors, $tm->errors);
                return false;
            }
        }

        $task_details = $tm->getTaskById($task_id);
        if ($task_details) {
            return $task_details;
        }

        $this->errors[] = 'Can not to get task details for execution';
        $this->errors = array_merge($this->errors, $tm->errors);
        return false;
    }

    private function validate()
    {
        $this->extensions->hk_ValidateData($this);
        if (!H::has_value($this->request->get['controller'])) {
            $this->errors[] = '"controller" - get param is empty or not exist';
        }

        if (!H::has_value($this->request->get['limit'])) {
            $this->errors[] = '"limit" - get param is empty or not exist';
        }
        if ((int)$this->request->get['limit'] === 0) {
            $this->errors[] = '"limit" - should be greater then 0';
        }

        if (!$this->errors) {
            return true;
        }
        return false;
    }

    public function complete()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('common/export_task');

        $this->exportTaskController = $this->request->get['controller'];

        $task_id = (int)$this->request->post['task_id'];
        if (!$task_id) {
            return null;
        }

        //check task result
        $tm = new ATaskManager();
        $task_info = $tm->getTaskById($task_id);
        $task_result = $task_info['last_result'];
        if ($task_result) {
            $tm->deleteTask($task_id);
            $rt = $this->rt();
            $rt = str_replace('responses/', 'r/', $rt);
            $result_text = '<a href="'.$this->html->getSecureURL($rt.'/downloadFile', '&file=export_'.$task_id.'.csv').'">'.
                $this->language->get('text_export_task_download')
                .'</a>';
        } else {
            $result_text = $this->language->get('text_export_task_failed');
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode(array(
            'result'      => $task_result,
            'result_text' => $result_text,
        ))
        );
    }

    public function abort()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('common/export_task');

        $task_id = (int)$this->request->post['task_id'];
        if (!$task_id) {
            return null;
        }

        //check task result
        $tm = new ATaskManager();
        $task_info = $tm->getTaskById($task_id);

        if ($task_info) {
            $tm->deleteTask($task_id);
            $result_text = $this->language->get('text_export_task_abort');
        } else {
            $error_text = 'Task #'.$task_id.' not found!';
            $error = new AError($error_text);
            return $error->toJSONResponse('APP_ERROR_402',
                array(
                    'error_text'  => $error_text,
                    'reset_value' => true,
                ));
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode(array(
            'result'      => true,
            'result_text' => $result_text,
        ))
        );
    }

    public function downloadFile()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if ($this->user->canAccess('tool/backup')) {
            $filename = str_replace(['../', '..\\',], '', $this->request->get['file']);
            $file = ABC::env('DIR_SYSTEM').'export'.DS.$filename;
            if (file_exists($file)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/x-gzip');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename='.$filename);
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: '.filesize($file));
                if (ob_get_level()) {
                    ob_end_clean();
                }
                ob_end_flush();
                readfile($file);
                exit;
            } else {
                $this->session->data['error'] = 'Error: You Cannot to Download File '
                    .$file.' Because of absent on hard drive.';
                abc_redirect($this->html->getSecureURL('sale/order'));
            }
        } else {
            return $this->dispatch('error/permission');
        }
    }

    private function getExportFile($taskId)
    {
        if (!file_exists(ABC::env('DIR_SYSTEM').'export')) {
            if (!mkdir($concurrentDirectory = ABC::env('DIR_SYSTEM').'export', 0775, true) && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }

        return ABC::env('DIR_SYSTEM').'export'.DS.'export_'.$taskId.'.csv';
    }
}