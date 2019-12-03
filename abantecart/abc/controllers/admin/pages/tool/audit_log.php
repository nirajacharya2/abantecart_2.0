<?php

namespace abc\controllers\admin;

use abc\core\ABC;
use abc\core\engine\AController;
use abc\models\system\Audit;

class ControllerPagesToolAuditLog extends AController
{
    public $data;

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('tool/audit_log');
        $heading_title = $this->language->get('audit_log_heading_title');

        $this->document->setTitle($heading_title);
        $this->data['heading_title'] = $heading_title;

        $this->document->resetBreadcrumbs();
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('tool/audit_log'),
            'text'      => $heading_title,
            'separator' => ' :: ',
            'current'   => true,
        ]);

        if (isset($this->request->get['auditable_type']) && !empty($this->request->get['auditable_type'])) {
            $this->data['auditable_type'] = $this->request->get['auditable_type'];
        }

        if (isset($this->request->get['auditable_id']) && !empty($this->request->get['auditable_id'])) {
            $this->data['auditable_id'] = $this->request->get['auditable_id'];
        }

        if (isset($this->request->get['auditable_fields']) && !empty($this->request->get['auditable_fields'])) {
            $this->data['auditable_fields'] = $this->request->get['auditable_fields'];
        }

        if (isset($this->request->get['modal_mode'])) {
            $this->data['modal_mode'] = 1;
        }

        $this->data['ajax_url'] = $this->html->getSecureURL('r/tool/audit_ajax');

        $this->data['data_objects'] = $this->getDataObjects();

        //TODO: Change load data to ajax object

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/tool/audit_log.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function getDataObjects()
    {
        $auditableTypes = array_keys(ABC::getModelClassMap());
        $arResult = [];

        foreach ($auditableTypes as $auditableType) {
            $arResult['classes'][] = $auditableType;
            $instance = ABC::getModelObjectByAlias($auditableType);
            $arResult[$auditableType]['table_columns'] = [];
            if (is_object($instance) && get_class($instance)) {
                $arResult[$auditableType]['table_columns'] = $instance->getTableColumns();
            }
        }

        return json_encode($arResult);
    }
}
