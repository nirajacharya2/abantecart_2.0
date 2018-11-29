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

use abc\core\engine\Model;
use abc\core\lib\ALayoutManager;
use abc\core\lib\AResourceManager;
use H;

/**
 * Class ModelCatalogCategory
 */
class ModelCatalogCategory extends Model
{
    /**
     * @param $data
     *
     * @return int
     * @throws \Exception
     */
    public function addCategory($data)
    {
        $parent_id = $data['parent_id'] ? "'".(int)$data['parent_id']."'" : "NULL";
        $this->db->query("INSERT INTO ".$this->db->table_name("categories")." 
                          SET parent_id = ".$parent_id.",
                              sort_order = '".(int)$data['sort_order']."',
                              STATUS = '".(int)$data['status']."',
                              date_modified = NOW(),
                              date_added = NOW()");

        $category_id = $this->db->getLastId();

        foreach ($data['category_description'] as $language_id => $value) {
            $this->language->replaceDescriptions('category_descriptions',
                ['category_id' => (int)$category_id],
                [
                    $language_id => [
                        'name'             => $value['name'],
                        'meta_keywords'    => $value['meta_keywords'],
                        'meta_description' => $value['meta_description'],
                        'description'      => $value['description'],
                    ],
                ]);
        }

        if (isset($data['category_store'])) {
            foreach ($data['category_store'] as $store_id) {
                $this->db->query(
                    "INSERT INTO ".$this->db->table_name("categories_to_stores")." 
                    SET category_id = '".(int)$category_id."', 
                        store_id = '".(int)$store_id."'");
            }
        }

        if ($data['keyword']) {
            $seo_key = H::SEOEncode($data['keyword'], 'category_id', $category_id);
        } else {
            //Default behavior to save SEO URL keyword from category name in default language
            $seo_key = H::SEOEncode($data['category_description'][$this->language->getDefaultLanguageID()]['name'],
                'category_id',
                $category_id);
        }
        if ($seo_key) {
            $this->language->replaceDescriptions('url_aliases',
                ['query' => "category_id=".(int)$category_id],
                [(int)$this->language->getContentLanguageID() => ['keyword' => $seo_key]]);
        } else {
            $this->db->query("DELETE
                               FROM ".$this->db->table_name("url_aliases")." 
                               WHERE `query` = 'category_id=".(int)$category_id."'
                                   AND language_id = '".(int)$this->language->getContentLanguageID()."'");
        }

        $this->cache->remove('category');

        return $category_id;
    }

    /**
     * @param int $category_id
     * @param array $data
     *
     * @throws \Exception
     */
    public function editCategory($category_id, $data)
    {
        $content_language_id = $this->language->getContentLanguageID();
        $fields = ['parent_id', 'sort_order', 'status'];
        $update = ['date_modified = NOW()'];
        foreach ($fields as $f) {
            if (isset($data[$f])) {
                $update[] = $f." = '".$this->db->escape($data[$f])."'";
            }
        }
        if (!empty($update)) {
            $sql = "UPDATE ".$this->db->table_name("categories")." 
                   SET ".implode(', ', $update)." 
                   WHERE category_id = '".(int)$category_id."'";
            $this->db->query($sql);
        }

        if (!empty($data['category_description'])) {
            foreach ($data['category_description'] as $language_id => $value) {
                $update = [];
                if (isset($value['name'])) {
                    $update["name"] = $value['name'];
                }
                if (isset($value['description'])) {
                    $update["description"] = $value['description'];
                }
                if (isset($value['meta_keywords'])) {
                    $update["meta_keywords"] = $value['meta_keywords'];
                }
                if (isset($value['meta_description'])) {
                    $update["meta_description"] = $value['meta_description'];
                }
                if (!empty($update)) {
                    // insert or update
                    $this->language->replaceDescriptions('category_descriptions',
                        ['category_id' => (int)$category_id],
                        [$language_id => $update]);
                }
            }
        }

        if (isset($data['category_store'])) {
            $this->db->query("DELETE FROM ".$this->db->table_name("categories_to_stores")." 
                                WHERE category_id = '".(int)$category_id."'");
            foreach ($data['category_store'] as $store_id) {
                $this->db->query("INSERT INTO ".$this->db->table_name("categories_to_stores")." 
                                   SET `category_id` = '".(int)$category_id."', 
                                       `store_id` = '".(int)$store_id."'");
            }
        }

        if (isset($data['keyword'])) {
            $data['keyword'] = H::SEOEncode($data['keyword']);
            if ($data['keyword']) {
                $this->language->replaceDescriptions('url_aliases',
                    ['query' => "category_id=".(int)$category_id],
                    [(int)$content_language_id => ['keyword' => $data['keyword']]]
                );
            } else {
                $this->db->query(
                    "DELETE
                    FROM ".$this->db->table_name("url_aliases")." 
                    WHERE query = 'category_id=".(int)$category_id."'
                        AND language_id = '".(int)$content_language_id."'"
                );
            }
        }

        $this->cache->remove('category');
        $this->cache->remove('product');

    }

    /**
     * @param int $category_id
     *
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public function deleteCategory($category_id)
    {
        $this->db->query("DELETE FROM ".$this->db->table_name("categories")." 
                            WHERE category_id = '".(int)$category_id."'");
        $this->db->query("DELETE FROM ".$this->db->table_name("category_descriptions")." 
                            WHERE category_id = '".(int)$category_id."'");
        $this->db->query("DELETE FROM ".$this->db->table_name("categories_to_stores")." 
                            WHERE category_id = '".(int)$category_id."'");
        $this->db->query("DELETE FROM ".$this->db->table_name("url_aliases")." 
                            WHERE query = 'category_id=".(int)$category_id."'");
        $this->db->query("DELETE FROM ".$this->db->table_name("products_to_categories")." 
                            WHERE category_id = '".(int)$category_id."'");

        //delete resources
        $rm = new AResourceManager();
        $resources = $rm->getResourcesList(['object_name' => 'categories', 'object_id' => (int)$category_id]);
        foreach ($resources as $r) {
            $rm->unmapResource('categories', $category_id, $r['resource_id']);
            //if resource became orphan - delete it
            if (!$rm->isMapped($r['resource_id'])) {
                $rm->deleteResource($r['resource_id']);
            }
        }
        //remove layout
        $lm = new ALayoutManager();
        $lm->deletePageLayout('pages/product/category', 'path', $category_id);

        //delete children categories
        $query = $this->db->query("SELECT category_id
                                    FROM ".$this->db->table_name("categories")."
                                    WHERE parent_id = '".(int)$category_id."'");

        foreach ($query->rows as $result) {
            $this->deleteCategory($result['category_id']);
        }

        $this->cache->remove('category');
        $this->cache->remove('product');
    }

    /**
     * @param int $category_id
     *
     * @return array
     * @throws \Exception
     */
    public function getCategory($category_id)
    {
        $query = $this->db->query(
            "SELECT DISTINCT *,
                (SELECT keyword
                FROM ".$this->db->table_name("url_aliases")." 
                WHERE query = 'category_id=".(int)$category_id."'
                    AND language_id='".(int)$this->language->getContentLanguageID()."' ) as keyword
            FROM ".$this->db->table_name("categories")." 
            WHERE category_id = '".(int)$category_id."'"
        );

        return $query->row;
    }

    /**
     * @param int $parent_id
     * @param int $store_id
     *
     * @return array
     * @throws \Exception
     */
    public function getCategories($parent_id, $store_id = null)
    {
        $language_id = $this->language->getContentLanguageID();
        $cache_key = 'category.'.$parent_id.'.store_'.$store_id.'_lang_'.$language_id;
        $category_data = $this->cache->pull($cache_key);

        if ($category_data === false) {
            $category_data = [];
            $sql = "SELECT *
                    FROM ".$this->db->table_name("categories")." c
                    LEFT JOIN ".$this->db->table_name("category_descriptions")." cd
                    ON (c.category_id = cd.category_id) ";
            if (!is_null($store_id)) {
                $sql .= "RIGHT JOIN ".$this->db->table_name("categories_to_stores")." cs 
                            ON (c.category_id = cs.category_id AND cs.store_id = '".(int)$store_id."')";
            }

            $sql .= "WHERE COALESCE(c.parent_id,0) = '".(int)$parent_id."'
                        AND cd.language_id = '".(int)$language_id."'
                    ORDER BY c.sort_order, cd.name ASC";
            $query = $this->db->query($sql);

            foreach ($query->rows as $result) {
                $category_data[] = [
                    'category_id' => $result['category_id'],
                    'parent_id'   => $result['parent_id'],
                    'name'        => $this->getPath($result['category_id'], $language_id),
                    'status'      => $result['status'],
                    'sort_order'  => $result['sort_order'],
                ];
                $category_data = array_merge($category_data, $this->getCategories($result['category_id'], $store_id));
            }
            $this->cache->push($cache_key, $category_data);
        }

        return $category_data;
    }

    /**
     * @param array $data
     *
     * @return array|int
     * @throws \Exception
     */
    public function getCategoriesData($data)
    {

        if ($data['language_id']) {
            $language_id = (int)$data['language_id'];
        } else {
            $language_id = (int)$this->language->getContentLanguageID();
        }

        if ($data['store_id']) {
            $store_id = (int)$data['store_id'];
        } else {
            $store_id = (int)$this->config->get('config_store_id');
        }

        $where = (isset($data['parent_id']) ? "WHERE COALESCE(c.parent_id,0) = '".(int)$data['parent_id']."'" : '');
        $sql = "SELECT ".$this->db->raw_sql_row_count()." *,
                      c.category_id,
                      (SELECT count(*) as cnt
                       FROM ".$this->db->table_name('products_to_categories')." p
                       WHERE p.category_id = c.category_id) as products_count,
                      (SELECT count(*) as cnt
                       FROM ".$this->db->table_name('categories')." cc
                       WHERE cc.parent_id = c.category_id) as subcategory_count,
                       cd.name as basename
                FROM ".$this->db->table_name('categories')." c
                LEFT JOIN ".$this->db->table_name('category_descriptions')." cd
                    ON (c.category_id = cd.category_id AND cd.language_id = '".$language_id."')
                INNER JOIN ".$this->db->table_name('categories_to_stores')." cs
                    ON (c.category_id = cs.category_id AND cs.store_id = '".$store_id."')
                ".$where;

        if (!empty($data['subsql_filter'])) {
            $sql .= ($where ? " AND " : 'WHERE ').$data['subsql_filter'];
        }

        $sort_data = [
            'name'       => 'cd.name',
            'status'     => 'c.status',
            'sort_order' => 'c.sort_order',
        ];

        if (isset($data['sort']) && in_array($data['sort'], array_keys($sort_data))) {
            $sql .= " ORDER BY ".$data['sort'];
        } else {
            $sql .= " ORDER BY c.sort_order, cd.name ";
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
        $category_data = [];
        foreach ($query->rows as $result) {
            $result['total_num_rows'] = $query->total_num_rows;
            if ($data['basename'] == true) {
                $result['name'] = $result['basename'];
            } else {
                $result['name'] = $this->getPath($result['category_id'], $language_id);
            }
            $category_data[] = $result;
        }
        return $category_data;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getParents()
    {
        $query = $this->db->query(
            "SELECT DISTINCT c.parent_id, cd.name
             FROM ".$this->db->table_name("categories")." c
             LEFT JOIN ".$this->db->table_name("categories")." c1 
                ON (c.parent_id = c1.category_id)
             LEFT JOIN ".$this->db->table_name("category_descriptions")." cd 
                ON (c1.category_id = cd.category_id)
             WHERE cd.language_id = '".(int)$this->language->getContentLanguageID()."'
             ORDER BY c.sort_order, cd.name ASC");
        $result = [];
        foreach ($query->rows as $r) {
            $result[$r['parent_id']] = $r['name'];
        }

        return $result;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getLeafCategories()
    {
        $query = $this->db->query(
            "SELECT t1.category_id AS category_id 
              FROM ".$this->db->table_name("categories")." AS t1 
              LEFT JOIN ".$this->db->table_name("categories")." AS t2
                    ON t1.category_id = t2.parent_id 
              WHERE t2.category_id IS NULL"
        );
        $result = [];
        foreach ($query->rows as $r) {
            $result[$r['category_id']] = $r['category_id'];
        }

        return $result;
    }

    /**
     * @param int $category_id
     * @param int $language_id
     *
     * @param string $mode can be empty or id
     *
     * @return string
     * @throws \Exception
     */
    public function getPath($category_id, $language_id = 0, $mode = '')
    {
        $category_id = (int)$category_id;
        $language_id = (int)$language_id;
        if (!$language_id) {
            $language_id = (int)$this->language->getContentLanguageID();
        }
        $query = $this->db->query("SELECT c.category_id, `name`, `parent_id`
                                    FROM ".$this->db->table_name("categories")." c
                                    LEFT JOIN ".$this->db->table_name("category_descriptions")." cd
                                        ON (c.category_id = cd.category_id)
                                    WHERE c.category_id = '".(int)$category_id."' 
                                        AND cd.language_id = '".$language_id."'
                                    ORDER BY c.sort_order, cd.name ASC");
        $category_info = $query->row;
        if ($category_info['parent_id']) {
            if ($mode == 'id') {
                return $this->getPath($category_info['parent_id'], $language_id, $mode).'_'
                    .$category_info['category_id'];
            } else {
                return $this->getPath($category_info['parent_id'], $language_id, $mode)
                    .$this->language->get('text_separator')
                    .$category_info['name'];
            }
        } else {
            return $mode == 'id' ? $category_info['category_id'] : $category_info['name'];
        }
    }

    /**
     * @param int $category_id
     *
     * @return array
     * @throws \Exception
     */
    public function getCategoryDescriptions($category_id)
    {
        $category_description_data = [];
        $query = $this->db->query("SELECT * 
                                    FROM ".$this->db->table_name("category_descriptions")." 
                                    WHERE category_id = '".(int)$category_id."'");

        foreach ($query->rows as $result) {
            $category_description_data[$result['language_id']] = [
                'name'             => $result['name'],
                'meta_keywords'    => $result['meta_keywords'],
                'meta_description' => $result['meta_description'],
                'description'      => $result['description'],
            ];
        }

        return $category_description_data;
    }

    /**
     * @param int $category_id
     *
     * @return array
     * @throws \Exception
     */
    public function getCategoryStores($category_id)
    {
        $category_store_data = [];
        $rows = $this->getCategoryStoresInfo($category_id);
        foreach ($rows as $result) {
            $category_store_data[] = $result['store_id'];
        }

        return $category_store_data;
    }

    /**
     * @param int $category_id
     *
     * @return array
     * @throws \Exception
     */
    public function getCategoryStoresInfo($category_id)
    {
        $query = $this->db->query(
            "SELECT c2s.*,
                    s.name AS store_name,
                    ss.`value` AS store_url,
                    sss.`value` AS store_ssl_url
            FROM ".$this->db->table_name("categories_to_stores")." c2s
            LEFT JOIN ".$this->db->table_name("stores")." s 
                ON s.store_id = c2s.store_id
            LEFT JOIN ".$this->db->table_name("settings")." ss
                ON (ss.store_id = c2s.store_id AND ss.`key`='config_url')
            LEFT JOIN ".$this->db->table_name("settings")." sss
                ON (sss.store_id = c2s.store_id AND sss.`key`='config_ssl_url')
            WHERE category_id = '".(int)$category_id."'"
        );
        return $query->rows;
    }

    /**
     * @param $category_id
     * @param int $language_id
     *
     * @return mixed
     * @throws \Exception
     */
    public function getCategoryInfo($category_id, $language_id = 0)
    {
        $category_id = (int)$category_id;
        $language_id = (int)$language_id;
        if (!$language_id) {
            $language_id = (int)$this->language->getContentLanguageID();
        }
        $result = $this->db->query(
            "SELECT cd.*, c.*,
                (SELECT keyword
                FROM ".$this->db->table_name("url_aliases")." 
                WHERE query = 'category_id=".$category_id."'
                    AND language_id='".$language_id."' ) as keyword
            FROM ".$this->db->table_name("categories")." c
            LEFT JOIN ".$this->db->table_name("category_descriptions")." cd
                ON (c.category_id = cd.category_id)
            WHERE c.category_id = '".$category_id."' AND cd.language_id = '".$language_id."'
            ORDER BY c.sort_order, cd.name ASC"
        );

        return $result->row;
    }
}
