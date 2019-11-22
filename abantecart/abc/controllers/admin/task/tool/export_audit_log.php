<?php

namespace abc\controllers\admin;

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\lib\AJson;
use abc\core\lib\ATaskManager;
use abc\core\lib\contracts\AuditLogStorageInterface;
use abc\core\lib\contracts\ExportTaskController;

class ControllerTaskToolExportAuditLog extends AController implements ExportTaskController
{

    /**
     * @param array $params
     *
     * @return int|void
     * @throws \abc\core\lib\AException
     */
    public function getCount(array $params)
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        /**
         * @var AuditLogStorageInterface $auditLogStorage
         */
        $auditLogStorage = ABC::getObjectByAlias('AuditLogStorage');

        if (!($auditLogStorage instanceof AuditLogStorageInterface)) {
            $this->log->write('Audit log storage not instance of AuditLogStorageInterface, please check classmap.php');
            return false;
        }

        $response = $auditLogStorage->getEvents($params);

        $this->data['response'] = [
            'result' => true,
            'count'  => (int)$response['total'],
        ];
        $this->load->library('json');
        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode($this->data['response'])
        );
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    /**
     * @return mixed|void
     * @throws \abc\core\lib\AException
     */
    public function export()
    {
        /**
         * @var AuditLogStorageInterface $auditLogStorage
         */
        $auditLogStorage = ABC::getObjectByAlias('AuditLogStorage');

        if (!($auditLogStorage instanceof AuditLogStorageInterface)) {
            $this->log->write('Audit log storage not instance of AuditLogStorageInterface, please check classmap.php');
            return false;
        }

        list($taskId, $stepId, $settings) = func_get_args();

        $start = $settings['start'] ?: 0;
        $limit = $settings['limit'] ?: 100;

        $request = $settings['request'];
        $request['rowsPerPage'] = $limit;
        $request['page'] = ceil($start / $limit) + 1;

        $file = $settings['file'];
        if (!$file) {
            $output = [
                'result'  => false,
                'message' => 'Check file for export!',
            ];
            $this->response->setOutput(AJson::encode($output));
            return;
        }

        $output = fopen($file, 'a');

        if ($start === 0) {
            $headers = [
                'User Name',
                'Auditable Object',
                'Auditable Id',
                'Event',
                'Description',
                'Ip Address',
                'Date Change',
                'Model',
                'Field',
                'Old Value',
                'New Value',
            ];
            fputcsv($output, $headers);
        }

        $result = $auditLogStorage->getEventsRaw($request);

        if ($result) {
            foreach ($result['items'] as $row) {
                $data = [
                    'user_name'        => $row['actor']['name'],
                    'auditable_object' => $row['entity']['name'],
                    'auditable_id'     => $row['entity']['id'],
                    'event'            => $row['entity']['group'],
                    'description'      => $row['description'],
                    'ip'       => $row['request']['ip'],
                    'date_added'       => $row['request']['timestamp'],
                ];
                foreach ($row['changes'] as $change) {
                    $data['model'] = $change['groupName'];
                    $data['field'] = $change['name'];
                    $data['oldValue'] = $change['oldValue'];
                    $data['newValue'] = $change['newValue'];
                    fputcsv($output, $data);
                }
            }
        }

        fclose($output);

        $tm = new ATaskManager();
        $tm->updateTask($taskId, [
            'last_result' => '0',
        ]);

        $this->load->library('json');
        $this->response->addJSONHeader();
        if ($result['items']) {
            $tm->updateTask($taskId, [
                'last_result' => '1',
            ]);
            $tm->updateStep($stepId,
                [
                    'last_result' => '1',
                ]);
            $output = [
                'result' => true,
                //'message' => $result->count().' product orders exported.',
            ];
        } else {
            $output = [
                'result'  => false,
                'message' => 'Orders not exported!',
            ];
        }
        $this->response->setOutput(AJson::encode($output));
    }

}