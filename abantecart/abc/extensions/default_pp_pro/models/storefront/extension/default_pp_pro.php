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

namespace abc\extensions\default_pp_pro\models\storefront\extension;

use abc\core\engine\Model;
use H;

class ModelExtensionDefaultPPPro extends Model
{
    public function getMethod($address)
    {
        $this->load->language('default_pp_pro/default_pp_pro');

        if ($this->config->get('default_pp_pro_status')) {
            $sql = "SELECT *
                    FROM " . $this->db->table_name("zones_to_locations") . "
                    WHERE location_id = '" . (int)$this->config->get('default_pp_pro_location_id') . "'
                           AND country_id = '" . (int)$address['country_id'] . "'
                           AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')";
            $query = $this->db->query($sql);

            if (!$this->config->get('default_pp_pro_location_id')) {
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
                'id'         => 'default_pp_pro',
                'title'      => $this->language->get('text_title'),
                'sort_order' => $this->config->get('default_pp_pro_sort_order'),
            ];
        }

        return $method_data;
    }

    public function getCreditCardTypes()
    {
        return [
            'Visa'       => 'Visa',
            'MasterCard' => 'MasterCard',
            'Discover'   => 'Discover',
            'Amex'       => 'American Express',
        ];
    }

    public function addShippingAddress($data)
    {

        //encrypt customer data
        $key_sql = '';
        if ($this->dcrypt->active) {
            $data = $this->dcrypt->encrypt_data($data, 'addresses');
            $key_sql = ", key_id = '" . (int)$data['key_id'] . "'";
        }

        if (!H::has_value($data['country_id'])) {
            $data['country_id'] = $this->getCountryIdByCode2($data['iso_code_2']);
        }

        if (!H::has_value($data['zone_id'])) {
            $data['zone_id'] = $this->getZoneId($data['country_id'], $data['zone_code']);
        }

        $this->db->query(
            "INSERT INTO " . $this->db->table_name("addresses") . "
            SET
                customer_id = '" . (int)$this->customer->getId() . "',
                company = '" . (H::has_value($data['company']) ? $this->db->escape($data['company']) : '') . "',
                firstname = '" . $this->db->escape($data['firstname']) . "',
                lastname = '" . $this->db->escape($data['lastname']) . "',
                address_1 = '" . $this->db->escape($data['address_1']) . "',
                address_2 = '" . (H::has_value($data['address_2']) ? $this->db->escape($data['address_2']) : '') . "',
                postcode = '" . $this->db->escape($data['postcode']) . "',
                city = '" . $this->db->escape($data['city']) . "',
                zone_id = '" . (int)$data['zone_id'] . "',
                country_id = '" . (int)$data['country_id'] . "'"
            . $key_sql
        );

        $address_id = $this->db->getLastId();

        if (isset($data['default']) && $data['default'] == '1') {
            $this->db->query(
                "UPDATE " . $this->db->table_name("customers") . "
                SET address_id = '" . (int)$address_id . "'
                WHERE customer_id = '" . (int)$this->customer->getId() . "'");
        }
        return $address_id;

    }

    public function getCountryIdByCode2($code)
    {
        $result = $this->db->query(
            "SELECT country_id 
             FROM " . $this->db->table_name('countries') . "
             WHERE iso_code_2 = '" . strtoupper($this->db->escape($code)) . "'"
        );
        if ($result->num_rows > 0) {
            return $result->row['country_id'];
        }
        return null;
    }

    public function getZoneId($country_id, $zone_code)
    {
        $result = $this->db->query(
            'SELECT zone_id 
            FROM ' . $this->db->table_name('zones') . '
            WHERE country_id = "' . (int)$country_id . '"
            AND code = "' . strtoupper($this->db->escape($zone_code)) . '"'
        );

        if ($result->num_rows > 0) {
            return $result->row['zone_id'];
        }

        return null;
    }
}