<?php

namespace abc\controllers\admin;

use abc\core\engine\AController;
use abc\core\engine\AForm;
use abc\core\lib\AException;
use abc\models\locale\Language;
use abc\models\system\EmailTemplate;
use H;
use Illuminate\Validation\ValidationException;

class ControllerPagesDesignEmailTemplates extends AController
{
    public $error = array();
    public $data = array();

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->buildHeader();

        $grid_settings = [
            'table_id'         => 'email_templates_grid',
            'url'              => $this->html->getSecureURL('listing_grid/email_templates'),
            'editurl'          => $this->html->getSecureURL('listing_grid/email_templates/update'),
            'update_field'     => $this->html->getSecureURL('listing_grid/email_templates/update_field'),
            'sortname'         => 'text_id',
            'sortorder'        => 'asc',
            'columns_search'   => true,
            'actions'          => array(
                'edit'   => array(
                    'text' => $this->language->get('text_edit'),
                    'href' => $this->html->getSecureURL('design/email_templates/update', '&id=%ID%'),
                ),
                'delete' => array(
                    'text' => $this->language->get('button_delete'),
                ),
            ),
        ];

        $grid_settings['colNames'] = array(
            $this->language->get('column_text_id'),
            $this->language->get('column_language'),
            $this->language->get('column_status'),
            $this->language->get('column_subject'),
        );

        $grid_settings['colModel'] = array(
            array(
                'name'  => 'text_id',
                'index' => 'text_id',
                'width' => 150,
                'align' => 'left',
            ),
            array(
                'name'  => 'language',
                'index' => 'language',
                'width' => 100,
                'align' => 'left',
            ),
            array(
                'name'   => 'status',
                'index'  => 'status',
                'width'  => 100,
                'align'  => 'center',
                'search' => false,
            ),
            array(
                'name'  => 'subject',
                'index' => 'subject',
                'width' => 250,
                'align' => 'left',
            ),
        );

        $grid = $this->dispatch('common/listing_grid', array($grid_settings));
        $this->view->assign('listing_grid', $grid->dispatchGetOutput());

        $this->view->assign('insert', $this->html->getSecureURL('design/email_templates/insert'));

        $this->processTemplate('pages/design/email_templates_list.tpl');
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

    }

    public function insert()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->buildHeader();

        if ($this->request->is_POST() && $this->validate($this->request->post)) {
            try {
                $emailTemplate = new EmailTemplate($this->request->post);
                $emailTemplate->save();
                $this->session->data['success'] = $this->language->get('save_complete');
            } catch (\Exception $e) {
                $this->log->write($e->getMessage());
                $this->session->data['warning'] = $this->language->get('save_error');
            }
        }

        if ($this->session->data['success']) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }
        if ($this->session->data['warning']) {
            $this->error['warning'] = $this->session->data['warning'];
            unset($this->session->data['warning']);
        }

        if (!empty($this->error)) {
            $this->view->assign('error', $this->error);
        }

        if ($emailTemplate) {
            abc_redirect($this->html->getSecureURL('design/email_templates/update', '&id='.$emailTemplate->id));
        }

        $this->getForm();

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/design/email_templates_form.tpl');
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function update()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->buildHeader();

        $emailTemplate = EmailTemplate::find((int)$this->request->get['id']);

        if ($this->request->is_POST() && $emailTemplate && $this->validate($this->request->post)) {
            try {
                $emailTemplate->update($this->request->post);
                $this->session->data['success'] = $this->language->get('save_complete');
                abc_redirect($this->html->getSecureURL('design/email_templates/update', '&id='.$emailTemplate->id));
            } catch (\Exception $e) {
                $this->log->write($e->getMessage());
                $this->session->data['warning'] = $this->language->get('save_error');
            }
        }

        if ($this->request->is_POST() && (!$emailTemplate || !$this->validate($this->request->post))) {
            $this->session->data['warning'] = $this->language->get('save_error');
        }

        if (!(int)$this->request->get['id']) {
            abc_redirect($this->html->getSecureURL('design/email_templates'));
        }

        if ($this->session->data['success']) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }
        if ($this->session->data['warning']) {
            $this->error['warning'] = $this->session->data['warning'];
            unset($this->session->data['warning']);
        }

        if (!empty($this->error)) {
            $this->view->assign('error', $this->error);
        }

        $this->getForm();

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/design/email_templates_form.tpl');
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function getForm($args = [])
    {
        $this->view->assign('error_warning', $this->error['warning']);
        $this->view->assign('error_name', $this->error['name']);

        $this->view->assign('cancel', $this->html->getSecureURL('design/email_templates'));

        $languages = Language::select(['language_id', 'name'])
            ->where('status', '=', 1)
            ->get();
        $this->data['languages'] = [
            0 => '-- Please Select --',
        ];
        if ($languages) {
            foreach ($languages as $language) {
                $this->data['languages'][$language->language_id] = $language->name;
            }
        }

        if ((int)$this->request->get['id']) {
            $emailTemplate = EmailTemplate::find((int)$this->request->get['id']);
            if ($emailTemplate) {
                $emailTemplate = $emailTemplate->toArray();
                foreach ($emailTemplate as $key => $value) {
                    $this->data[$key] = $value;
                }
            }
        }

        if ($this->request->is_POST()) {
            foreach ($this->request->post as $key => $value) {
                $this->data[$key] = $value;
            }
        }

        $form = new AForm ('ST');
        $form->setForm(['form_name' => 'emailTemplateFrm']);

        $this->data['form']['id'] = 'emailTemplateFrm';
        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'emailTemplateFrm',
                'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
                'action' => $this->data['action'],
            ]);

        $this->data['form']['submit'] = $form->getFieldHtml(
            [
                'type'  => 'button',
                'name'  => 'submit',
                'text'  => $this->language->get('button_save'),
                'style' => 'button1',
            ]);

        $this->data['form']['cancel'] = $form->getFieldHtml(
            [
                'type'  => 'button',
                'name'  => 'cancel',
                'text'  => $this->language->get('button_cancel'),
                'style' => 'button2',
            ]);

        $this->data['form']['fields']['status'] = $form->getFieldHtml(
            [
                'type'  => 'checkbox',
                'name'  => 'status',
                'value' => isset($this->data['status']) ? $this->data['status'] : 1,
                'style' => 'btn_switch',
            ]);

        $this->data['form']['fields']['text_id'] = $form->getFieldHtml(
            [
                'type'     => 'input',
                'name'     => 'text_id',
                'value'    => $this->data['text_id'],
                'required' => true,
                'attr'     => (int)$this->request->get['id'] ? 'disabled' : '',
            ]);

        $this->data['form']['fields']['language_id'] = $form->getFieldHtml(
            [
                'type'     => 'selectbox',
                'name'     => 'language_id',
                'options'  => $this->data['languages'],
                'value'    => isset($this->data['language_id']) ? $this->data['language_id'] : $this->language->getContentLanguageID(),
                'required' => true,
                'attr'     => (int)$this->request->get['id'] ? 'disabled' : '',
            ]);

        $this->data['form']['fields']['headers'] = $form->getFieldHtml(
            [
                'type'  => 'input',
                'name'  => 'headers',
                'value' => $this->data['headers'],
            ]);

        $this->data['form']['fields']['subject'] = $form->getFieldHtml(
            [
                'type'     => 'input',
                'name'     => 'subject',
                'value'    => $this->data['subject'],
                'required' => true,
            ]);

        $this->data['form']['fields']['html_body'] = $form->getFieldHtml(
            [
                'type'     => 'texteditor',
                'name'     => 'html_body',
                'value'    => $this->data['html_body'],
                'required' => true,
            ]);

        $this->data['form']['fields']['text_body'] = $form->getFieldHtml(
            [
                'type'     => 'textarea',
                'name'     => 'text_body',
                'value'    => $this->data['text_body'],
                'attr'     => 'rows="16"',
                'required' => true,
            ]);
        $this->data['form']['fields']['allowed_placeholders'] = $form->getFieldHtml(
            [
                'type'  => 'textarea',
                'name'  => 'allowed_placeholders',
                'value' => $this->data['allowed_placeholders'],
            ]);

    }

    private function validate(array $data)
    {
        $emailTemplate = new EmailTemplate();
        try {
            $emailTemplate->validate($data);
        } catch (ValidationException $e) {
            H::SimplifyValidationErrors($emailTemplate->errors()['validation'], $this->error);
        } catch (\ReflectionException $e) {
            $this->log->write($e->getMessage());
        } catch (AException $e) {
            $this->log->write($e->getMessage());
        }
        if (empty($this->error)) {
            if (!(int)$this->request->get['id']) {
                $eTemplateCount = EmailTemplate::where('text_id', '=', $data['text_id'])
                    ->where('language_id', '=', $data['language_id'])
                    ->get()->first();
                if ($eTemplateCount > 0) {
                    $this->error['text_id'] = 'Text ID must be unique';
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    private function buildHeader()
    {
        $this->view->assign('form_language_switch', $this->html->getContentLanguageSwitcher());
        $this->view->assign('form_store_switch', $this->html->getStoreSwitcher());

        $this->document->initBreadcrumb(
            array(
                'href'      => $this->html->getSecureURL('index/home'),
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ));
        $this->document->addBreadcrumb(
            array(
                'href'      => $this->html->getSecureURL('design/email_templates'),
                'text'      => $this->language->get('heading_title'),
                'separator' => ' :: ',
                'current'   => true,
            ));

        $this->document->setTitle($this->language->get('heading_title'));
    }

}
