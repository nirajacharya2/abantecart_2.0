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

class ModelCatalogManufacturer extends Model
{
    /**
     * @param array $data
     *
     * @return int
     * @throws \Exception
     */
    public function addManufacturer($data)
    {
        $seo_keys = [];
        $language_id = (int)$this->language->getContentLanguageID();
        $this->db->query(
            "INSERT INTO ".$this->db->table_name("manufacturers")." 
            SET name = '".$this->db->escape($data['name'])."', 
                sort_order = '".(int)$data['sort_order']."'"
        );
        $manufacturer_id = $this->db->getLastId();
        if (isset($data['manufacturer_store'])) {
            foreach ($data['manufacturer_store'] as $store_id) {
                $this->db->query(
                    "INSERT INTO ".$this->db->table_name("manufacturers_to_stores")." 
                    SET manufacturer_id = '".(int)$manufacturer_id."', 
                        store_id = '".(int)$store_id."'"
                );
            }
        }

        if ($data['keyword'] && !is_array($data['keyword'])) {
            $seo_keys = [
                $language_id => [
                    'keyword' => H::SEOEncode($data['keyword'], 'manufacturer_id', $manufacturer_id),
                ],
            ];
        } else {
            if (is_array($data['keyword'])) {
                $all_languages = $this->language->getAvailableLanguages();
                $all_ids = [];
                foreach ($all_languages as $l) {
                    $all_ids[] = $l['language_id'];
                }
                foreach ($data['keyword'] as $lang_id => $seo_key) {
                    if (!in_array($lang_id, $all_ids)) {
                        continue;
                    }
                    $seo_keys[(int)$lang_id] = [
                        'keyword' => H::SEOEncode($seo_key, 'manufacturer_id', $manufacturer_id),
                    ];
                }
            } else {
                $seo_keys = [
                    $language_id => [
                        'keyword' => H::SEOEncode($data['name'], 'manufacturer_id', $manufacturer_id),
                    ],
                ];
            }
        }

        if ($seo_keys) {
            foreach ($seo_keys as $lang_id => $seo_key) {
                $this->language->replaceDescriptions('url_aliases',
                    [
                        'query'       => "manufacturer_id=".(int)$manufacturer_id,
                        'language_id' => $lang_id,
                    ],
                    [$lang_id => $seo_key]
                );
            }
        } else {
            $this->db->query(
                "DELETE
                FROM ".$this->db->table_name("url_aliases")."
                WHERE query = 'manufacturer_id=".(int)$manufacturer_id."'
                    AND language_id = '".(int)$language_id."'");
        }

        $this->cache->flush('manufacturer');

        return $manufacturer_id;
    }

    /**
     * @param int $manufacturer_id
     * @param array $data
     *
     * @throws \Exception
     */
    public function editManufacturer($manufacturer_id, $data)
    {

        $fields = ['name', 'sort_order'];
        $update = [];
        foreach ($fields as $f) {
            if (isset($data[$f])) {
                $update[] = $f." = '".$this->db->escape($data[$f])."'";
            }
        }
        if (!empty($update)) {
            $this->db->query("UPDATE ".$this->db->table_name("manufacturers")." SET ".implode(',',
                    $update)." WHERE manufacturer_id = '".(int)$manufacturer_id."'");
        }

        if (isset($data['manufacturer_store'])) {
            $this->db->query("DELETE FROM ".$this->db->table_name("manufacturers_to_stores")
                ." WHERE manufacturer_id = '".(int)$manufacturer_id."'");
            foreach ($data['manufacturer_store'] as $store_id) {
                $this->db->query("INSERT INTO ".$this->db->table_name("manufacturers_to_stores")
                    ." SET manufacturer_id = '".(int)$manufacturer_id."', store_id = '".(int)$store_id."'");
            }
        }

        if (isset($data['keyword'])) {
            $data['keyword'] = H::SEOEncode($data['keyword'], 'manufacturer_id', $manufacturer_id);
            if ($data['keyword']) {
                //NOTE: Even though we only support 1 languages for manufacture,
                // we still need to save SEO keyword for every language.
                $this->language->replaceDescriptions(
                    'url_aliases',
                    ['query' => "manufacturer_id=".(int)$manufacturer_id],
                    [
                        (int)$this->session->data['content_language_id'] => [
                            'keyword' => $data['keyword'],
                        ],
                    ]);
            } else {
                $this->db->query(
                    "DELETE
                    FROM ".$this->db->table_name("url_aliases")." 
                    WHERE query = 'manufacturer_id=".(int)$manufacturer_id."'
                        AND language_id = '".(int)$this->session->data['content_language_id']."'"
                );
            }
        }

        $this->cache->flush('manufacturer');
    }

    /**
     * @param int $manufacturer_id
     *
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteManufacturer($manufacturer_id)
    {
        $this->db->query(
            "DELETE FROM ".$this->db->table_name("manufacturers")." 
            WHERE manufacturer_id = '".(int)$manufacturer_id."'"
        );
        $this->db->query(
            "DELETE FROM ".$this->db->table_name("manufacturers_to_stores")." 
            WHERE manufacturer_id = '".(int)$manufacturer_id."'"
        );
        $this->db->query(
            "DELETE FROM ".$this->db->table_name("url_aliases")." 
            WHERE query = 'manufacturer_id=".(int)$manufacturer_id."'"
        );

        $lm = new ALayoutManager();
        $lm->deletePageLayout('pages/product/manufacturer', 'manufacturer_id', (int)$manufacturer_id);

        //delete resources
        $rm = new AResourceManager();
        $resources = $rm->getResourcesList([
            'object_name' => 'manufacturers',
            'object_id'   => (int)$manufacturer_id,
        ]);
        foreach ($resources as $r) {
            $rm->unmapResource('manufacturers', $manufacturer_id, $r['resource_id']);
            //if resource became orphan - delete it
            if (!$rm->isMapped($r['resource_id'])) {
                $rm->deleteResource($r['resource_id']);
            }
        }
        $this->cache->flush('manufacturer');
    }

    /**
     * @param int $manufacturer_id
     *
     * @return array
     * @throws \Exception
     */
    public function getManufacturer($manufacturer_id)
    {
        $query = $this->db->query(
            "SELECT DISTINCT *, 
                ( SELECT keyword
                FROM ".$this->db->table_name("url_aliases")." 
                WHERE query = 'manufacturer_id=".(int)$manufacturer_id."'
                  AND language_id='".(int)$this->language->getContentLanguageID()."') AS keyword
            FROM ".$this->db->table_name("manufacturers")." 
            WHERE manufacturer_id = '".(int)$manufacturer_id."'"
        );

        return $query->row;
    }

    /**
     * @param array $data
     * @param string $mode
     *
     * @return array|int
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getManufacturers($data = [], $mode = 'default')
    {
        if ($data) {

            if ($data['store_id']) {
                $store_id = (int)$data['store_id'];
            } else {
                $store_id = (int)$this->config->get('config_store_id');
            }

            if ($mode == 'total_only') {
                $total_sql = 'count(DISTINCT m.manufacturer_id) as total';
            } else {
                $total_sql = 'ms.store_id, m.*';
            }
            $sql = "SELECT DISTINCT ".$total_sql." 
                    FROM ".$this->db->table_name("manufacturers")." m
                    INNER JOIN ".$this->db->table_name('manufacturers_to_stores')." ms
                    ON (m.manufacturer_id = ms.manufacturer_id 
                        AND ms.store_id = '".$store_id."')";

            if (!empty($data['subsql_filter'])) {
                $sql .= " WHERE ".$data['subsql_filter'];
            }

            //If for total, we done building the query
            if ($mode == 'total_only') {
                $query = $this->db->query($sql);
                return $query->row['total'];
            }

            $sort_data = [
                'name'       => 'm.name',
                'sort_order' => 'm.sort_order',
            ];

            if (isset($data['sort']) && in_array($data['sort'], array_keys($sort_data))) {
                $sql .= " ORDER BY ".$data['sort'];
            } else {
                $sql .= " ORDER BY m.name ";
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
        } else {
            // this slice of code is duplicate of storefront model for manufacturer
            $cache_key = 'manufacturer.store_'.(int)$this->config->get('config_store_id');
            $manufacturer_data = $this->cache->get($cache_key);
            if ($manufacturer_data === null) {
                $query = $this->db->query(
                    "SELECT DISTINCT m.*, m2s.store_id
                    FROM ".$this->db->table_name("manufacturers")." m
                    LEFT JOIN ".$this->db->table_name("manufacturers_to_stores")." m2s
                        ON (m.manufacturer_id = m2s.manufacturer_id)
                    WHERE m2s.store_id = '".(int)$this->config->get('config_store_id')."'
                    ORDER BY sort_order, LCASE(m.name) ASC"
                );
                $manufacturer_data = $query->rows;
                $this->cache->put($cache_key, $manufacturer_data);
            }
            return $manufacturer_data;
        }
    }















    /**
     * @param int $manufacturer_id
     *
     * @return array
     * @throws \Exception
     */
    public function getManufacturerStores($manufacturer_id)
    {
        $manufacturer_store_data = [];
        $rows = $this->getManufacturerStoresInfo($manufacturer_id);
        foreach ($rows as $result) {
            $manufacturer_store_data[] = $result['store_id'];
        }
        return $manufacturer_store_data;
    }

    /**
     * @param int $manufacturer_id
     *
     * @return array
     * @throws \Exception
     */
    public function getManufacturerStoresInfo($manufacturer_id)
    {
        $query = $this->db->query(
            "SELECT m2s.*,
                s.name as store_name,
                ss.`value` as store_url,
                sss.`value` as store_ssl_url
            FROM ".$this->db->table_name("manufacturers_to_stores")." m2s
            LEFT JOIN ".$this->db->table_name("stores")." s 
                ON s.store_id = m2s.store_id
            LEFT JOIN ".$this->db->table_name("settings")." ss
                ON (ss.store_id = m2s.store_id AND ss.`key`='config_url')
            LEFT JOIN ".$this->db->table_name("settings")." sss
                ON (sss.store_id = m2s.store_id AND sss.`key`='config_ssl_url')
            WHERE m2s.manufacturer_id = '".(int)$manufacturer_id."'"
        );
        return $query->rows;
    }

    /**
     * @param array $data
     *
     * @return mixed|null
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getTotalManufacturers($data = [])
    {
        return $this->getManufacturers($data, 'total_only');
    }
}
