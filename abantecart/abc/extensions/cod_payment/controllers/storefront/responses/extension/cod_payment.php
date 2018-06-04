<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */

namespace abc\controllers\storefront;

use abc\core\engine\AController;
use abc\core\lib\AJson;

class ControllerResponsesExtensionCodPayment extends AController
{
    public function main()
    {
        $item = $this->html->buildElement(
            array(
                'type'  => 'button',
                'name'  => 'back',
                'style' => 'button',
                'text'  => $this->language->get('button_back'),
            ));
        $this->view->assign('button_back', $item);

        $item = $this->html->buildElement(
            array(
                'type'  => 'button',
                'name'  => 'checkout',
                'style' => 'button btn-primary',
                'text'  => $this->language->get('button_confirm'),
            ));
        $this->view->assign('button_confirm', $item);

        $this->view->assign('continue', $this->html->getSecureURL('checkout/success'));

        if ($this->request->get['rt'] == 'checkout/guest_step_3') {
            $this->view->assign('back', $this->html->getSecureURL('checkout/guest_step_2', '&mode=edit', true));
        } else {
            $this->view->assign('back', $this->html->getSecureURL('checkout/payment', '&mode=edit', true));
        }

        $this->processTemplate('responses/cod_payment.tpl');
    }

    public function api()
    {
        $data = array();

        $data['text_note'] = $this->language->get('text_note');
        $data['process_rt'] = 'cod_payment/api_confirm';

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($data));
    }

    public function api_confirm()
    {
        $data = array();

        $this->confirm();
        $data['success'] = 'completed';

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($data));
    }

    public function confirm()
    {
        $this->load->model('checkout/order');
        $this->model_checkout_order->confirm(
            $this->session->data['order_id'],
            $this->config->get('cod_payment_order_status_id')
        );
    }
}
