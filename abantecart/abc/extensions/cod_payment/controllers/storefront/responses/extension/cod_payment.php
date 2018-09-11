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

class ControllerResponsesExtensionCodPayment extends AController
{
    public function main()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $back_rt = $this->request->get['rt'] == 'checkout/guest_step_3' ? 'checkout/guest_step_2' : 'checkout/payment';
        $item = $this->html->buildElement(
            [
                'type'  => 'button',
                'name'  => 'back',
                'style' => 'button',
                'href'  => $this->html->getSecureURL($back_rt, '&mode=edit', true),
                'text'  => $this->language->get('button_back'),
            ]
        );
        $this->view->assign('button_back', $item);

        $item = $this->html->buildElement(
            [
                'type'  => 'button',
                'name'  => 'checkout',
                'style' => 'button btn-primary',
                'text'  => $this->language->get('button_confirm'),
            ]
        );
        $this->view->assign('button_confirm', $item);
        $this->view->assign('continue_url', $this->html->getSecureURL('checkout/success'));
        $this->view->assign(
            'confirm_url',
            $this->html->getSecureURL('checkout/process/confirm', '&extension=cod_payment&action=confirm')
        );

        $this->processTemplate('responses/cod_payment.tpl');

        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}
