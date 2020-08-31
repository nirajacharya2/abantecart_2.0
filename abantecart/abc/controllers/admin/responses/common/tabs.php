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
use abc\models\customer\Customer;
use abc\models\order\Order;
use H;

class ControllerResponsesCommonTabs extends AController
{
    public $data = [];
    public $parent_controller = ''; //rt of page where you plan to place tabs

    public function main($parent_controller, $data)
    {
        $this->data = $data;
        $this->parent_controller = $parent_controller; //use it in hooks to recognize what page controller calls
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $tabs = (array)$this->data['tabs'];
        $this->data['tabs'] = $idx = [];
        foreach ($tabs as $k => $tab) {
            $idx[] = (int)$tab['sort_order'];
        }

        array_multisort($idx, SORT_ASC, $tabs);
        $this->data['tabs'] = $tabs;

        $this->view->batchAssign($this->data);
        $this->processTemplate('responses/common/tabs.tpl');
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function latest_customers()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        //10 new customers
        $filter = [
            'sort'  => 'date_added',
            'order' => 'DESC',
            'start' => 0,
            'limit' => 10,
        ];
        $top_customers = Customer::search($filter);
        foreach ($top_customers as $idx => $customer) {
            $top_customers[$idx]['url'] = $this->html->getSecureURL(
                'sale/customer/update',
                '&customer_id='.$customer['customer_id']
            );
        }
        $this->view->assign('top_customers', $top_customers);
        $this->view->assign('recent_customers', $this->language->get('recent_customers'));

        $this->processTemplate('responses/common/latest_customers.tpl');
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function latest_orders()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        //10 new orders
        $filter = [
            'sort'  => 'date_added',
            'order' => 'DESC',
            'start' => 0,
            'limit' => 10,
        ];
        $top_orders = Order::search($filter)->toArray();
        foreach ($top_orders as $idx => &$order) {
            $top_orders[$idx]['url'] = $this->html->getSecureURL(
                'sale/order/details',
                '&order_id='.$order['order_id']
            );

            $top_orders[$idx]['total'] = $this->currency->format(
                $order['total'],
                $this->config->get('config_currency')
            );
            $order['date_added'] = H::dateISO2Display(
                $order['date_added'],
                $this->language->get('date_format_long')
            );
        }
        $this->view->assign('top_orders', $top_orders);

        $this->view->assign('new_orders', $this->language->get('new_orders'));

        $this->processTemplate('responses/common/latest_orders.tpl');
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

}

