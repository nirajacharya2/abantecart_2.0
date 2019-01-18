<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 15/01/2019
 * Time: 13:45
 */

namespace abc\controllers\admin;

use abc\core\ABC;
use abc\core\engine\AController;
use abc\models\base\Audit;

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
        $this->document->addBreadcrumb(array(
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ));
        $this->document->addBreadcrumb(array(
            'href'      => $this->html->getSecureURL('tool/audit_log'),
            'text'      => $heading_title,
            'separator' => ' :: ',
            'current'   => true,
        ));

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
        $auditInstance = new Audit();
        $auditableTypes = $auditInstance->select('auditable_type')
            ->groupBy('auditable_type')
            ->get()->toArray();
        $arResult = [];

        foreach ($auditableTypes as &$auditableType) {
            $arResult['classes'][] = $auditableType['auditable_type'];
            $instance = ABC::getModelObjectByAlias($auditableType['auditable_type']);
            if (get_class($instance)) {
                $arResult[$auditableType['auditable_type']]['table_columns'] = $instance->getTableColumns();
            }
        }

        return json_encode($arResult);
    }
}