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

namespace abc\modules\traits;

use H;

trait CustomerTabsTrait
{
    public function getTabs($customer_id, $active)
    {
        $this->load->language('sale/customer');
        $this->data['tabs']['general'] = [
            'href'       => $this->html->getSecureURL('sale/customer/update', '&customer_id=' . $customer_id),
            'text'       => $this->language->get('tab_customer_details'),
            'active'     => ($active === 'general'),
            'sort_order' => 0,
        ];
        if (H::has_value($customer_id)) {
            $this->data['tabs']['transactions'] = [
                'href'       => $this->html->getSecureURL('sale/customer_transaction', '&customer_id=' . $customer_id),
                'text'       => $this->language->get('tab_transactions'),
                'active'     => ($active === 'transactions'),
                'sort_order' => 10,
            ];
            $this->data['tabs']['notes'] = [
                'href'       => $this->html->getSecureURL('sale/customer/notes', '&customer_id=' . $customer_id),
                'text'       => $this->language->get('tab_customer_notes'),
                'active'     => ($active === 'notes'),
                'sort_order' => 20,
            ];
            if ($this->config->get('config_save_customer_communication')) {
                $this->data['tabs']['communications'] = [
                    'href'       => $this->html->getSecureURL(
                        'sale/customer/communications',
                        '&customer_id=' . $customer_id
                    ),
                    'text'       => $this->language->get('tab_customer_communications'),
                    'active'     => ($active === 'communications'),
                    'sort_order' => 30,
                ];
            }
        }

        $obj = $this->dispatch(
            'responses/common/tabs',
            [
                'customer',
                $this->rt(),
                //parent controller. Use customer to use for other extensions that will add tabs via their hooks
                ['tabs' => $this->data['tabs']],
            ]
        );
        $this->data['tabs'] = $obj->dispatchGetOutput();
        return $this->data['tabs'];
    }
}