<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2022 Belavier Commerce LLC
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

namespace abc\extensions\free_shipping\models\storefront\extension;

use abc\core\engine\Model;
use abc\core\engine\ALanguage;

class ModelExtensionFreeShipping extends Model
{
    function getQuote($address)
    {
        //create new instance of language for case when model called from admin-side
        $language = new ALanguage($this->registry, $this->language->getLanguageCode(), 0);
        $language->load($language->language_details['filename']);
        $language->load('free_shipping/free_shipping');

        if ($this->config->get('free_shipping_status')) {
            $query = $this->db->query(
                "SELECT *
                FROM " . $this->db->table_name("zones_to_locations") . "
                WHERE location_id = '" . (int)$this->config->get('free_shipping_location_id') . "'
                    AND country_id = '" . (int)$address['country_id'] . "'
                    AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id IS NULL )"
            );

            if (!$this->config->get('free_shipping_location_id')) {
                $status = true;
            } elseif ($query->num_rows) {
                $status = true;
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        if ($this->cart->getSubTotal() < $this->config->get('free_shipping_total')) {
            $status = false;
        }

        $method_data = [];

        if ($status) {
            $quote_data = [];

            $quote_data['free_shipping'] = [
                'id'           => 'free_shipping.free_shipping',
                'title'        => $language->get('text_description'),
                'cost'         => 0.00,
                'tax_class_id' => 0,
                'text'         => $language->get('text_free'),
            ];

            $method_data = [
                'id'         => 'free_shipping',
                'title'      => $language->get('text_title'),
                'quote'      => $quote_data,
                'sort_order' => (int)$this->config->get('free_shipping_sort_order'),
                'error'      => false,
            ];
        }

        return $method_data;
    }
}