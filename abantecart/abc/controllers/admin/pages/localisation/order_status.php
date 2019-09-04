<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2019 Belavier Commerce LLC

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

use abc\core\engine\AController;
use abc\core\engine\AForm;
use abc\core\engine\Registry;
use abc\models\order\OrderStatus;
use abc\models\order\OrderStatusDescription;
use H;
use Illuminate\Validation\ValidationException;

class ControllerPagesLocalisationOrderStatus extends AController
{
    public $data = [];
    public $error = [];

    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->setTitle($this->language->get('heading_title'));

        $this->view->assign('error_warning', $this->error['warning']);
        $this->view->assign('success', $this->session->data['success']);
        if (isset($this->session->data['success'])) {
            unset($this->session->data['success']);
        }

        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('localisation/order_status'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
            'current'   => true,
        ]);

        $grid_settings = [
            'table_id'       => 'stock_grid',
            'url'            => $this->html->getSecureURL('listing_grid/order_status'),
            'editurl'        => $this->html->getSecureURL('listing_grid/order_status/update'),
            'update_field'   => $this->html->getSecureURL('listing_grid/order_status/update_field'),
            'sortname'       => 'name',
            'sortorder'      => 'asc',
            'columns_search' => false,
            'actions'        => [
                'edit'   => [
                    'text' => $this->language->get('text_edit'),
                    'href' => $this->html->getSecureURL('localisation/order_status/update', '&order_status_id=%ID%'),
                ],
                'save'   => [
                    'text' => $this->language->get('button_save'),
                ],
                'delete' => [
                    'text' => $this->language->get('button_delete'),
                ],
            ],
        ];

        $grid_settings['colNames'] = [
            $this->language->get('column_name'),
            $this->language->get('column_text_id'),
            $this->language->get('column_display_status'),
        ];
        $grid_settings['colModel'] = [
            [
                'name'  => 'name',
                'index' => 'name',
                'width' => 200,
                'align' => 'left',
            ],
            [
                'name'  => 'status_text_id',
                'index' => 'status_text_id',
                'width' => 200,
                'align' => 'left',
            ],
            [
                'name'  => 'display_status',
                'index' => 'display_status',
                'width' => 50,
                'align' => 'center',
            ],
        ];

        $grid = $this->dispatch('common/listing_grid', [$grid_settings]);
        $this->view->assign('listing_grid', $grid->dispatchGetOutput());

        $this->view->assign('insert', $this->html->getSecureURL('localisation/order_status/insert'));
        $this->view->assign('form_language_switch', $this->html->getContentLanguageSwitcher());
        $this->view->assign('help_url', $this->gen_help_url('order_status_listing'));

        $this->processTemplate('pages/localisation/order_status_list.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function insert()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->setTitle($this->language->get('heading_title'));

        if ($this->request->is_POST() && $this->validateForm($this->request->post)) {
            $post = $this->request->post;
            $post['language_id'] = $this->language->getContentLanguageID();
            $this->db->beginTransaction();
            try {
                $orderStatus = new OrderStatus($this->request->post);
                $orderStatus->save();
                $order_status_id = $orderStatus->order_status_id;
                $post['order_status_id'] = $order_status_id;
                $orderStatusDescription = new OrderStatusDescription($post);
                $orderStatusDescription->save();
                $this->extensions->hk_ProcessData($this, __FUNCTION__, ['order_status_id' => $order_status_id]);
                $this->session->data['success'] = $this->language->get('text_success');
                $this->db->commit();
                abc_redirect(
                    $this->html->getSecureURL(
                        'localisation/order_status/update',
                        '&order_status_id='.$order_status_id
                    )
                );
            } catch (\Exception $e) {
                Registry::log()->write(__CLASS__.': '.$e->getMessage());
                $this->db->rollback();
            }
        }
        $this->getForm();

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function update()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->view->assign('success', $this->session->data['success']);
        if (isset($this->session->data['success'])) {
            unset($this->session->data['success']);
        }

        $orderStatusId = $this->request->get['order_status_id'];
        $this->document->setTitle($this->language->get('heading_title'));

        if ($this->request->is_POST() && $this->validateForm($this->request->post, $orderStatusId)) {
            $post = $this->request->post;
            $this->db->beginTransaction();
            try {
                $orderStatus = OrderStatus::find($orderStatusId);
                $orderStatus->update($post);
                $orderStatusDesc = OrderStatusDescription::where(
                    [
                        'order_status_id' => $orderStatusId,
                        'language_id'     => $this->language->getContentLanguageID(),
                    ]
                )->first();
                $orderStatusDesc->update($post);

                $this->session->data['success'] = $this->language->get('text_success');
                $this->extensions->hk_ProcessData($this, __FUNCTION__);

                $this->db->commit();
                abc_redirect(
                    $this->html->getSecureURL(
                        'localisation/order_status/update',
                        '&order_status_id='.$orderStatusId
                    )
                );
            } catch (\Exception $e) {
                Registry::log()->write(__CLASS__.': '.$e->getMessage());
                $this->db->rollback();
            }
        }
        $this->getForm();

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function getForm()
    {
        $this->data = [];
        $this->data['error'] = $this->error;
        $this->data['cancel'] = $this->html->getSecureURL('localisation/order_status');

        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('localisation/order_status'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
        ]);

        $order_status_id = $this->request->get['order_status_id'];

        if (isset($this->request->post['order_status'])) {
            $this->data['order_status'] = $this->request->post['order_status'];
        } elseif (isset($this->request->get['order_status_id'])) {
            $this->data['order_status'] = OrderStatus::with('description')->find($order_status_id);
        } else {
            $this->data['order_status'] = [];
        }

        if (!isset($this->request->get['order_status_id'])) {
            $this->data['action'] = $this->html->getSecureURL('localisation/order_status/insert');
            $this->data['heading_title'] = $this->language->get('text_insert').' '.$this->language->get('text_status');
            $this->data['update'] = '';
            $form = new AForm('ST');
            $is_base = false;
        } else {
            $this->data['action'] = $this->html->getSecureURL(
                'localisation/order_status/update',
                '&order_status_id='.$order_status_id
            );
            $this->data['heading_title'] = $this->language->get('text_edit').' '.$this->language->get('text_status');
            $this->data['update'] = $this->html->getSecureURL(
                'listing_grid/order_status/update_field',
                '&id='.$order_status_id
            );
            $form = new AForm('HS');
            $is_base = in_array($order_status_id, array_keys($this->order_status->getBaseStatuses()));
        }

        $this->document->addBreadcrumb([
            'href'      => $this->data['action'],
            'text'      => $this->data['heading_title'],
            'separator' => ' :: ',
            'current'   => true,
        ]);

        $form->setForm([
            'form_name' => 'editFrm',
            'update'    => $this->data['update'],
        ]);

        $this->data['form']['id'] = 'editFrm';
        $this->data['form']['form_open'] = $form->getFieldHtml([
            'type'   => 'form',
            'name'   => 'editFrm',
            'action' => $this->data['action'],
            'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
        ]);
        $this->data['form']['submit'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'submit',
            'text'  => $this->language->get('button_save'),
            'style' => 'button1',
        ]);
        $this->data['form']['cancel'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'cancel',
            'text'  => $this->language->get('button_cancel'),
            'style' => 'button2',
        ]);

        $this->data['form']['fields']['name'] = $form->getFieldHtml([
            'type'         => 'input',
            'name'         => 'name',
            'value'        => $this->data['order_status']['description']['name'],
            'required'     => true,
            'style'        => 'large-field',
            'multilingual' => true,
        ]);

        if (!$is_base) {
            $this->data['form']['fields']['status_text_id'] = $form->getFieldHtml([
                'type'     => 'input',
                'name'     => 'status_text_id',
                'value'    => $this->data['order_status']['status_text_id'],
                'required' => true,
                'style'    => 'large-field',
            ]);
        }

        $this->data['form']['fields']['display_status'] = $form->getFieldHtml([
            'type'     => 'checkbox',
            'name'     => 'display_status',
            'value'    => $this->data['order_status']['display_status'],
            'required' => true,
            'style'    => 'btn_switch',

        ]);

        $this->view->batchAssign($this->data);
        $this->view->assign('form_language_switch', $this->html->getContentLanguageSwitcher());
        $this->view->assign('help_url', $this->gen_help_url('order_status_edit'));

        $this->processTemplate('pages/localisation/order_status_form.tpl');
    }

    protected function validateForm($data, $orderStatusId = null)
    {
        if (!$this->user->canModify('localisation/order_status')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ($orderStatusId) {
            $oStatus = OrderStatus::find($orderStatusId);
        } else {
            $oStatus = new OrderStatus();
        }

        try {
            $oStatus->validate($data);
        } catch (ValidationException $e) {
            H::SimplifyValidationErrors($oStatus->errors()['validation'], $this->error);
        }
        $oStatusDesc = new OrderStatusDescription();
        try {
            $oStatusDesc->validate($data);
        } catch (ValidationException $e) {
            H::SimplifyValidationErrors($oStatusDesc->errors()['validation'], $this->error);
        }

        $this->extensions->hk_ValidateData($this);

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

}
