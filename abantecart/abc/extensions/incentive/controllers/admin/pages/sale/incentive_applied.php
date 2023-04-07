<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2023 Belavier Commerce LLC

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
use abc\core\engine\Registry;
use abc\core\lib\AError;
use abc\core\lib\APromotion;
use abc\extensions\incentive\models\Incentive;
use abc\extensions\incentive\models\IncentiveDescription;
use abc\extensions\incentive\modules\conditions\CustomerPostcodes;
use H;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ControllerPagesSaleIncentiveApplied extends AController
{
    public $error = [];

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $customerId = $this->request->get['customer_id'];
        $incentiveId = $this->request->get['incentive_id'];

        $this->loadLanguage('incentive/incentive');
        $title = $this->language->t('incentive_name_applied', 'Applied Incentives');
        $this->document->setTitle($title);
        $this->view->assign('help_url', $this->gen_help_url('sale_incentive'));

        $this->document->initBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('index/home'),
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ]
        );
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('sale/incentive_applied'),
                'text'      => $title,
                'separator' => ' :: ',
                'current'   => true,
            ]
        );

        $this->view->assign('heading_title', $title);

        $grid_settings = [
            'table_id'   => 'incentive_applied_grid',
            'url'        => $this->html->getSecureURL(
                'listing_grid/incentive_applied',
                '&customer_id=' . $customerId . '&incentive_id=' . $incentiveId
            ),
            'sortname'   => 'date_modified',
            'sortorder'  => 'desc',
            'grid_ready' => 'grid_ready();',
            'actions'    => [
                'view' => [
                    'text' => $this->language->get('text_edit'),
                    'href' => $this->html->getSecureURL('listing_grid/incentive_applied/details', '&id=%ID%'),
                ],
            ],
        ];

        $grid_settings['colNames'] = [
            $this->language->t('incentive_text_customer', 'Customer'),
            $this->language->t('incentive_text_incentive', 'Incentive'),
            $this->language->t('incentive_text_bonus_amount', 'Bonus Amount'),
            $this->language->t('incentive_text_date', 'Date'),
            $this->language->t('incentive_text_result', 'Result')
        ];
        $grid_settings['colModel'] = [
            [
                'name'   => 'customer',
                'index'  => 'customer',
                'align'  => 'center',
                'search' => true,
            ],
            [
                'name'   => 'incentive',
                'index'  => 'incentive',
                'align'  => 'center',
                'search' => false,
            ],
            [
                'name'   => 'bonus_amount',
                'index'  => 'bonus_amount',
                'align'  => 'center',
                'search' => true,
            ],
            [
                'name'   => 'date',
                'index'  => 'date',
                'width'  => 150,
                'align'  => 'center',
                'search' => false,
            ],
            [
                'name'   => 'result',
                'index'  => 'result',
                'align'  => 'center',
                'search' => false,
            ],
        ];

        $form = new AForm();
        $form->setForm(
            [
                'form_name' => 'incentive_applied_grid_search',
            ]
        );

        $grid_search_form = [];
        $grid_search_form['id'] = 'incentive_applied_grid_search';
        $grid_search_form['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'incentive_applied_grid_search',
                'action' => '',
            ]
        );
        $grid_search_form['submit'] = $form->getFieldHtml(
            [
                'type'  => 'button',
                'name'  => 'submit',
                'text'  => $this->language->get('button_go'),
                'style' => 'button1',
            ]
        );
        $grid_search_form['reset'] = $form->getFieldHtml(
            [
                'type'  => 'button',
                'name'  => 'reset',
                'text'  => $this->language->get('button_reset'),
                'style' => 'button2',
            ]
        );

        $grid_search_form['fields']['incentive_id'] = $form->getFieldHtml(
            [
                'type'        => 'selectbox',
                'name'        => 'incentive_id',
                'options'     =>
                    ['' => $this->language->t('incentive_text_all_incentives', 'All Incentives')]
                    +
                    (array)Incentive::getIncentives(['sort' => 'name'])?->pluck('name', 'incentive_id')->toArray(),
                'value'       => $incentiveId,
                'placeholder' => $this->language->get('text_select'),
            ]
        );

        $this->view->assign('js_date_format', H::format4Datepicker($this->language->get('date_format_short')));
        $grid_search_form['fields']['start_date'] = $form->getFieldHtml(
            [
                'type'        => 'date',
                'name'        => 'start_date',
                'value'       => ($this->data['start_date']
                    ? H::dateISO2Display($this->data['start_date'], $this->language->get('date_format_short'))
                    : null),
                'placeholder' => 'Start Date',
                'highlight'   => 'future',
                'dateformat'  => H::format4Datepicker($this->language->get('date_format_short')),
            ]
        );

        $grid_search_form['fields']['end_date'] = $form->getFieldHtml(
            [
                'type'        => 'date',
                'name'        => 'end_date',
                'value'       => ($this->data['end_date']
                    ? H::dateISO2Display($this->data['end_date'], $this->language->get('date_format_short'))
                    : null),
                'placeholder' => 'End Date',
                'highlight'   => 'past',
                'dateformat'  => H::format4Datepicker($this->language->get('date_format_short')),
            ]
        );

        $grid_settings['search_form'] = true;

        $grid = $this->dispatch('common/listing_grid', [$grid_settings]);
        $this->view->assign('listing_grid', $grid->dispatchGetOutput());
        $this->view->assign('search_form', $grid_search_form);
        $this->view->assign('grid_url', $this->html->getSecureURL('listing_grid/incentive_applied'));

        $this->document->setTitle($this->language->get('incentive_name'));

        $this->processTemplate('pages/sale/incentive_applied.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

    }


    protected function processTabs($tabs)
    {
        $obj = $this->dispatch('responses/common/tabs', [
                'group'    => 'incentives',
                'parentRt' => 'sale/incentive/edit',
                'data'     => ['tabs' => $tabs]
            ]
        );

        return $obj->dispatchGetOutput('responses/common/tabs');
    }

}