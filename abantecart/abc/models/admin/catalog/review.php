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

namespace abc\models\admin;

use abc\core\engine\Model;

if (!class_exists('abc\core\ABC') || !\abc\core\ABC::env('IS_ADMIN')) {
    header('Location: static_pages/?forbidden='.basename(__FILE__));
}

class ModelCatalogReview extends Model
{
    /**
     * @param array $data
     *
     * @return int
     * @throws \Exception
     */
    public function addReview($data)
    {
        $this->db->query("INSERT INTO ".$this->db->table_name("reviews")." 
                          SET author = '".$this->db->escape($data['author'])."',
                              product_id = '".$this->db->escape($data['product_id'])."',
                              text = '".$this->db->escape(strip_tags($data['text']))."',
                              rating = '".(int)$data['rating']."',
                              status = '".(int)$data['status']."',
                              date_added = NOW()");
        $this->cache->flush('product');
        return $this->db->getLastId();
    }

    /**
     * @param int $review_id
     * @param array $data
     *
     * @throws \Exception
     */
    public function editReview($review_id, $data)
    {

        $allowFields = ['product_id', 'customer_id', 'author', 'text', 'rating', 'status', 'date_added'];
        $update_data = [' date_modified = NOW() '];
        foreach ($data as $key => $val) {
            if (in_array($key, $allowFields)) {
                $update_data[] = "`$key` = '".$this->db->escape($val)."' ";
            }
        }

        $this->db->query("UPDATE ".$this->db->table_name("reviews")." 
                          SET ".implode(',', $update_data)."
                          WHERE review_id = '".(int)$review_id."'");
        $this->cache->flush('product');
    }

    /**
     * @avoid
     *
     * @param int $review_id
     *
     * @throws \Exception
     */
    public function deleteReview($review_id)
    {

        $this->db->query("DELETE FROM ".$this->db->table_name("reviews")." WHERE review_id = '".(int)$review_id."'");
        $this->cache->flush('product');
    }

    /**
     * @param int $review_id
     *
     * @return array
     * @throws \Exception
     */
    public function getReview($review_id)
    {
        $query = $this->db->query(
            "SELECT DISTINCT *
                 FROM ".$this->db->table_name("reviews")."
                 WHERE review_id = '".(int)$review_id."'");
        return $query->row;
    }

    /**
     * @param array $data
     * @param string $mode
     *
     * @return array|int
     * @throws \Exception
     */
    public function getReviews($data = [], $mode = 'default')
    {

        if ($mode == 'total_only') {
            $total_sql = 'COUNT(*) as total';
        } else {
            $total_sql = 'r.review_id, r.product_id, pd.name, r.author, r.rating, r.status, r.date_added';
        }
        $filter = (isset($data['filter']) ? $data['filter'] : []);
        $join = '';
        if (isset($filter['store_id']) && $filter['store_id'] !== null) {
            $join = " INNER JOIN ".$this->db->table_name("products_to_stores")." p2s 
            ON (p2s.product_id = r.product_id AND p2s.store_id = '".(int)$filter['store_id']."')";
        }
        $sql = "SELECT ".$total_sql."
                FROM ".$this->db->table_name("reviews")." r
                LEFT JOIN ".$this->db->table_name("product_descriptions")." pd
                    ON (r.product_id = pd.product_id AND pd.language_id = '"
            .(int)$this->language->getContentLanguageID()."')
                LEFT JOIN ".$this->db->table_name("products")." p ON (r.product_id = p.product_id)
                ".$join."
                WHERE 1=1 ";

        if (isset($filter['product_id']) && !is_null($filter['product_id'])) {
            $sql .= " AND r.product_id = '".(int)$filter['product_id']."'";
        }

        if (isset($filter['status']) && !is_null($filter['status'])) {
            $sql .= " AND r.status = '".(int)$filter['status']."'";
        }

        if (!empty($data['subsql_filter'])) {
            $sql .= " AND ".$data['subsql_filter'];
        }

        //If for total, we done building the query
        if ($mode == 'total_only') {
            $query = $this->db->query($sql);
            return $query->row['total'];
        }

        $sort_data = [
            'name'       => 'pd.name',
            'author'     => 'r.author',
            'rating'     => 'r.rating',
            'status'     => 'r.status',
            'date_added' => 'r.date_added',
        ];

        if (isset($data['sort']) && in_array($data['sort'], array_keys($sort_data))) {
            $sql .= " ORDER BY ".$data['sort'];
        } else {
            $sql .= " ORDER BY r.date_added";
        }

        if (isset($data['order']) && (strtoupper($data['order']) == 'DESC')) {
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
     * @throws \Exception
     */
    public function getTotalReviews($data = [])
    {
        return $this->getReviews($data, 'total_only');
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getTotalReviewsAwaitingApproval()
    {
        $query = $this->db->query(
            "SELECT COUNT(*) AS total
                 FROM ".$this->db->table_name("reviews")."
                 WHERE status = '0'");

        return (int)$query->row['total'];
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getTotalToday()
    {
        $sql = "SELECT count(*) as total
                FROM `".$this->db->table_name("reviews")."` r
                WHERE DATE_FORMAT(r.date_added,'%Y-%m-%d') = DATE_FORMAT(now(),'%Y-%m-%d') ";
        $query = $this->db->query($sql);
        return (int)$query->row['total'];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getReviewProducts()
    {
        $sql = "SELECT DISTINCT r.product_id, pd.name
                FROM ".$this->db->table_name("reviews")." r
                LEFT JOIN ".$this->db->table_name("product_descriptions")." pd
                    ON (r.product_id = pd.product_id AND pd.language_id = '"
            .(int)$this->language->getContentLanguageID()."')";
        $query = $this->db->query($sql);

        $result = [];
        foreach ($query->rows as $row) {
            $result[$row['product_id']] = $row['name'];
        }

        return $result;
    }
}
