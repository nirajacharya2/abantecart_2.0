<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2022 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/
namespace abc\controllers\admin;

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\engine\ADispatcher;
use abc\core\engine\Registry;
use abc\core\lib\AError;
use abc\core\lib\AJson;
use abc\core\lib\ATaskManager;
use Exception;
use H;
use RuntimeException;

class ControllerResponsesCommonExportTask extends AController
{
    protected $zipFile = '';
    protected $errors = [];
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
        $this->data['output'] = [];
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $task_api_key = $this->config->get('task_api_key');

        if ($this->validate()) {
            $this->exportTaskController = $this->request->get['controller'];
            $task_details = $this->addTask();

            if (is_bool($task_details)) {
                $error = new AError("Create export error: \n Result: " . var_export($task_details, true) . " \n" . implode("\n", $this->errors));
                $error->toLog()->toJSONResponse(
                    'APP_ERROR_402',
                    [
                        'error_text'  => 'Result: ' . var_export($task_details, true) . '  ' . implode("\n", $this->errors),
                        'reset_value' => true,
                    ]
                );
                return;
            } else {
                $task_details['task_api_key'] = $task_api_key;
                $task_details['url'] = ABC::env('HTTPS_SERVER').'task.php';
                $this->data['output']['task_details'] = $task_details;
            }
        } else {
            $error = new AError('Invalid task Data: ' . implode('<br>', $this->errors));
            $error->toLog()->toJSONResponse(
                'VALIDATION_ERROR_406',
                [
                    'error_text'  => implode('<br>', $this->errors),
                    'reset_value' => true,
                ]
            );
            return;
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode($this->data['output']));
    }

    protected function addTask()
    {
        $tm = new ATaskManager();
        $timePerItem = 10;
        $itemsCount = 0;

        try {
            $dd = new ADispatcher(
                $this->exportTaskController.'/getCount',
                [$this->request->get]
            );
            $response = $dd->dispatchGetOutput();

            $json = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $response = $json;
            }

            $result = isset($response['result']) && $response['result'];
            if ($result) {
                $itemsCount = $response['count'] ?? 0;
            } else {
                $this->errors[] = $response['error_text'] ?? '';
            }
        } catch (Exception|\Error $e) {
            $this->errors[] = $e->getMessage();
            Registry::log()->error($e->getMessage());
        }

        if ($itemsCount === 0) {
            return true;
        }

        //1. create new task
        $task_id = $tm->addTask(
            [
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
            ]
        );
        if (!$task_id) {
            $this->errors[] = 'unexpected error during adding of task';
            $this->errors = array_merge($this->errors, $tm->errors);
            return false;
        }

        $limit = (int) $this->request->get['limit'];
        $stepCount = ceil($itemsCount / $limit);

        for ($i = 1; $i <= $stepCount; $i++) {
            $step_id = $tm->addStep(
                [
                    'task_id'            => $task_id,
                    'sort_order'         => 1,
                    'status'             => 1,
                    'last_time_run'      => null,
                    'last_result'        => 0,
                    'max_execution_time' => $timePerItem * $limit,
                    'controller'         => $this->exportTaskController . '/export',
                    'settings'           => [
                        'start'   => $i * $limit - $limit,
                        'limit'   => $limit,
                        'file'    => $this->getExportFile($task_id),
                        'request' => $this->request->get,
                    ],
                ]
            );

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

    protected function validate()
    {
        $this->extensions->hk_ValidateData($this);
        if (!H::has_value($this->request->get['controller'])) {
            $this->errors[] = '"controller" - get param is empty or not exist';
        }

        if (!H::has_value($this->request->get['limit'])) {
            $this->errors[] = '"limit" - get param is empty or not exist';
        }
        if ((int) $this->request->get['limit'] === 0) {
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

        $task_id = (int) $this->request->post['task_id'];
        if (!$task_id) {
            return;
        }

        //check task result
        $tm = new ATaskManager();
        $task_info = $tm->getTaskById($task_id);
        $task_result = $task_info['last_result'];
        if ($task_result) {
            $tm->deleteTask($task_id);
            $resultFile = $this->getExportFile($task_id);
            $tmpFile = $this->getPublicExportFilePath($task_id);
            rename($resultFile, ABC::env('DIR_SYSTEM').$tmpFile);
            @unlink($resultFile);
            $downloadLink = $this->html->getSecureURL('r/common/export_task/downloadFile', '&file='.$tmpFile);
            $result_text = '<a href="'.$downloadLink.'">'
                .$this->language->get('text_export_task_download').'</a>';
        } else {
            $result_text = $this->language->get('text_export_task_failed');
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->addJSONHeader();
        $this->response->setOutput(
            AJson::encode(
                [
                    'result'      => $task_result,
                    'result_text' => $result_text,
                ]
            )
        );
    }

    public function abort()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('common/export_task');

        $task_id = (int) $this->request->post['task_id'];
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
            $error->toJSONResponse(
                'APP_ERROR_402',
                [
                    'error_text'  => $error_text,
                    'reset_value' => true,
                ]
            );
            return;
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->addJSONHeader();
        $this->response->setOutput(
            AJson::encode(
                [
                    'result'      => true,
                    'result_text' => $result_text,
                ]
            )
        );
    }

    public function downloadFile()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if ($this->user->canAccess('tool/backup')) {
            $filename = str_replace(['../', '..\\',], '', $this->request->get['file']);
            //look into temporary directory first, then dig into system/export directory
            $file = is_file(ABC::env('DIR_SYSTEM').$filename) &&  str_starts_with($filename, 'temp')
                ? ABC::env('DIR_SYSTEM').$filename
                : ABC::env('DIR_SYSTEM').'export'.DS.$filename;
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
            $this->dispatch('error/permission');
        }
    }

    private function getExportFile($taskId)
    {
        if (!file_exists(ABC::env('DIR_SYSTEM').'export')) {
            if (!mkdir($concurrentDirectory = ABC::env('DIR_SYSTEM').'export', 0775, true)
                && !is_dir(
                    $concurrentDirectory
                )) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }
        if (!file_exists(ABC::env('DIR_PUBLIC').'resources'.DS.'download'.DS.'export')) {
            if (!mkdir($concurrentDirectory = ABC::env('DIR_PUBLIC').'resources'.DS.'download'.DS.'export', 0775, true)
                && !is_dir($concurrentDirectory)
            ) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }

        return ABC::env('DIR_SYSTEM').'export'.DS.'export_'.$taskId.'.csv';
    }

    private function getPublicExportFilePath($taskId)
    {
        if (!file_exists(ABC::env('DIR_SYSTEM').'temp'.DS.'export')) {
            if (!mkdir($concurrentDirectory = ABC::env('DIR_SYSTEM').'temp'.DS.'export', 0775, true)
                && !is_dir($concurrentDirectory)
            ) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }

        $this->zipFile = 'export_'.$taskId.microtime(true).'.csv';

        return 'temp'.DS.'export'.DS.$this->zipFile;
    }
}