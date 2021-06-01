<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2018 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\models\storefront;

use abc\core\engine\Model;

class ModelToolOnlineNow extends Model
{
    /**
     * @param string $ip
     * @param int $customer_id
     * @param string $url
     * @param string $referer
     *
     * @throws \Exception
     */
    public function setOnline($ip, $customer_id, $url, $referer)
    {
        //delete old records
        $this->db->query("DELETE FROM `".$this->db->table_name("online_customers")."`
                          WHERE `date_added`< (NOW() - INTERVAL 1 HOUR)");

        //insert new record
        $customer_id = (int)$customer_id ? '" . (int)$customer_id   . "' : 'NULL';
        $this->db->query("REPLACE INTO `".$this->db->table_name("online_customers")."`
                        SET `ip` = '".$this->db->escape($ip)."',
                            `customer_id` = ".$customer_id.",
                            `url` = '".$this->db->escape($url)."',
                            `referer` = '".$this->db->escape($referer)."',
                            `date_added` = NOW()");
    }
}
