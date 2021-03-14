<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2021 Belavier Commerce LLC

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

class ControllerBlocksCouponCodes extends AController
{

    public function main($action = '')
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('checkout/payment');
        if (!$this->config->get('coupon_status')) {
            return null;
        }

        $this->data['coupon_status'] = $this->config->get('coupon_status');
        $entered_cpn_code = $this->request->post['coupon'] ?? $this->session->data['coupon'];

        $form = new AForm();
        $form->setForm(['form_name' => 'coupon']);

        $this->data['coupon_code'] = $entered_cpn_code;
        $this->data['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'coupon',
                'action' => $action,
                'csrf'   => true,
            ]
        );
        $this->data['coupon'] = $form->getFieldHtml(
            [
                'type'  => 'input',
                'name'  => 'coupon',
                'value' => $entered_cpn_code,
            ]
        );
        $this->data['submit'] = $form->getFieldHtml(
            [
                'type' => 'submit',
                'name' => $this->language->get('button_coupon'),
            ]
        );

        $this->view->batchAssign($this->data);
        $this->processTemplate('blocks/coupon_form.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}