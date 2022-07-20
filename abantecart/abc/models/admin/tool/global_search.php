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

namespace abc\models\admin;

use abc\core\ABC;
use abc\core\engine\Model;
use abc\core\lib\AdminCommands;

/**
 * Class ModelToolGlobalSearch
 *
 * @package abc\models\admin
 */
class ModelToolGlobalSearch extends Model
{
    /**
     * registry to provide access to cart objects
     *
     * @var object Registry
     */
    public $registry;

    /**
     * commands available in the system
     *
     * @var array
     */
    public $commands;

    /**
     * array with descriptions of controller for search
     *
     * @var array
     */
    public $results_controllers
        = [
            "commands"           => [],
            "orders"             => [
                'alias'    => 'order',
                'id'       => 'order_id',
                'page'     => 'sale/order/details',
                'response' => '',
            ],
            "customers"          => [
                'alias'    => 'customer',
                'id'       => 'customer_id',
                'page'     => 'sale/customer/update',
                'response' => '',
            ],
            "product_categories" => [
                'alias'    => 'category',
                'id'       => 'category_id',
                'page'     => 'catalog/category/update',
                'response' => '',
            ],
            "products"           => [
                'alias'    => 'product',
                'id'       => 'product_id',
                'page'     => 'catalog/product/update',
                'response' => '',
            ],
            "reviews"            => [
                'alias'    => 'review',
                'id'       => 'review_id',
                'page'     => 'catalog/review/update',
                'response' => '',
            ],
            "manufacturers"      => [
                'alias'    => 'brand',
                'id'       => 'manufacturer_id',
                'page'     => 'catalog/manufacturer/update',
                'response' => '',
            ],
            "languages"          => [
                'alias'        => 'language',
                'id'           => 'language_definition_id',
                'extra_fields' => ['language_id'],
                'page'         => 'localisation/language_definition_form/update',
                'response'     => 'localisation/language_definition_form/update',
            ],
            "pages"              => [
                'alias'    => 'information',
                'id'       => ['page_id', 'layout_id', 'tmpl_id'],
                'page'     => 'design/layout',
                'response' => '',
            ],
            "settings"           => [
                'alias'    => 'setting',
                'id'       => ['setting_id', 'active', 'store_id'],
                'page'     => 'setting/setting',
                'response' => 'setting/setting_quick_form',
            ],
            "messages"           => [
                'alias'    => 'information',
                'id'       => 'msg_id',
                'page'     => 'tool/message_manager',
                'response' => '',
            ],
            "extensions"         => [
                'alias'    => 'extension',
                'id'       => 'extension',
                'page'     => 'extension/extensions/edit',
                'page2'    => 'total/%s',
                'response' => '',
            ],
            "downloads"          => [
                'alias'    => 'download',
                'id'       => 'download_id',
                'page'     => 'catalog/download/update',
                'response' => '',
            ],
            "contents"           => [
                'alias'    => 'content',
                'id'       => 'content_id',
                'page'     => 'design/content/update',
                'response' => '',
            ],
        ];

    public function __construct($registry)
    {
        parent::__construct($registry);

        $text_data = $this->language->getASet('common/action_commands');
        $keys = preg_grep("/^command.*/", array_keys($text_data));
        foreach ($keys as $key) {
            $this->commands[$key] = $text_data[$key];
        }
    }

    /**
     * function returns list of accessible search categories
     *
     * @param string $keyword
     *
     * @return array
     */
    public function getSearchSources($keyword = '')
    {
        $search_categories = [];
        // limit of keyword length
        if (mb_strlen($keyword) >= 1) {
            foreach ($this->results_controllers as $k => $item) {
                $search_categories[$k] = $item['alias'];
            }
        }

        return $search_categories;
    }

    /**
     * function returns total counts of search results
     *
     * @param string $search_category
     * @param string $keyword
     *
     * @return int
     * @throws \Exception
     */
    public function getTotal($search_category, $keyword)
    {

        // two variants of needles for search: with and without html-entities
        $needle = $this->db->escape(mb_strtolower(htmlentities($keyword, ENT_QUOTES)), true);
        $needle2 = $this->db->escape(mb_strtolower($keyword), true);

        $language_id = (int)$this->config->get('storefront_language_id');

        $all_languages = $this->language->getActiveLanguages();
        $current_store_id =
            !isset($this->session->data['current_store_id'])
            ? 0
            : $this->session->data['current_store_id'];
        $search_languages = [];
        foreach ($all_languages as $l) {
            $search_languages[] = (int)$l['language_id'];
        }

        $output = [];

        switch ($search_category) {
            case 'commands' :
                $output = $this->possibleCommands($needle, 'total');
                break;
            case 'product_categories' :
                $sql = "SELECT count(*) AS total
                        FROM ".$this->db->table_name("category_descriptions")." c 
                        WHERE (LOWER(c.name) LIKE '%".$needle."%'
                                OR LOWER(c.name) LIKE '%".$needle2."%' )
                        AND c.language_id IN (".(implode(",", $search_languages)).");";
                $result = $this->db->query($sql);
                $output = $result->row ['total'];
                break;

            case 'languages' :
                $sql = "SELECT count(*) AS total
                        FROM ".$this->db->table_name("language_definitions")." l
                        WHERE (LOWER(l.language_value) LIKE '%".$needle."%'
                                OR LOWER(l.language_value) LIKE '%".$needle2."%'
                                OR LOWER(l.language_key) LIKE '%".$needle."%'
                                OR LOWER(l.language_key) LIKE '%".str_replace(' ', '_', $needle)."%' )
                            AND l.language_id IN (".implode(",", $search_languages).")";
                $result = $this->db->query($sql);
                $output = $result->row ['total'];
                break;

            case 'products' :
                $sql = "SELECT a.product_id
                        FROM ".$this->db->table_name("products")." a
                        LEFT JOIN ".$this->db->table_name("product_descriptions")." b 
                            ON (b.product_id = a.product_id 
                                AND b.language_id IN (".(implode(",", $search_languages)).")
                                )
                        WHERE LOWER(a.model) LIKE '%".$needle."%' OR LOWER(a.model) LIKE '%".$needle2."%'
                        UNION
                        SELECT product_id
                        FROM ".$this->db->table_name("product_descriptions")." pd1
                        WHERE ( LOWER(pd1.name) LIKE '%".$needle."%' OR LOWER(pd1.name) LIKE '%".$needle2."%' )
                            AND pd1.language_id	IN (".(implode(",", $search_languages)).")
                        UNION
                        SELECT DISTINCT a.product_id
                        FROM ".$this->db->table_name("product_option_value_descriptions")." a
                        LEFT JOIN ".$this->db->table_name("product_descriptions")." b 
                            ON (b.product_id = a.product_id AND b.language_id	IN (".(implode(",", $search_languages))
                    ."))
                        WHERE ( LOWER(a.name) LIKE '%".$needle."%' OR LOWER(a.name) LIKE '%".$needle2."%' )
                            AND a.language_id IN (".(implode(",", $search_languages)).")
                        UNION
                        SELECT DISTINCT a.product_id
                        FROM ".$this->db->table_name("product_tags")." a
                        LEFT JOIN ".$this->db->table_name("product_descriptions")." b 
                            ON (b.product_id = a.product_id AND b.language_id	IN (".(implode(",", $search_languages))
                    ."))
                        WHERE ( LOWER(a.tag) LIKE '%".$needle."%' OR LOWER(a.tag) LIKE '%".$needle2."%' )
                            AND a.language_id = ".$language_id;

                $result = $this->db->query($sql);
                if ($result->num_rows) {
                    foreach ($result->rows as $row) {
                        $output [$row ['product_id']] = 0;
                    }
                }
                $output = sizeof($output);
                break;

            case 'reviews' :
                $sql
                    = "SELECT DISTINCT product_id
                        FROM ".$this->db->table_name("reviews")." r
                        WHERE (LOWER(`text`) LIKE '%".$needle."%')
                                OR (LOWER(r.`author`) LIKE '%".$needle."%') ";

                $result = $this->db->query($sql);
                if ($result->num_rows) {
                    foreach ($result->rows as $row) {
                        $output [$row ['product_id']] = 0;
                    }
                }
                $output = sizeof($output);
                break;

            case "manufacturers" :
                $sql = "SELECT count(*) AS total
                        FROM ".$this->db->table_name("manufacturers")." 
                        WHERE (LOWER(name) LIKE '%".$needle."%')";

                $result = $this->db->query($sql);
                $output = $result->row ['total'];

                break;
            case "orders" :
                $sql = "SELECT COUNT(DISTINCT order_id) AS total
                        FROM ".$this->db->table_name("orders")." 
                        WHERE ((LOWER(invoice_prefix) LIKE '%".$needle."%')
                            OR (LOWER(firstname) LIKE '%".$needle."%')
                            OR (LOWER(lastname) LIKE '%".$needle."%')
                            OR (LOWER(email) LIKE '".$needle."%')
                            OR (LOWER(shipping_address_1) LIKE '%".$needle."%')
                            OR (LOWER(shipping_address_2) LIKE '%".$needle."%')
                            OR (LOWER(payment_address_1) LIKE '%".$needle."%')
                            OR (LOWER(payment_address_2) LIKE '%".$needle."%')
                            OR order_id= '".(int)$needle."'
                            )
                        AND language_id = ".$language_id;
                $result = $this->db->query($sql);
                $output = $result->row ['total'];

                break;
            case "customers" :
                $sql = "SELECT COUNT(customer_id) AS total
                        FROM ".$this->db->table_name("customers")." 
                        WHERE ((LOWER(firstname) LIKE '%".$needle."%')
                            OR (LOWER(lastname) LIKE '%".$needle."%')
                            OR (LOWER(email) LIKE '%".$needle."%')
                            )";

                $result = $this->db->query($sql);
                $output = $result->row ['total'];

                break;
            case "pages" :
                $sql = "SELECT COUNT(DISTINCT p.page_id) AS total
                        FROM ".$this->db->table_name("pages")." p 
                        LEFT JOIN ".$this->db->table_name("page_descriptions")." b 
                            ON (p.page_id = b.page_id AND b.language_id	IN (".(implode(",", $search_languages))."))
                        WHERE
                            ((LOWER(b.name) LIKE '%".$needle."%')
                            OR (LOWER(b.title) LIKE '%".$needle."%')
                            OR (LOWER(b.keywords) LIKE '%".$needle."%'))";
                $result = $this->db->query($sql);
                $output = $result->row ['total'];
                break;

            case "settings" :
                $sql = "SELECT count(*) AS total
                        FROM ".$this->db->table_name("settings")." s
                        LEFT JOIN ".$this->db->table_name("extensions")." e ON s.`group` = e.`key`
                        LEFT JOIN ".$this->db->table_name("language_definitions")." l
                                        ON l.language_key LIKE CONCAT(s.`key`,'%')
                        WHERE (LOWER(`value`) LIKE '%".$needle."%')
                                OR
                                (LOWER(s.`key`) LIKE '%".$needle."%')
                            AND s.`store_id` ='".( int )$current_store_id."'
                        UNION
                        SELECT COUNT(s.setting_id) AS total
                        FROM ".$this->db->table_name("language_definitions")." l
                        LEFT JOIN ".$this->db->table_name("settings")." s 
                            ON l.language_key = CONCAT('entry_',REPLACE(s.`key`,'config_',''))
                        WHERE (LOWER(l.language_value) LIKE '%".$needle."%'
                                OR LOWER(l.language_value) LIKE '%".$needle."%'
                                OR LOWER(l.language_key) LIKE '%".$needle."%' )
                            AND block='setting_setting'
                            AND l.language_id ='".$language_id."'
                            AND s.`store_id` ='".( int )$current_store_id."'
                            AND setting_id>0";
                $result = $this->db->query($sql);
                $output = 0;
                foreach ($result->rows as $row) {
                    $output += (int)$row['total'];
                }
                break;
            case "messages" :
                $sql = "SELECT COUNT(DISTINCT msg_id) AS total
                        FROM ".$this->db->table_name("messages")." 
                        WHERE (LOWER(`title`) LIKE '%".$needle."%' OR LOWER(`message`) LIKE '%".$needle."%')";
                $result = $this->db->query($sql);
                $output = $result->row ['total'];
                break;
            case "extensions" :
                $sql = "SELECT COUNT( DISTINCT `key`) AS total
                        FROM ".$this->db->table_name("extensions")." 
                        WHERE LOWER(`key`) LIKE '%".$needle."%' AND `type` <> 'total'";
                $result = $this->db->query($sql);
                $output = $result->row ['total'];
                break;
            case "downloads" :
                $sql = "SELECT COUNT( DISTINCT d.download_id) AS total
                        FROM ".$this->db->table_name("downloads")." d
                        RIGHT JOIN ".$this->db->table_name("download_descriptions")." dd
                            ON (d.download_id = dd.download_id AND dd.language_id IN (".(implode(",",
                        $search_languages))."))
                        WHERE (LOWER(`name`) LIKE '%".$needle."%')";
                $result = $this->db->query($sql);
                $output = $result->row ['total'];
                break;
            case "contents" :
                $sql = "SELECT COUNT( DISTINCT c.content_id) AS total
                        FROM ".$this->db->table_name("contents")." c
                        RIGHT JOIN ".$this->db->table_name("content_descriptions")." cd
                            ON (c.content_id = cd.content_id 
                                AND cd.language_id IN (".(implode(",", $search_languages)).")
                                )
                        WHERE 
                            (LOWER(`name`) LIKE '%".$needle."%')
                            OR (LOWER(`title`) LIKE '%".$needle."%')
                            OR (LOWER(`description`) LIKE '%".$needle."%')
                            OR (LOWER(`content`) LIKE '%".$needle."%')
                        ";
                $result = $this->db->query($sql);
                $output = $result->row ['total'];
                break;
            default :
                break;
        }

        return $output;
    }

    /**
     * function returns search results in JSON format
     *
     * @param string $search_category
     * @param string $keyword
     * @param string $mode
     *
     * @return array
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getResult($search_category, $keyword, $mode = 'listing')
    {

        $language_id = (int)$this->config->get('storefront_language_id');

        // two variants of needles for search: with and without html-entities
        $needle = $this->db->escape(mb_strtolower(htmlentities($keyword, ENT_QUOTES)));
        $needle2 = $this->db->escape(mb_strtolower($keyword));

        $page = (int)$this->request->get_or_post('page');
        $rows = (int)$this->request->get_or_post('rows');

        if ($page) {
            $page = !$page ? 1 : $page;
            $offset = ($page - 1) * $rows;
            $rows_count = $rows;
        } else {
            $offset = 0;
            $rows_count = $mode == 'listing' ? 10 : 3;
        }

        $all_languages = $this->language->getActiveLanguages();
        $current_store_id = (int)$this->session->data['current_store_id'];
        $search_languages = [];
        foreach ($all_languages as $l) {
            $search_languages[] = (int)$l['language_id'];
        }

        switch ($search_category) {
            case 'commands' :
                $result = array_slice($this->possibleCommands($needle), $offset, $rows_count);
                break;

            case 'product_categories' :
                $sql = "SELECT
                            c.category_id,
                            c.name AS title,
                            c.name AS text,
                            c.meta_keywords AS text2,
                            c.meta_description AS text3,
                            c.description AS text4
                        FROM ".$this->db->table_name("category_descriptions")." c 
                        WHERE (LOWER(c.name) LIKE '%".$needle."%'
                                OR LOWER(c.name) LIKE '%".$needle2."%' )
                            AND c.language_id IN (".(implode(",", $search_languages)).")
                        LIMIT ".$offset.",".$rows_count;
                $result = $this->db->query($sql);
                $result = $result->rows;
                break;

            case 'languages' :
                $sql = "SELECT l.language_definition_id, 
                            l.language_key AS title, 
                            CONCAT_WS('  ',l.language_key,l.language_value) AS text, 
                            language_id
                        FROM ".$this->db->table_name("language_definitions")." l
                        WHERE ( LOWER(l.language_value) LIKE '%".$needle."%'
                                OR LOWER(l.language_value) LIKE '%".$needle2."%'
                                OR l.language_key LIKE '%".str_replace(' ', '_', $needle)."%'
                        )
                            AND l.language_id IN (".(implode(",", $search_languages)).")
                        LIMIT ".$offset.",".$rows_count;
                $result = $this->db->query($sql);
                $result = $result->rows;
                break;

            case 'products' :

                $sql = "SELECT a.product_id, b.name AS title, a.model AS text
                        FROM ".$this->db->table_name("products")." a
                        LEFT JOIN ".$this->db->table_name("product_descriptions")." b 
                            ON (b.product_id = a.product_id 
                                AND b.language_id IN (".(implode(",", $search_languages)).")
                                )
                        WHERE LOWER(a.model) LIKE '%".$needle."%'
                        ";
                if ($needle != $needle2) {
                    $sql .= " OR LOWER(a.model) like '%".$needle2."%' ";
                }
                $sql .= "
                        UNION
                        SELECT pd1.product_id, pd1.name as title, pd1.name as text
                        FROM ".$this->db->table_name("product_descriptions")." pd1
                        WHERE ( LOWER(pd1.name) like '%".$needle."%'
                        ";
                if ($needle != $needle2) {
                    $sql .= " OR LOWER(pd1.name) like '%".$needle2."%' ";
                }
                $sql .= " )
                            AND pd1.language_id IN (".(implode(",", $search_languages)).")
                        UNION
                        SELECT a.product_id, b.name as title, a.name as text
                        FROM ".$this->db->table_name("product_option_descriptions")." a
                        LEFT JOIN ".$this->db->table_name("product_descriptions")." b 
                            ON (b.product_id = a.product_id 
                                AND b.language_id IN (".(implode(",", $search_languages)).")
                                )
                        WHERE ( LOWER(a.name) like '%".$needle."%'
                        ";
                if ($needle != $needle2) {
                    $sql .= " OR LOWER(a.name) like '%".$needle2."%' ";
                }
                $sql .= ")
                            AND a.language_id IN (".(implode(",", $search_languages)).")
                        UNION
                        SELECT a.product_id, b.name as title, a.name as text
                        FROM ".$this->db->table_name("product_option_value_descriptions")." a
                        LEFT JOIN ".$this->db->table_name("product_descriptions")." b
                            ON (b.product_id = a.product_id 
                                AND b.language_id IN (".(implode(",", $search_languages)).")
                                )
                        WHERE ( LOWER(a.name) like '%".$needle."%'
                        ";
                if ($needle != $needle2) {
                    $sql .= " OR LOWER(a.name) like '%".$needle2."%' ";
                }
                $sql .= " )
                            AND a.language_id IN (".(implode(",", $search_languages)).")
                        UNION
                        SELECT a.product_id, b.name as title, a.tag as text
                        FROM ".$this->db->table_name("product_tags")." a
                        LEFT JOIN ".$this->db->table_name("product_descriptions")." b 
                            ON (b.product_id = a.product_id 
                                AND b.language_id IN (".(implode(",", $search_languages)).")
                                )
                        WHERE ( a.tag like '".$needle."%'
                        ";
                if ($needle != $needle2) {
                    $sql .= " OR a.tag like '".$needle2."%' ";
                }
                $sql .= " )
                            AND a.language_id IN (".(implode(",", $search_languages)).")
                        LIMIT ".$offset.",".$rows_count;

                $result = $this->db->query($sql);
                $table = [];
                if ($result->num_rows) {
                    foreach ($result->rows as $row) {
                        if (!isset($table [$row ['product_id']])) {
                            $table [$row ['product_id']] = $row;
                        }
                    }
                }
                $result = $table;
                break;
            case "reviews" :
                $sql = "SELECT review_id, r.`text`, pd.`name` AS title
                        FROM ".$this->db->table_name("reviews")." r
                        LEFT JOIN ".$this->db->table_name("product_descriptions")." pd
                            ON (pd.product_id = r.product_id 
                                AND pd.language_id IN (".(implode(",", $search_languages)).")
                                )
                        WHERE ( LOWER(r.`text`) LIKE '%".$needle."%'
                                OR LOWER(r.`author`) LIKE '%".$needle."%'
                        ";
                if ($needle != $needle2) {
                    $sql .= " OR LOWER(r.`text`) LIKE '%".$needle2."%'
                              OR LOWER(r.`author`) LIKE '%".$needle2."%' ";
                }
                $sql .= ") LIMIT ".$offset.",".$rows_count;
                $result = $this->db->query($sql);
                $result = $result->rows;
                break;
            case "manufacturers" :
                $sql = "SELECT manufacturer_id, `name` AS text, `name` AS title
                        FROM ".$this->db->table_name("manufacturers")." 
                        WHERE (LOWER(name) LIKE '%".$needle."%' OR LOWER(name) LIKE '%".$needle2."%' )
                        LIMIT ".$offset.",".$rows_count;
                $result = $this->db->query($sql);
                $result = $result->rows;
                break;
            case "orders" :
                $sql = "SELECT order_id, CONCAT('order #', order_id) AS title,
                            CONCAT(invoice_prefix,' ',firstname,' ',lastname,' ',email)  AS text
                        FROM ".$this->db->table_name("orders")." 
                        WHERE ((LOWER(invoice_prefix) LIKE '%".$needle."%')
                            OR (LOWER(firstname) LIKE '%".$needle."%')
                            OR (LOWER(lastname) LIKE '%".$needle."%')
                            OR (LOWER(email) LIKE '%".$needle."%')
                            OR (LOWER(shipping_address_1) LIKE '%".$needle."%')
                            OR (LOWER(shipping_address_2) LIKE '%".$needle."%')
                            OR (LOWER(payment_address_1) LIKE '%".$needle."%')
                            OR (LOWER(payment_address_2) LIKE '%".$needle."%')
                            OR order_id= '".(int)$needle."'
                            )
                        AND language_id = ".$language_id."
                        LIMIT ".$offset.",".$rows_count;
                $result = $this->db->query($sql);
                $result = $result->rows;
                break;
            case "customers" :
                $customer_needle = '+"'.implode('" +"', explode(' ', $needle)).'"';
                $sql = "SELECT customer_id, 
                            CONCAT('".($mode == 'listing' ? "customer: " : "")."', firstname,' ',lastname) as title,
                            CONCAT(firstname,' ',lastname,' ',email)  as text
                        FROM ".$this->db->table_name("customers")." 
                        WHERE (
                                MATCH(firstname, lastname) AGAINST ('".$customer_needle."' IN BOOLEAN MODE)
                                OR  (LOWER(email) like '%".$needle."%')
                            )
                        LIMIT ".$offset.",".$rows_count;
                $result = $this->db->query($sql);
                $result = $result->rows;
                break;
            case "pages" :
                $sql = "SELECT p.page_id,
                                b.name AS title,
                                CONCAT(b.name, ' ',b.title, ' ',b.keywords) AS text,
                                pl.layout_id, l.template_id AS tmpl_id
                        FROM ".$this->db->table_name("pages")." p 
                        LEFT JOIN ".$this->db->table_name("page_descriptions")." b 
                            ON (p.page_id = b.page_id AND b.language_id	IN (".(implode(",", $search_languages))."))
                        LEFT JOIN ".$this->db->table_name("pages_layouts")." pl
                            ON (pl.page_id = p.page_id
                                AND pl.layout_id IN (SELECT layout_id
                                                     FROM ".$this->db->table_name("layouts")." 
                                                     WHERE template_id = '"
                    .$this->config->get('config_storefront_template')."'
                                                            AND layout_type='1'))
                        LEFT JOIN ".$this->db->table_name("layouts")." l ON  l.layout_id = pl.layout_id
                        WHERE (LOWER(b.name) LIKE '%".$needle."%')
                            OR (LOWER(b.title) LIKE '%".$needle."%')
                            OR (LOWER(b.keywords) LIKE '%".$needle."%')
                        LIMIT ".$offset.",".$rows_count;

                $result = $this->db->query($sql);
                $result = $result->rows;
                break;
            case "settings" :
                $sql = "SELECT setting_id,
                                CONCAT(`group`,'-',s.`key`,'-',s.store_id) AS active,
                                s.store_id,
                                COALESCE(l.language_value,s.`key`) AS title,
                                COALESCE(l.language_value,s.`key`) AS text,
                                e.`key` AS extension, e.`type` AS type
                        FROM ".$this->db->table_name("settings")." s
                        LEFT JOIN ".$this->db->table_name("extensions")." e ON s.`group` = e.`key`
                        LEFT JOIN ".$this->db->table_name("language_definitions")." l
                                        ON l.language_key LIKE CONCAT(s.`key`,'%')
                        WHERE (LOWER(s.`value`) LIKE '%".$needle."%'
                                OR s.`key` LIKE '%".str_replace(' ', '_', $needle)."%' ";

                if ($needle != $needle2) {
                    $sql .= "	OR LOWER(s.`value`) like '%".$needle2."%'
                                OR s.`key` like '%".str_replace(' ', '_', $needle)."%' ";
                }

                $sql .= " ) AND s.`key` NOT IN ('encryption_key', 'config_ssl')
                            AND s.`store_id` ='".( int )$current_store_id."'
                        UNION
                        SELECT s.setting_id,
                                CONCAT(s.`group`,'-',s.`key`,'-',s.store_id) as active,
                                s.store_id,
                                CONCAT(`group`,' -> ',COALESCE( l.language_value,s.`key` )) as title,
                        CONCAT_WS(' >> ',l.language_value) as text, '', 'core'
                        FROM ".$this->db->table_name("language_definitions")." l
                        LEFT JOIN ".$this->db->table_name("settings")." s
                            ON l.language_key = CONCAT('entry_',REPLACE(s.`key`,'config_',''))
                        WHERE ( LOWER(l.language_value) like '%".$needle."%'
                                OR l.language_key like '%".str_replace(' ', '_', $needle)."%'
                            ";

                if ($needle != $needle2) {
                    $sql .= "   OR LOWER(l.language_value) like '%".$needle2."%'
                                OR l.language_key like '%".str_replace(' ', '_', $needle2)."%'
                            ";
                }

                $sql .= " )
                            AND block='setting_setting' AND l.language_id ='".$language_id."'
                            AND s.`store_id` ='".( int )$current_store_id."'
                            AND setting_id>0
                        LIMIT ".$offset.",".$rows_count;
                $result = $this->db->query($sql);
                $rows = $result->rows;
                $result = [];
                foreach ($rows as $row) {
                    if (!isset($result[$row['setting_id']])) {
                        //remove all text between span tags
                        $regex = '/<span(.*)span>/';
                        $row['title'] = str_replace(
                            ["	", "  ", "\n"],
                            "",
                            strip_tags(preg_replace($regex, '', $row['title']))
                        );
                        $row['text'] = !$row['text'] ? $row['title'] : $row['text'];
                        $row['text'] = str_replace(
                            ["	", "  ", "\n"],
                            "",
                            strip_tags(preg_replace($regex, '', $row['text']))
                        );
                        $result[$row['setting_id']] = $row;
                    }
                }
                $result = array_values($result);
                break;
            case "messages" :
                $sql = "SELECT DISTINCT msg_id, title AS title, `message` AS text
                        FROM ".$this->db->table_name("messages")." 
                        WHERE ( LOWER(`title`) LIKE '%".$needle."%'
                                OR LOWER(`message`) LIKE '%".$needle."%'
                                OR LOWER(`title`) LIKE '%".$needle2."%'
                                OR LOWER(`message`) LIKE '%".$needle2."%' )
                        LIMIT ".$offset.",".$rows_count;
                $result = $this->db->query($sql);
                $result = $result->rows;
                break;
            case "extensions" :
                $sql = "SELECT DISTINCT `key` AS extension, `key` AS title, `key` AS text
                        FROM ".$this->db->table_name("extensions")." e
                        WHERE ( LOWER(`key`) LIKE '%".$needle."%'
                                OR LOWER(`key`) LIKE '%".str_replace(' ', '_', $needle)."%' )
                                    AND `type` <> 'total'
                        LIMIT ".$offset.",".$rows_count;
                $result = $this->db->query($sql);
                $result = $result->rows;
                break;
            case "downloads" :
                $sql = "SELECT d.download_id, name AS title, name  AS text
                        FROM ".$this->db->table_name("downloads")." d
                        LEFT JOIN ".$this->db->table_name("download_descriptions")." dd
                            ON (d.download_id = dd.download_id 
                                AND dd.language_id IN (".(implode(",", $search_languages)).")
                                )
                        WHERE ( LOWER(dd.name) LIKE '%".$needle."%' )
                        LIMIT ".$offset.",".$rows_count;
                $result = $this->db->query($sql);
                $result = $result->rows;
                break;
            case "contents" :
                $sql = "SELECT c.content_id, name AS title, name  AS text
                        FROM ".$this->db->table_name("contents")." c
                        RIGHT JOIN ".$this->db->table_name("content_descriptions")." cd
                            ON (c.content_id = cd.content_id 
                                AND cd.language_id IN (".(implode(",", $search_languages))."))
                        WHERE 
                            (LOWER(`name`) LIKE '%".$needle."%')
                            OR (LOWER(`title`) LIKE '%".$needle."%')
                            OR (LOWER(`description`) LIKE '%".$needle."%')
                            OR (LOWER(`content`) LIKE '%".$needle."%')
                        LIMIT ".$offset.",".$rows_count;
                $result = $this->db->query($sql);
                $result = $result->rows;
                break;
            default :
                $result = [0 => ["text" => "no results! "]];
                break;
        }

        if ($mode == 'listing') {
            if ($search_category == 'commands') {
                $result = $this->prepareCommandsResponse($result);
            } else {
                $result = $this->prepareResponse($keyword,
                    $this->results_controllers[$search_category]['page'],
                    $this->results_controllers[$search_category]['id'],
                    $result);
            }
        }
        foreach ($result as &$row) {
            $row['controller'] = $this->results_controllers[$search_category]['page'];

            //shorten text for suggestion
            if ($mode != 'listing') {
                $dec_text = htmlentities($row['text'], ENT_QUOTES);
                $len = mb_strlen($dec_text);
                if ($len > 100) {
                    $ellipsis = '...';
                    $row['text'] = mb_substr($dec_text, 0, 100).$ellipsis;
                }
            }
        }
        $output ["result"] = $result;
        $output ['search_category'] = $search_category;

        return $output;
    }

    /**
     * function prepares array with search results for json encoding
     *
     * @param string $keyword
     * @param string $rt
     * @param string|array $key_field (s)
     * @param array $table
     *
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    private function prepareResponse($keyword = '', $rt = '', $key_field = '', $table = [])
    {
        $output = [];
        if (!$rt || !$key_field || !$keyword) {
            return null;
        }

        $tmp = [];

        if ($table && is_array($table)) {

            foreach ($table as $row) {
                //let's extract  and colorize keyword in row
                foreach ($row as $key => $field) {
                    $field_decoded = htmlentities($field, ENT_QUOTES);

                    // if keyword found
                    $pos = mb_stripos($field_decoded, $keyword);
                    if (is_int($pos) && $key != 'title') {
                        $ellipsis = mb_strlen(strip_tags($row['text'])) > 100 ? '...' : '';
                        $row ['title'] = mb_substr(strip_tags($row['text']), 0, 100).$ellipsis;
                        $row ['text'] = strip_tags($row['text']);
                        break;
                    }
                }

                // exception for extension settings
                $temp_key_field = $key_field;
                $url = $rt;

                if ($rt == 'setting/setting' && !empty($row['extension'])) {
                    $temp_key_field = $this->results_controllers['extensions']['id'];
                    if ($row['type'] == 'total') { //for order total extensions
                        $url = sprintf($this->results_controllers['extensions']['page2'], $row['extension']);
                    } else {
                        $url = $this->results_controllers['extensions']['page'];
                    }
                }

                if (is_array($temp_key_field)) {
                    foreach ($temp_key_field as $var) {
                        $url .= "&".$var."=".$row [$var];
                    }
                } else {
                    $url .= "&".$temp_key_field."=".$row [$temp_key_field];
                }
                $tmp ['type'] = $row['type'];
                $tmp ['href'] = $this->html->getSecureURL($url);
                $tmp ['text'] =
                    '<a href="'.$tmp ['href'].'" target="_blank" title="'.$row ['text'].'">'.$row ['title'].'</a>';
                $output [] = $tmp;
            }
        } else {
            $this->load->language('tool/global_search');
            $output [0] = ["text" => $this->language->get('no_results_message')];
        }

        return $output;
    }

    protected function prepareCommandsResponse($table = [])
    {
        $output = [];
        foreach ($table as $row) {
            $tmp = [];
            $tmp ['text'] = '<a href="'.$row['url'].'" target="_blank" title="'.$row['text'].'">'.$row['title'].'</a>';
            $output [] = $tmp;
        }

        return $output;
    }

    /**
     * function to get possible commands for the look up
     *
     * @param string $keyword
     * @param string $mode ('total')
     *
     * @return array
     */
    protected function possibleCommands($keyword, $mode = '')
    {
        /**
         * @var AdminCommands $commands_obj
         */
        $commands_obj = ABC::getObjectByAlias('AdminCommands');
        $this->commands = $commands_obj->commands;
        $result = $commands_obj->getCommands($keyword);

        if ($mode == 'total' && isset($result['found_actions'])) {
            return count((array)$result['found_actions']);
        } elseif ($mode == 'total') {
            return 0;
        }

        $ret = [];
        if ($result['found_actions']) {
            foreach ($result['found_actions'] as $command) {
                $ret[] = [
                    'text'  => $result['command']." ".$command['title']." ".$result['request'],
                    'title' => $result['command']." ".$command['title']." ".$result['request'],
                    'url'   => $command['url'],
                ];
            }
        }

        return $ret;
    }

}
