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
use abc\core\engine\AForm;
use H;

/**
 * Class ControllerPagesReportCustomerTransactions
 *
 * @package abc\controllers\admin
 */
class ControllerPagesReportCustomerTransactions extends AController
{
    public $data = [];

    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('report/customer/transactions'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
            'current'   => true,
        ]);

        $grid_settings = [
            //id of grid
            'table_id'       => 'customer_transactions_grid',
            // url to load data from
            'url'            => $this->html->getSecureURL('listing_grid/report_customer/transactions'),
            // default sort column
            'sortname'       => 'date_added',
            'sortorder'      => 'desc',
            'columns_search' => true,
            'multiselect'    => 'false',
            // actions
            'actions'        => [
                'view' => [
                    'text' => $this->language->get('text_view'),
                    'href' => $this->html->getSecureURL(
                        'listing_grid/customer_transaction/transaction',
                        '&customer_transaction_id=%ID%'
                    ),
                ],
            ],
            'grid_ready'     => 'updateViewButtons();',
        ];

        $grid_settings['search_form'] = true;

        $grid_settings['colNames'] = [
            $this->language->get('column_transaction_date'),
            $this->language->get('column_customer'),
            $this->language->get('column_debit'),
            $this->language->get('column_credit'),
            $this->language->get('column_transaction_type'),
            $this->language->get('column_created_by'),
        ];

        $grid_settings['colModel'] = [
            [
                'name'     => 'date_added',
                'index'    => 'date_added',
                'width'    => 110,
                'align'    => 'center',
                'sortable' => true,
                'search'   => false,
            ],
            [
                'name'     => 'customer',
                'index'    => 'customer',
                'width'    => 120,
                'align'    => 'center',
                'sorttype' => 'string',
                'sortable' => true,
                'search'   => true,
            ],
            [
                'name'   => 'debit',
                'index'  => 'debit',
                'width'  => 100,
                'align'  => 'center',
                'search' => false,
            ],
            [
                'name'     => 'credit',
                'index'    => 'credit',
                'width'    => 100,
                'align'    => 'center',
                'sortable' => true,
                'search'   => false,
            ],
            [
                'name'     => 'transaction_type',
                'index'    => 'transaction_type',
                'width'    => 120,
                'align'    => 'center',
                'sortable' => true,
                'search'   => false,
            ],
            [
                'name'     => 'created_by',
                'index'    => 'created_by',
                'width'    => 120,
                'align'    => 'center',
                'sortable' => true,
                'search'   => false,
            ],
        ];

        $grid = $this->dispatch('common/listing_grid', [$grid_settings]);
        $this->view->assign('listing_grid', $grid->dispatchGetOutput());

        //prepare the filter form
        //Note: External search form needs to be named [grid_name]_search
        //		In this case it will be auto submitted to filter grid
        $form = new AForm();
        $form->setForm([
            'form_name' => 'customer_transactions_grid_search',
        ]);
        $this->data['grid_search_form'] = [];
        $this->data['grid_search_form']['id'] = 'customer_transactions_grid_search';
        $this->data['grid_search_form']['form_open'] = $form->getFieldHtml([
            'type'   => 'form',
            'name'   => 'customer_transactions_grid_search',
            'action' => '',
        ]);
        $this->data['grid_search_form']['submit'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'submit',
            'text'  => $this->language->get('button_go'),
            'style' => 'button1',
        ]);
        $this->view->assign('js_date_format', H::format4Datepicker($this->language->get('date_format_short')));
        $this->data['grid_search_form']['fields']['date_start'] = $form->getFieldHtml([
            'type'    => 'input',
            'name'    => 'date_start',
            'default' => H::dateInt2Display(strtotime('-7 day')),
        ]);
        $this->data['grid_search_form']['fields']['date_end'] = $form->getFieldHtml([
            'type'    => 'input',
            'name'    => 'date_end',
            'default' => H::dateInt2Display(time()),
        ]);

        $this->view->assign('search_form', $this->data['grid_search_form']);

        $this->view->assign('reset', $this->html->getSecureURL('report/customer/transactions'));

        $this->processTemplate('pages/report/customer/transactions.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}