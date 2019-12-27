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

use abc\core\engine\AController;
use abc\core\lib\AJson;
use abc\models\order\OrderHistory;
use abc\models\order\OrderStatusDescription;
use abc\modules\events\ABaseEvent;
use H;

class ControllerResponsesSaleOrderHistory extends AController
{

    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('sale/order');
        $json = [];
        if (!$this->user->canModify('sale/order')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $post = $this->request->post;
            $this->db->beginTransaction();
            try {
                $data = $post;
                $data['order_id'] = $this->request->get['order_id'];
                $data['notify'] = (isset($post['notify']));

                $oHistory = new OrderHistory($data);
                $oHistory->save();
                $this->db->commit();
                $json['success'] = $this->language->get('text_success');
                $json['date_added'] = date($this->language->get('date_format_short'));
                H::event('admin\SendOrderStatusNotifyEmail', [new ABaseEvent($data)]);
            } catch (\Exception $e) {
                $json['error'] = H::getAppErrorText();
                $this->db->rollback();
            }

            $orderStatus = OrderStatusDescription::where(
                [
                    'language_id'     => $this->language->getContentLanguageID(),
                    'order_status_id' => $this->request->post['order_status_id'],
                ]
            )->first();

            if ($orderStatus) {
                $json['order_status'] = $orderStatus->name;
            } else {
                $json['order_status'] = '';
            }

            if ($this->request->post['notify']) {
                $json['notify'] = $this->language->get('text_yes');
            } else {
                $json['notify'] = $this->language->get('text_no');
            }

            if (isset($this->request->post['comment'])) {
                $json['comment'] = $this->request->post['comment'];
            } else {
                $json['comment'] = '';
            }
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($json));
    }

}