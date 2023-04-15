<?php

namespace abc\controllers\admin;

use abc\core\engine\AController;
use abc\core\engine\AForm;

class ControllerPagesTotalIncentiveTotal extends AController
{
    public $error = [];
    private $fields = [
        'incentive_total_status',
        'incentive_total_sort_order',
        'incentive_total_calculation_order',
        'incentive_total_total_type',
    ];

    public function main()
    {

        $this->loadModel('setting/setting');
        $this->loadLanguage('extension/total');
        $this->loadLanguage('incentive/incentive');

        if ($this->request->is_POST() && ($this->validate())) {
            $this->model_setting_setting->editSetting('incentive_total', $this->request->post);
            $this->session->data['success'] = $this->language->get('incentive_total_text_success');
            abc_redirect($this->html->getSecureURL('total/incentive_total'));
        }

        $this->document->setTitle($this->language->get('total_name'));

        if (isset($this->error['warning'])) {
            $this->data['error_warning'] = $this->error['warning'];
        } else {
            $this->data['error_warning'] = '';
        }
        $this->data['success'] = $this->session->data['success'];
        if (isset($this->session->data['success'])) {
            unset($this->session->data['success']);
        }

        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('extension/total'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('total/incentive_total'),
            'text'      => $this->language->get('total_name'),
            'separator' => ' :: ',
            'current'   => true,
        ]);

        foreach ($this->fields as $f) {
            if (isset ($this->request->post [$f])) {
                $this->data [$f] = $this->request->post [$f];
            } else {
                $this->data [$f] = $this->config->get($f);
            }
        }

        $this->data ['action'] = $this->html->getSecureURL('total/incentive_total');
        $this->data ['cancel'] = $this->html->getSecureURL('extension/total');
        $this->data ['heading_title'] = $this->language->get('text_edit') . $this->language->get('total_name');
        $this->data ['form_title'] = $this->language->get('heading_title');
        $this->data ['update'] = $this->html->getSecureURL('listing_grid/total/update_field', '&id=incentive_total');

        $form = new AForm ('HS');
        $form->setForm(
            [
                'form_name' => 'editFrm',
                'update'    => $this->data ['update'],
            ]
        );

        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'editFrm',
                'action' => $this->data ['action'],
                'attr'   => 'confirm-exit="true" class="aform form-horizontal"',
            ]
        );
        $this->data['form']['submit'] = $form->getFieldHtml(
            [
                'type' => 'button',
                'name' => 'submit',
                'text' => $this->language->get('button_save'),
            ]);
        $this->data['form']['cancel'] = $form->getFieldHtml(
            [
                'type' => 'button',
                'name' => 'cancel',
                'text' => $this->language->get('button_cancel'),
            ]);

        $this->data['form']['fields']['status'] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'incentive_total_status',
            'value' => $this->data['incentive_total_status'],
            'style' => 'btn_switch',
        ]);

        $this->data['form']['fields']['total_type'] = $form->getFieldHtml([
            'type'  => 'hidden',
            'name'  => 'incentive_total_total_type',
            'value' => 'incentive',
        ]);
        $this->data['form']['fields']['sort_order'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'incentive_total_sort_order',
            'value' => $this->data['incentive_total_sort_order'],
        ]);
        $this->data['form']['fields']['calculation_order'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'incentive_total_calculation_order',
            'value' => $this->data['incentive_total_calculation_order'],
        ]);
        $this->view->assign('help_url', $this->gen_help_url('edit_incentive_total'));
        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/total/form.tpl');

    }

    protected function validate()
    {
        if (!$this->user->canModify('total/incentive_total')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        $this->extensions->hk_ValidateData($this);
        return (!$this->error);
    }
}
