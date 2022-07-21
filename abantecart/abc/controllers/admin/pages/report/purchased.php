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
use abc\core\engine\AForm;
use H;

if (!class_exists('abc\core\ABC') || !ABC::env('IS_ADMIN')) {
    header('Location: static_pages/?forbidden=' . basename(__FILE__));
}

class ControllerPagesReportPurchased extends AController
{

    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $grid_settings = [
            //id of grid
            'table_id' => 'report_purchased_grid',
            // url to load data from
            'url' => $this->html->getSecureURL('listing_grid/report_purchased'),
            // default sort column
            'sortname' => 'total',
            'columns_search' => true,
            'multiselect' => 'false',
            'search_form' => true
        ];

        $grid_settings['colNames'] = [
            $this->language->get('column_name'),
            $this->language->get('column_model'),
            $this->language->get('column_quantity'),
            $this->language->get('column_total'),
        ];

        $grid_settings['colModel'] = [
            [
                'name' => 'name',
                'index' => 'name',
                'width' => 300,
                'align' => 'left',
                'sortable' => true,
                'search' => true,
            ],
            [
                'name' => 'model',
                'index' => 'model',
                'width' => 80,
                'align' => 'left',
                'sortable' => true,
            ],
            [
                'name' => 'quantity',
                'index' => 'quantity',
                'width' => 50,
                'align' => 'center',
                'sortable' => true,
                'search' => false,
            ],
            [
                'name' => 'total',
                'index' => 'total',
                'width' => 90,
                'align' => 'center',
                'sortable' => false,
                'search' => false,
            ],
        ];

        $grid = $this->dispatch('common/listing_grid', [$grid_settings]);
        $this->view->assign('listing_grid', $grid->dispatchGetOutput());

        //prepare the filter form
        //Note: External search form needs to be named [grid_name]_search
        //		In this case it will be auto submitted to filter grid
        $form = new AForm();
        $form->setForm([
            'form_name' => 'report_purchased_grid_search',
        ]);
        $this->data['grid_search_form'] = [];
        $this->data['grid_search_form']['id'] = 'report_purchased_grid_search';
        $this->data['grid_search_form']['form_open'] = $form->getFieldHtml(
            [
                'type' => 'form',
                'name' => 'report_purchased_grid_search',
                'action' => '',
            ]);
        $this->data['grid_search_form']['submit'] = $form->getFieldHtml(
            [
                'type' => 'button',
                'name' => 'submit',
                'text' => $this->language->get('button_go'),
                'style' => 'button1',
            ]);
        $this->view->assign('js_date_format', H::format4Datepicker($this->language->get('date_format_short')));
        $this->data['grid_search_form']['fields']['date_start'] = $form->getFieldHtml(
            [
                'type' => 'input',
                'name' => 'date_start',
                'default' => H::dateInt2Display(strtotime('-30 day')),
            ]);
        $this->data['grid_search_form']['fields']['date_end'] = $form->getFieldHtml(
            [
                'type' => 'input',
                'name' => 'date_end',
                'default' => H::dateInt2Display(time()),
            ]);

        $this->view->assign('search_form', $this->data['grid_search_form']);

        $this->document->initBreadcrumb(
            [
                'href' => $this->html->getSecureURL('index/home'),
                'text' => $this->language->get('text_home'),
                'separator' => false
            ]);
        $this->document->addBreadcrumb(
            [
                'href' => $this->html->getSecureURL('report/purchased'),
                'text' => $this->language->get('heading_title'),
                'separator' => ' :: ',
                'current' => true
            ]);

        $this->document->setTitle($this->language->get('heading_title'));

        $this->processTemplate('pages/report/purchased.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}
