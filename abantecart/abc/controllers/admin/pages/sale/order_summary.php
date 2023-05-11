<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2017 Belavier Commerce LLC

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
use abc\models\order\Order;
use abc\models\order\OrderStatus;
use H;

class ControllerPagesSaleOrderSummary extends AController
{

    public $data = [];

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        //add phone validation js for quick preview modal
        $this->document->addScript($this->view->templateResource('assets/js/intl-tel-input/js/intlTelInput.min.js'));

        $this->loadLanguage('sale/order');

        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];
        } else {
            $order_id = 0;
        }

        $order_info = Order::getOrderArray($order_id, 'any');

        if (empty($order_info)) {
            $this->data['error_warning'] = $this->language->get('error_order_load');
        } else {
            //if virtual product (no shipment);
            if (!$order_info['shipping_method']) {
                $order_info['shipping_method'] = $this->language->get('text_not_applicable');
            }
            // no payment
            if (!$order_info['payment_method']) {
                $order_info['payment_method'] = $this->language->get('text_not_applicable');
            }

            $this->data['order'] = [
                'order_id'        => '#'.$order_info['order_id'],
                'customer_name'   => $order_info['firstname'].' '.$order_info['lastname'],
                'email'           => $order_info['email'],
                'telephone'       => $order_info['telephone'],
                'date_added'      => H::dateISO2Display($order_info['date_added'],
                    $this->language->get('date_format_short').' '.$this->language->get('time_format')),
                'total'           => $this->currency->format($order_info['total'], $order_info['currency'],
                    $order_info['value']),
                'order_status'    => $order_info['order_status_id'],
                'shipping_method' => $order_info['shipping_method'],
                'payment_method'  => $order_info['payment_method'],
            ];

            if ($order_info['customer_id']) {
                $this->data['customer'] = [
                    'name'  => $this->data['order']['customer_name'],
                    'href'  => $this->html->getSecureURL('sale/customer/update',
                        '&customer_id='. $order_info['customer_id']),
                    //viewport URL
                    'vhref' => $this->html->getSecureURL('r/common/viewport',
                        '&viewport_rt=sale/customer/update&customer_id=' . $order_info['customer_id']),
                ];
            } else {
                $this->data['customer'] = [
                    'name' => $this->data['order']['customer_name'],
                ];
            }

            if ($this->registry->get('AuditLogStorage') || ABC::getObjectByAlias('AuditLogStorage')) {
                $this->data['auditLog'] = $this->html->buildElement([
                        'type'  => 'button',
                        'text'  => $this->language->get('text_audit_log'),
                        'href'  => $this->html->getSecureURL('tool/audit_log',
                            '&modal_mode=1&auditable_type=Order&auditable_id=' . $order_info['order_id']),
                        //quick view port URL
                        'vhref' => $this->html->getSecureURL(
                            'r/common/viewport/modal',
                            '&viewport_rt=tool/audit_log&modal_mode=1&auditable_type=Order&auditable_id='
                            . $order_info['order_id']),
                    ]
                );
            }

            $status = OrderStatus::with('description')->find($order_info['order_status_id']);
            if ($status) {
                $this->data['order']['order_status'] = $status['description']['name'];
            }

        }

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/sale/order_summary.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

}