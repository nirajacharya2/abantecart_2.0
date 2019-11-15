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

namespace abc\models\storefront;

use abc\core\engine\Model;

class ModelCatalogContent extends Model
{
    /**
     * @param $content_id
     *
     * @return array
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getContent($content_id)
    {
        $content_id = (int)$content_id;
        $store_id = (int)$this->config->get('config_store_id');
        $language_id = (int)$this->config->get('storefront_language_id');
        $cache_key = 'content.'.$content_id.'.store_'.$store_id.'_lang_'.$language_id;
        $cache = $this->cache->get($cache_key);

        if ($cache !== null) {
            return $cache;
        }

        $cache = [];
        $sql = "SELECT DISTINCT i.content_id, i.hide_title, id.*
                FROM ".$this->db->table_name("contents")." i
                LEFT JOIN ".$this->db->table_name("content_descriptions")." id
                    ON (i.content_id = id.content_id AND id.language_id = '".$language_id."')
                LEFT JOIN ".$this->db->table_name("contents_to_stores")." i2s
                    ON (i.content_id = i2s.content_id)
                WHERE i.content_id = '".$content_id."' AND COALESCE(i2s.store_id,0) = '".$store_id
            ."' AND i.status = '1'";
        $query = $this->db->query($sql);

        if ($query->num_rows) {
            $cache = $query->row;
        }
        $this->cache->put($cache_key, $cache);

        return $cache;
    }

    /**
     * @return array
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getContents()
    {
        $store_id = (int)$this->config->get('config_store_id');
        $language_id = (int)$this->config->get('storefront_language_id');
        $cache_key = 'content.all.store_'.$store_id.'_lang_'.$language_id;
        $output = $this->cache->get($cache_key);
        if ($output === null) {
            $output = [];
            $sql = "SELECT i.*, id.*
                    FROM ".$this->db->table_name("contents")." i
                    LEFT JOIN ".$this->db->table_name("content_descriptions")." id
                            ON (i.content_id = id.content_id
                                    AND id.language_id = '".(int)$this->config->get('storefront_language_id')."')";

            $sql .= "LEFT JOIN ".$this->db->table_name("contents_to_stores")." i2s ON (i.content_id = i2s.content_id)";
            $sql .= "WHERE i.status = '1' ";
            $sql .= " AND COALESCE(i2s.store_id,0) = '".(int)$this->config->get('config_store_id')."'";

            $sql .= "ORDER BY COALESCE(i.parent_content_id,0), i.sort_order, LCASE(id.title) ASC";
            $query = $this->db->query($sql);

            if ($query->num_rows) {
                $output = $query->rows;
            }
            $this->cache->put($cache_key, $output);
        }
        return $output;
    }
}
