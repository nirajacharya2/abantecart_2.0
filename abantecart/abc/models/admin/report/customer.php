<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2019 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\models\admin;

use abc\core\engine\Model;
use Exception;
use H;

class ModelReportCustomer extends Model
{
    /**
     * @param array $data
     * @param string $mode
     *
     * @return array|int
     * @throws Exception
     */
    public function getOnlineCustomers($data = [], $mode = 'default')
    {
        if ($mode == 'total_only') {
            $total_sql = 'SELECT co.ip, co.customer_id as total';
        } else {
            $total_sql = "SELECT  c.status, 
                    co.ip, co.customer_id, 
                    CONCAT(c.firstname, ' ', c.lastname) as customer, 
                    co.url, co.referer, 
                    co.date_added 
        ";
        }

        $sql = $total_sql." FROM ".$this->db->table_name("online_customers")." co 
                            LEFT JOIN ".$this->db->table_name("customers")." c ON (co.customer_id = c.customer_id) ";

        $where = '';
        if ( ! empty($data['subsql_filter'])) {
            $where .= " ".$data['subsql_filter'];
        }
        if ($where) {
            $sql .= " WHERE ".$where;
        }

        //If for total, we done building the query
        if ($mode == 'total_only') {
            $query = $this->db->query($sql);
            $total = 0;
            //prevent duplicates of logged customers by different ip's
            foreach ($query->rows as $row) {
                if ( ! isset($total[$row['customer_id']]) || ! $total[$row['customer_id']]) {
                    $total++;
                }
            }

            return $total;
        }

        $sort_data = [
            'customer'    => 'customer',
            'ip'          => 'co.ip',
            'url'         => 'co.url',
            'date_added ' => 'co.date_added ',
        ];

        if (isset($data['sort']) && array_key_exists($data['sort'], $sort_data)) {
            $sql .= " ORDER BY ".$sort_data[$data['sort']];
        } else {
            $sql .= " ORDER BY co.date_added";
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

    /**
     * @param array $data
     *
     * @return int
     * @throws Exception
     */
    public function getTotalOnlineCustomers($data = [])
    {
        return $this->getOnlineCustomers($data, 'total_only');
    }

    /**
     * @param array $data
     * @param string $mode
     *
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public function getCustomerOrders($data = [], $mode = 'default')
    {
        if ($mode == 'total_only') {
            $total_sql = 'SELECT COUNT(DISTINCT o.customer_id) as total';
        } else {
            $total_sql
                = "SELECT 	o.customer_id, 
                                    CONCAT(o.firstname, ' ', o.lastname) AS customer, 
                                    COALESCE(cg.name, 'N/A') AS customer_group, 
                                    c.status, 
                                    COUNT(DISTINCT o.order_id) AS order_count, 
                                    SUM(o.total) AS `total`
                        ";
        }

        $sql = $total_sql." FROM `".$this->db->table_name("orders")."` o 
                                LEFT JOIN `".$this->db->table_name("customers")."` c ON (o.customer_id = c.customer_id) 
                                LEFT JOIN `".$this->db->table_name("customer_groups")."` cg ON (o.customer_group_id = cg.customer_group_id) 
                            ";

        $filter = (isset($data['filter']) ? $data['filter'] : []);
        $implode = [];
        $where = '';
        if (H::has_value($filter['order_status'])) {
            $implode[] = " o.order_status_id = ".(int)$filter['order_status']." ";
        }

        if (H::has_value($filter['customer_id'])) {
            $implode[] = " o.customer_id = ".(int)$filter['customer_id']." ";
        }
        if ( ! empty($filter['date_start'])) {
            $date_start = H::dateDisplay2ISO($filter['date_start'], $this->language->get('date_format_short'));
            $implode[] = " DATE_FORMAT(o.date_added,'%Y-%m-%d') >= DATE_FORMAT('".$this->db->escape($date_start)."','%Y-%m-%d') ";
        }
        if ( ! empty($filter['date_end'])) {
            $date_end = H::dateDisplay2ISO($filter['date_end'], $this->language->get('date_format_short'));
            $implode[] = " DATE_FORMAT(o.date_added,'%Y-%m-%d') <= DATE_FORMAT('".$this->db->escape($date_end)."','%Y-%m-%d') ";
        }
        //filter for first and last name
        if (H::has_value($filter['customer'])) {
            $implode[] = "CONCAT(o.firstname, ' ', o.lastname) LIKE '%".$this->db->escape($filter['customer'], true)."%' collate utf8_general_ci";
        }

        if ($implode) {
            $where .= implode(" AND ", $implode);
        }

        if ($where) {
            $sql .= " WHERE ".$where;
        }

        //If for total, we done building the query
        if ($mode == 'total_only') {

            $query = $this->db->query($sql);

            return $query->row['total'];
        }

        $sql .= " GROUP BY o.customer_id ";

        $sort_data = [
            'customer_group' => 'cg.name',
            'orders'         => 'COUNT(o.order_id)',
            'products '      => 'SUM(op.quantity)',
            'total'          => 'SUM(o.total)',
        ];

        if (isset($data['sort']) && array_key_exists($data['sort'], $sort_data)) {
            $sql .= " ORDER BY ".$sort_data[$data['sort']];
        } else {
            $sql .= " ORDER BY c.customer_id";
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

    /**
     * @param array $data
     *
     * @return int
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getTotalCustomerOrders($data = [])
    {
        return $this->getCustomerOrders($data, 'total_only');
    }

    /**
     * @param array $data
     * @param string $mode
     *
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public function getCustomerTransactions($data = [], $mode = 'default')
    {
        if ($mode == 'total_only') {
            $sql = 'SELECT COUNT(DISTINCT c.customer_id) as total';
        } else {
            $sql = "SELECT ".$this->db->raw_sql_row_count()." ct.customer_transaction_id,
                                    c.customer_id,
                                    CONCAT(c.firstname, ' ', c.lastname) AS customer,
                                    ct.date_added,
                                    c.status, 
                                    ct.debit,
                                    ct.credit,
                                    ct.date_added,
                                    ct.transaction_type,
                                    u.username as created_by
                        ";
        }

        $sql .= " FROM `".$this->db->table_name("customer_transactions")."` ct 
            LEFT JOIN `".$this->db->table_name("customers")."` c ON (ct.customer_id = c.customer_id) 
            LEFT JOIN `".$this->db->table_name("users")."` u ON u.user_id = ct.created_by 
        ";

        $filter = ($data['filter'] ?? []);
        $implode = [];
        $where = '';
        if (H::has_value($filter['customer_id'])) {
            $implode[] = " c.customer_id = ".(int)$filter['customer_id']." ";
        }
        if ( $filter['date_start'] && $filter['date_end']) {
            $implode[] = " ct.date_added BETWEEN '".$this->db->escape($filter['date_start'])."' AND '".$this->db->escape($filter['date_end'])." 23:59:59'";
        }

        //filter for first and last name
        if (H::has_value($filter['customer'])) {
            $implode[] = "CONCAT(c.firstname, ' ', c.lastname) LIKE '%".$this->db->escape($filter['customer'], true)."%' collate utf8_general_ci";
        }

        if ($implode) {
            $where .= implode(" AND ", $implode);
        }

        if ( ! empty($data['subsql_filter'])) {
            $where .= " ".$data['subsql_filter'];
        }

        if ($where) {
            $sql .= " WHERE ".$where;
        }

        //If for total, we're done building the query
        if ($mode == 'total_only') {
            $query = $this->db->query($sql);

            return $query->row['total'];
        }

        $sort_data = [
            'transaction_type' => 'ct.transaction_type',
            'debit'            => 'ct.debit',
            'credit'           => 'ct.credit',
            'date_added'       => 'ct.date_added',
        ];

        if (isset($data['sort']) && array_key_exists($data['sort'], $sort_data)) {
            $sql .= " ORDER BY ".$sort_data[$data['sort']];
        } else {
            $sql .= " ORDER BY ct.date_added";
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
            $sql .= " LIMIT ".(int)$data['start'].",".(int)$data['limit'].";";
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * @param array $data
     *
     * @return mixed
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getTotalCustomerTransactions($data = [])
    {
        return $this->getCustomerTransactions($data, 'total_only');
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getCustomersCountByDay()
    {
        $customer_data = [];
        for ($i = 0; $i < 24; $i++) {
            $customer_data[$i] = [
                'hour'  => $i,
                'total' => 0,
            ];
        }
        $query = $this->db->query(
            "SELECT COUNT(*) AS total, HOUR(date_added) AS hour 
                FROM `".$this->db->table_name("customers")."` 
                WHERE DATE(date_added) = DATE(NOW()) 
                GROUP BY HOUR(date_added) 
                ORDER BY date_added ASC");
        foreach ($query->rows as $result) {
            $customer_data[$result['hour']] = [
                'hour'  => $result['hour'],
                'total' => $result['total'],
            ];
        }

        return $customer_data;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getCustomersCountByWeek()
    {
        $customer_data = [];
        $date_start = strtotime('-'.date('w').' days');
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', $date_start + ($i * 86400));
            $order_data[date('w', strtotime($date))] = [
                'day'   => date('D', strtotime($date)),
                'total' => 0,
            ];
        }
        $query = $this->db->query(
            "SELECT COUNT(*) AS total, date_added 
                FROM `".$this->db->table_name("customers")."` 
                WHERE DATE(date_added) >= DATE('".$this->db->escape(date('Y-m-d', $date_start))."') 
                GROUP BY DAYNAME(date_added)");
        foreach ($query->rows as $result) {
            $customer_data[date('w', strtotime($result['date_added']))] = [
                'day'   => date('D', strtotime($result['date_added'])),
                'total' => $result['total'],
            ];
        }

        return $customer_data;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getCustomersCountByMonth()
    {
        $customer_data = [];
        for ($i = 1; $i <= date('t'); $i++) {
            $date = date('Y').'-'.date('m').'-'.$i;
            $customer_data[date('j', strtotime($date))] = [
                'day'   => date('d', strtotime($date)),
                'total' => 0,
            ];
        }
        $query = $this->db->query(
            "SELECT COUNT(*) AS total, date_added 
                FROM `".$this->db->table_name("customers")."` 
                WHERE DATE(date_added) >= '".$this->db->escape(date('Y').'-'.date('m').'-1')."' 
                GROUP BY DATE(date_added)");

        foreach ($query->rows as $result) {
            $customer_data[date('j', strtotime($result['date_added']))] = [
                'day'   => date('d', strtotime($result['date_added'])),
                'total' => $result['total'],
            ];
        }

        return $customer_data;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getCustomersCountByYear()
    {
        $customer_data = [];
        for ($i = 1; $i <= 12; $i++) {
            $customer_data[$i] = [
                'month' => date('M', mktime(0, 0, 0, $i)),
                'total' => 0,
            ];
        }
        $query = $this->db->query(
            "SELECT COUNT(*) AS total, date_added 
            FROM `".$this->db->table_name("customers")."` 
            WHERE YEAR(date_added) = YEAR(NOW()) 
            GROUP BY MONTH(date_added)");
        foreach ($query->rows as $result) {
            $customer_data[date('n', strtotime($result['date_added']))] = [
                'month' => date('M', strtotime($result['date_added'])),
                'total' => $result['total'],
            ];
        }

        return $customer_data;
    }
    
    public function clearOnlineCustomers()
    {
        $sql = "DELETE FROM ".$this->db->table_name("online_customers");
        $this->db->query($sql);
    }
}