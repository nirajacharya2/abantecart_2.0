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

namespace abc\extensions\per_item_shipping\models\storefront\extension;

use abc\core\engine\Model;
use abc\core\engine\ALanguage;

class ModelExtensionPerItemShipping extends Model
{
    public function getQuote($address)
    {
        //create new instance of language for case when model called from admin-side
        $language = new ALanguage($this->registry, $this->language->getLanguageCode(), 0);
        $language->load($language->language_details['directory']);
        $language->load('per_item_shipping/per_item_shipping');

        if ($this->config->get('per_item_shipping_status')) {
            if (!$this->config->get('per_item_shipping_location_id')) {
                $status = true;
            } else {
                $query = $this->db->query(
                    "SELECT *
                    FROM ".$this->db->table_name('zones_to_locations')."
                    WHERE location_id = '".(int)$this->config->get('per_item_shipping_location_id')."'
                        AND country_id = '".(int)$address['country_id']."'
                        AND (zone_id = '".(int)$address['zone_id']."' OR zone_id = '0')");
                if ($query->num_rows) {
                    $status = true;
                } else {
                    $status = false;
                }
            }
        } else {
            $status = false;
        }

        $method_data = [];
        if (!$status) {
            return $method_data;
        }

        $cost = 0;

        //Process all products shipped together with not special shipping settings on a product level
        $b_products = $this->cart->basicShippingProducts();
        if (count($b_products) > 0) {
            foreach ($b_products as $prd) {
                $cost += $this->config->get('per_item_shipping_cost') * $prd['quantity'];
            }
        }

        //Process products that have special shipping settings
        $special_ship_products = $this->cart->specialShippingProducts();
        foreach ($special_ship_products as $product) {
            if ($product['free_shipping']) {
                continue;
            } else {
                if ($product['shipping_price'] > 0) {
                    $cost += $product['shipping_price'] * $product['quantity'];
                } else {
                    $cost += $this->config->get('per_item_shipping_cost') * $product['quantity'];
                }
            }
        }

        $quote_data = [];
        $cost_text = $language->get('text_free');

        if ($cost) {
            $cost_text = $this->currency->format(
                $this->tax->calculate($cost,
                    $this->config->get('per_item_shipping_tax'),
                    $this->config->get('config_tax')
                )
            );
        }
        $quote_data['per_item_shipping'] = [
            'id'           => 'per_item_shipping.per_item_shipping',
            'title'        => $language->get('text_description'),
            'cost'         => $cost,
            'tax_class_id' => $this->config->get('per_item_shipping_tax'),
            'text'         => $cost_text,
        ];
        $method_data = [
            'id'         => 'per_item_shipping',
            'title'      => $language->get('text_title'),
            'quote'      => $quote_data,
            'sort_order' => $this->config->get('per_item_shipping_sort_order'),
            'error'      => false,
        ];
        return $method_data;
    }
}