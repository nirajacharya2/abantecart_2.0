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
namespace abc\extensions\gdpr\models\storefront\extension;

use abc\core\ABC;
use abc\core\engine\Model;

class ModelExtensionGdpr extends Model
{

    public $data = [];
    public $error = [];

    /**
     * @param $customer_id
     *
     * @return array
     * @throws \Exception
     */
    public function getPersonalData($customer_id)
    {
        $customer_id = (int)$customer_id;
        $data_map = ABC::env('GDPR_DATAMAP');

        $table_names = array_keys($data_map);
        foreach ($table_names as &$name) {
            $name = $this->db->table_name($this->db->escape($name));
        }

        $sql = "SELECT TABLE_NAME, COLUMN_NAME
                FROM information_schema.COLUMNS c
                WHERE c.`TABLE_SCHEMA` = '".$this->db->getDatabaseName()."'
                    AND c.`TABLE_NAME` IN ('".implode("','", $table_names)."')
                ORDER BY TABLE_NAME, COLUMN_NAME";
        $result = $this->db->query($sql);
        $tables = [];
        foreach ($result->rows as $row) {
            $tables[$row['TABLE_NAME']][] = $row['COLUMN_NAME'];
        }

        $output = [];
        foreach ($data_map as $table_name => $columns) {
            try {
                $sql = "SELECT ".implode(", ", array_intersect($columns, $tables[$this->db->table_name($table_name)]))."
                        FROM ".$this->db->table_name($table_name)."
                        WHERE customer_id = ".$customer_id;
                $result = $this->db->query($sql);
                $rows = $result->rows;
                $output[$table_name] = $rows;
            } catch (\Exception $e) {
                $this->log->write('GDPR view data error: '.$sql.' File: '.__FILE__);
            }
        }
        return $output;
    }

    /**
     * @param array $data
     *
     * @return bool|int
     * @throws \Exception
     */
    public function saveHistory(array $data)
    {
        if (!$data) {
            return false;
        }

        $sql = "INSERT INTO ".$this->db->table_name('gdpr_history')."
                (customer_id,
                request_type,
                email,
                name,
                user_agent,
                accept_language,
                ip,
                server_ip)
        VALUES (
            ".(int)$data['customer_id'].",
            '".$this->db->escape($data['request_type'])."',
            '".$this->db->escape($data['email'])."',
            '".$this->db->escape($data['name'])."',
            '".$this->db->escape($data['user_agent'])."',
            '".$this->db->escape($data['accept_language'])."',
            '".$this->db->escape($data['ip'])."',
            '".$this->db->escape($data['server_ip'])."')";
        $result = $this->db->query($sql);
        if ($result) {
            return (int)$this->db->getLastId();
        } else {
            return false;
        }
    }
}
