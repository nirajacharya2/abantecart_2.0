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

namespace abc\extensions\cod_payment\models\storefront\extension;

use abc\core\engine\ALanguage;
use abc\core\engine\Model;

class ModelExtensionCODPayment extends Model
{
    public function getMethod($address)
    {
        $language = new ALanguage($this->registry, $this->language->getLanguageCode());
        $language->load('cod_payment/cod_payment');
        if ($this->config->get('cod_payment_status')) {
            $query = $this->db->query(
                "SELECT * 
                FROM ".$this->db->table_name("zones_to_locations")." 
                WHERE location_id = '".(int)$this->config->get('cod_payment_location_id')."' 
                    AND country_id = '".(int)$address['country_id']."' 
                    AND (zone_id = '".(int)$address['zone_id']."' 
                    OR zone_id = '0')"
            );

            if (!$this->config->get('cod_payment_location_id')) {
                $status = true;
            } elseif ($query->num_rows) {
                $status = true;
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        $method_data = [];

        if ($status) {
            $method_data = [
                'id'         => 'cod_payment',
                'title'      => $language->get('text_title'),
                'sort_order' => $this->config->get('cod_payment_sort_order'),
            ];
        }

        return $method_data;
    }
}
