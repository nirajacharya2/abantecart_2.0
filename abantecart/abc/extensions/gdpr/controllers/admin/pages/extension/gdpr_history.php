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
namespace abc\controllers\admin;

use abc\core\engine\AController;

class ControllerPagesExtensionGdprHistory extends AController
{
    public $data;

    public function main()
    {

        $this->loadLanguage('gdpr/gdpr');
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->setTitle($this->language->get('gdpr_history_title'));

        $this->document->initBreadcrumb();
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('extension/gdpr_history'),
            'text'      => $this->language->get('gdpr_history_title'),
            'separator' => ' :: ',
            'current'   => true,
        ]);

        $grid_settings = [
            //id of grid
            'table_id'     => 'gdpr_history',
            // url to load data from
            'url'          => $this->html->getSecureURL('listing_grid/gdpr_history'),
            // url to send data for edit / delete
            'editurl'      => '',
            'multiselect'  => 'false',
            // url to update one field
            'update_field' => '',
            // default sort column
            'sortname'     => 'date_added',
            // actions
            'actions'      => '',
            'sortable'     => true,
        ];

        $grid_settings ['colNames'] = [
            '#',
            $this->language->get('gdpr_column_date_added'),
            $this->language->get('gdpr_column_type'),
            $this->language->get('gdpr_column_name'),
            $this->language->get('gdpr_column_user_agent'),
            $this->language->get('gdpr_column_user_ip'),
        ];
        $grid_settings ['colModel'] = [
            [
                'name'     => 'id',
                'index'    => 'id',
                'width'    => 10,
                'align'    => 'center',
                'sortable' => false,
                'search'   => false,
            ],
            [
                'name'     => 'date_added',
                'index'    => 'date_added',
                'width'    => 50,
                'align'    => 'center',
                'sortable' => false,
                'search'   => false,
            ],
            [
                'name'     => 'type',
                'index'    => 'type',
                'width'    => 50,
                'align'    => 'center',
                'sortable' => false,
                'search'   => false,
            ],
            [
                'name'     => 'name',
                'index'    => 'name',
                'width'    => 50,
                'align'    => 'center',
                'sortable' => false,
            ],
            [
                'name'     => 'user_agent',
                'index'    => 'user_agent',
                'width'    => 20,
                'align'    => 'center',
                'sortable' => false,
                'search'   => false,
            ],
            [
                'name'     => 'ip',
                'index'    => 'ip',
                'width'    => 50,
                'align'    => 'center',
                'sortable' => false,
                'search'   => false,
            ],
        ];

        $grid = $this->dispatch('common/listing_grid', [$grid_settings]);
        $this->view->assign('listing_grid', $grid->dispatchGetOutput());
        $this->view->assign('help_url', $this->gen_help_url());

        $this->view->batchAssign($this->data);

        if (isset($this->session->data['error'])) {
            $this->view->assign('error_warning', $this->session->data['error']);
            unset($this->session->data['error']);
        }
        if (isset($this->session->data['success'])) {
            $this->view->assign('success', $this->session->data['success']);
            unset($this->session->data['success']);
        }

        $this->processTemplate('pages/extension/gdpr_history.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

}