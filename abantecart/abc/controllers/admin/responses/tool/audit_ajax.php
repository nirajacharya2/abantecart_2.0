<?php

namespace abc\controllers\admin;

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\lib\AJson;
use abc\core\lib\contracts\AuditLogStorageInterface;
use abc\models\system\Audit;
use abc\models\system\AuditEvent;
use abc\models\system\AuditEventDescription;
use abc\models\system\AuditModel;

class ControllerResponsesToolAuditAjax extends AController
{

    public $data;

    public function main()
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

        if (isset($this->request->get['sortDesc']) && is_array($this->request->get['sortDesc'])) {
            $this->request->get['sortDesc'] = $this->request->get['sortDesc'][0];
        } else {
            $this->request->get['sortDesc'] = 'true';
        }

        if (isset($this->request->get['sortBy']) && is_array($this->request->get['sortBy'])) {
            $this->request->get['sortBy'] = $this->request->get['sortBy'][0];
        } else {
            $this->request->get['sortBy'] = 'date_added';
        }

        if (isset($this->request->get['getDetail'])) {
            $this->getDetail();
            return;
        }

        $this->data['response'] = $auditLogStorage->getEvents($this->request->get);

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data['response']));

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function getDetail()
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

        $this->data['response'] = $auditLogStorage->getEventDetails($this->request->get);

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data['response']));

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

}
