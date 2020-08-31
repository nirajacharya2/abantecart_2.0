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
namespace abc\extensions\gdpr\models\admin\extension;

use abc\core\ABC;
use abc\core\engine\Model;
use abc\models\customer\Customer;

/**
 * Class ModelExtensionGdpr
 *
 * @package abc\extensions\gdpr\model\admin
 */
class ModelExtensionGdpr extends Model
{

    public $data = [];
    public $error = [];

    public function erase($customer_id)
    {
        $customer_id = (int)$customer_id;
        if (!$customer_id) {
            return false;
        }
        $customer_id = (int)$customer_id;

        $customer_info = Customer::find($customer_id);
        if (!$customer_info) {
            return false;
        }

        $data_map = ABC::env('GDPR_DATAMAP');
        $table_names = array_keys($data_map);
        foreach ($table_names as &$name) {
            $name = $this->db->table_name($this->db->escape($name));
        }

        $sql = "SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
                FROM information_schema.COLUMNS c
                WHERE c.`TABLE_SCHEMA` = '".$this->db->getDatabaseName()."'
                    AND c.`TABLE_NAME` IN ('".implode("','", $table_names)."')
                ORDER BY TABLE_NAME, COLUMN_NAME";
        $result = $this->db->query($sql);
        $tables = [];
        foreach ($result->rows as $row) {
            $tables[$row['TABLE_NAME']][$row['COLUMN_NAME']] = [
                'DATA_TYPE' => $row['DATA_TYPE'],
                'MAXLENGTH' => $row['CHARACTER_MAXIMUM_LENGTH'],
            ];
        }

        $output = [];
        $this->db->beginTransaction();
        try {
            foreach ($data_map as $table_name => $columns) {
                $cfg_cols = $columns;
                $updateData = $data = [];
                if ($table_name == 'customers') {
                    $updateData['status'] = 0;
                }
                foreach ($columns as $column_name) {
                    if (in_array($column_name, $cfg_cols)) {
                        $data_type = $tables[$this->db->table_name($table_name)][$column_name]['DATA_TYPE'];

                        if (is_int(strpos($column_name, 'loginname'))) {
                            $value = 'erased_'.$customer_id;
                        } elseif (is_int(strpos($column_name, 'email'))) {
                            $value = 'erased_'.$customer_id.'@eeeeeeeee.info';
                        } elseif (in_array($data_type, ['varchar', 'text', 'longtext', 'char'])) {
                            $value = $this->db->escape('**erased**');
                        } elseif (in_array($data_type, ['int', 'bigint', 'smallint', 'decimal', 'double'])) {
                            $value = 0;
                        } elseif (in_array($data_type, ['timestamp', 'datetime'])) {
                            $value = null;
                        } else {
                            $value = '';
                        }
                        $data[$column_name] = $value;

                        $updateData[$column_name] = $value;
                    }
                }
                if (!$updateData) {
                    continue;
                }

                $this->db->table($table_name)
                         ->where('customer_id', '=', $customer_id)
                         ->update($updateData);
            }


            $this->saveHistory(
                [
                    'customer_id'     => $customer_id,
                    'request_type'    => 'e',
                    'email'           => $customer_info['email'],
                    'name'            => $customer_info['firstname'].' '.$customer_info['lastname'],
                    'user_agent'      => 'Erased by '
                                            .$this->user->getUserFirstName()
                                            .' '
                                            .$this->user->getUserLastName()
                                            .' (ID '.$this->user->getId().')',
                    'accept_language' => '',
                    'ip'              => $this->request->getRemoteIP(),
                    'server_ip'       => $this->request->server['SERVER_ADDR'],
                ]
            );
            $this->db->commit();

        } catch (\Exception $e) {
            $this->log->write('GDPR view data error: '.$sql.' File: '.__FILE__);
            $this->db->rollback();
        }


        return $output;

    }

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
            '".$this->db->escape($data['server_ip'])."'
        )";
        $result = $this->db->query($sql);
        if ($result) {
            return (int)$this->db->getLastId();
        } else {
            return false;
        }
    }

    public function getHistory(array $data, $mode = '')
    {
        if (!$data) {
            return false;
        }

        if ($mode == 'total_only') {
            $sql = "SELECT COUNT(*) as total ";
        } else {
            $sql = "SELECT * ";
        }

        $sql .= " FROM ".$this->db->table_name('gdpr_history');
        $sql .= ' WHERE 1=1 ';
        if (!empty($data['subsql_filter'])) {
            $sql .= " AND ".$data['subsql_filter'];
        }

        //If for total, we done building the query
        if ($mode == 'total_only') {
            $query = $this->db->query($sql);
            return $query->row['total'];
        }

        $sort_data = [
            'customer_id'   => 'customer_id',
            'name'          => "CONCAT(firstname, ' ', lastname )",
            'type'          => 'request_type',
            'date_modified' => 'date_modified',
        ];

        if (isset($data['sort']) && array_key_exists($data['sort'], $sort_data)) {
            $sql .= " ORDER BY ".$sort_data[$data['sort']];
        } else {
            //for faster SQL default to ID based order
            $sql .= " ORDER BY date_modified";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
            $sql .= " LIMIT ".(int)$data['start'].",".(int)$data['limit'];
        }
        $query = $this->db->query($sql);
        return $query->rows;

    }

    public function getHistoryTotalRows($filter)
    {
        return $this->getHistory($filter, 'total_only');
    }

}
