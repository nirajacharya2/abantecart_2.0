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

namespace abc\controllers\storefront;

use abc\core\engine\AController;
use abc\core\engine\AForm;
use abc\models\customer\CustomerTransaction;
use Carbon\Carbon;
use H;

class ControllerPagesAccountTransactions extends AController
{
    /**
     * Main Controller function to show transaction history.
     * Note: Regular orders are considered in the transactions.
     */
    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->html->getSecureURL('account/transactions');
            abc_redirect($this->html->getSecureURL('account/login'));
        }

        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->resetBreadcrumbs();
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getHomeURL(),
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ]
        );
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('account/account'),
                'text'      => $this->language->get('text_account'),
                'separator' => $this->language->get('text_separator'),
            ]
        );
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('account/transactions'),
                'text'      => $this->language->get('text_transactions'),
                'separator' => $this->language->get('text_separator'),
            ]
        );

        $this->data['action'] = $this->html->getSecureURL('account/transactions');

        $page = $this->request->get['page'] ?? 1;

        if (isset($this->request->get['limit'])) {
            $limit = (int) $this->request->get['limit'];
            $limit = min($limit, 50);
        } else {
            $limit = $this->config->get('config_catalog_limit');
        }

        $trans = [];

        $sidx = $this->request->get['sidx']; // get index row - i.e. user click to sort
        $sord = $this->request->get['sord']; // get the direction

        $balance = $this->customer->getBalance();
        $this->data['balance_amount'] = $this->currency->format($balance);

        $form = new AForm();
        $form->setForm([
                           'form_name' => 'transactions_search',
                       ]);
        $this->data['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'transactions_search',
                'action' => $this->html->getSecureURL(
                    'account/transactions'
                ),
                'method' => 'GET',
            ]
        );
        $this->data['rt'] = $form->getFieldHtml(
            [
                'type'  => 'hidden',
                'name'  => 'rt',
                'value' => 'account/transactions',
            ]
        );
        $this->data['js_date_format'] = H::format4Datepicker($this->language->get('date_format_short'));
        $this->data['date_start'] = $form->getFieldHtml(
            [
                'type'  => 'date',
                'name'  => 'date_start',
                'value' => $this->request->get['date_start']
                    ? : H::dateInt2Display(strtotime('-7 day')),
            ]
        );
        $this->data['date_end'] = $form->getFieldHtml(
            [
                'type'  => 'date',
                'name'  => 'date_end',
                'value' => $this->request->get['date_end']
                    ? : H::dateInt2Display(time()),
            ]
        );

        $this->data['submit'] = $this->html->buildElement(
            [
                'type'  => 'submit',
                'name'  => 'Go',
                'style' => 'btn-primary lock-on-click',
            ]
        );

        $data = [
            'sort'        => $sidx,
            'order'       => $sord,
            'start'       => ($page - 1) * $limit,
            'limit'       => $limit,
            'filter'      => [
                'date_start' => Carbon::parse(H::dateDisplay2ISO($this->data['date_start']->value))
                    ->startOfDay()->toDateTimeString(),
                'date_end'   => Carbon::parse(H::dateDisplay2ISO($this->data['date_end']->value))
                    ->endOfDay()->toDateTimeString(),
            ],
            'customer_id' => (int) $this->customer->getId(),
        ];

        $results = CustomerTransaction::getTransactions($data);
        $trans_total = $results::getFoundRowsCount();
        $results = $results->toArray();

        if (count($results)) {
            foreach ($results as $result) {
                $result['date_added'] = Carbon::parse($result['date_added'])
                                              ->format(
                                                  $this->language->get('date_format_short')
                                                        .' '
                                                        .$this->language->get('time_format') );
                $result['credit'] = $this->currency->format($result['credit']);
                $result['debit'] = $this->currency->format($result['debit']);
                $trans[] = $result;
            }

            $this->data['pagination_bootstrap'] = $this->html->buildElement(
                [
                    'type'       => 'Pagination',
                    'name'       => 'pagination',
                    'text'       => $this->language->get('text_pagination'),
                    'text_limit' => $this->language->get('text_per_page'),
                    'total'      => $trans_total,
                    'page'       => $page,
                    'limit'      => $limit,
                    'url'        => $this->html->getSecureURL(
                        'account/transactions',
                        '&date_start=' . $this->data['date_start']->value
                        . '&date_end=' . $this->data['date_end']->value
                        . '&limit=' . $limit . '&page={page}'
                    ),
                    'style'      => 'pagination',
                ]
            );
        }

        $this->data['continue'] = $this->html->getSecureURL('account/account');
        $this->view->setTemplate('pages/account/transactions.tpl');
        $this->data['transactions'] = $trans;
        $this->data['button_continue'] = $this->html->buildElement(
            [
                'type'  => 'button',
                'name'  => 'continue_button',
                'text'  => $this->language->get('button_continue'),
                'style' => 'button',
            ]
        );

        $this->view->batchAssign($this->data);
        $this->processTemplate();
        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}