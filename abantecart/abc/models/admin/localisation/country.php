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
/**
 * Class ModelLocalisationCountry
 */
class ModelLocalisationCountry extends Model
{
    /**
     * @param array $data
     *
     * @return int
     * @throws \Exception
     */
    public function addCountry($data)
    {
        $this->db->query(
            "INSERT INTO ".$this->db->table_name("countries")." 
            SET status = '".(int)$data['status']."', 
                iso_code_2 = '".$this->db->escape($data['iso_code_2'])."', 
                iso_code_3 = '".$this->db->escape($data['iso_code_3'])."', 
                address_format = '".$this->db->escape($data['address_format'])."'"
        );

        $country_id = $this->db->getLastId();

        foreach ($data['country_name'] as $language_id => $value) {
            $this->language->replaceDescriptions('country_descriptions',
                ['country_id' => (int)$country_id],
                [
                    $language_id => [
                        'name' => $value['name'],
                    ],
                ]);
        }

        $this->cache->flush('localization');
        return $country_id;
    }

    /**
     * @param int $country_id
     * @param $data
     *
     * @throws \Exception
     */
    public function editCountry($country_id, $data)
    {

        $fields = ['status', 'iso_code_2', 'iso_code_3', 'address_format',];
        $update = [];
        foreach ($fields as $f) {
            if (isset($data[$f])) {
                $update[] = $f." = '".$this->db->escape($data[$f])."'";
            }
        }
        if (!empty($update)) {
            $this->db->query(
                "UPDATE ".$this->db->table_name("countries")." 
                SET ".implode(',', $update)." 
                WHERE country_id = '".(int)$country_id."'"
            );
            $this->cache->flush('localization');
        }

        if ($data['country_name']) {
            foreach ($data['country_name'] as $language_id => $value) {
                $this->language->replaceDescriptions('country_descriptions',
                    ['country_id' => (int)$country_id],
                    [
                        $language_id => [
                            'name' => $value['name'],
                        ],
                    ]);
            }
        }
    }

    /**
     * @param int $country_id
     *
     * @throws \Exception
     */
    public function deleteCountry($country_id)
    {
        $this->db->query(
            "DELETE FROM ".$this->db->table_name("countries")." 
            WHERE country_id = '".(int)$country_id."'"
        );
        $this->db->query(
            "DELETE FROM ".$this->db->table_name("country_descriptions")." 
            WHERE country_id = '".(int)$country_id."'"
        );
        $this->cache->flush('localization');
    }

    /**
     * @param int $country_id
     *
     * @return array
     * @throws \Exception
     */
    public function getCountry($country_id)
    {
        $language_id = $this->language->getContentLanguageID();

        $query = $this->db->query(
            "SELECT DISTINCT *
            FROM ".$this->db->table_name("countries")." c
            LEFT JOIN ".$this->db->table_name("country_descriptions")." cd
            ON (c.country_id = cd.country_id AND cd.language_id = '".(int)$language_id."')
            WHERE c.country_id = '".(int)$country_id."'"
        );
        $ret_data = $query->row;
        $ret_data['country_name'] = $this->getCountryDescriptions($country_id);
        return $ret_data;
    }

    /**
     * @param int $country_id
     *
     * @return array
     * @throws \Exception
     */
    public function getCountryDescriptions($country_id)
    {
        $country_data = [];

        $query = $this->db->query(
            "SELECT *
            FROM ".$this->db->table_name("country_descriptions")." 
            WHERE country_id = '".(int)$country_id."'"
        );

        foreach ($query->rows as $result) {
            $country_data[$result['language_id']] = ['name' => $result['name']];
        }

        return $country_data;
    }

    /**
     * @param array $data
     * @param string $mode
     *
     * @return array|int
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getCountries($data = [], $mode = 'default')
    {
        $language_id = $this->language->getContentLanguageID();
        $default_language_id = $this->language->getDefaultLanguageID();

        if ($data) {
            if ($mode == 'total_only') {
                $sql = "SELECT count(*) as total FROM ".$this->db->table_name("countries")." c ";
            } else {
                $sql = "SELECT c.country_id, 
							   c.iso_code_2,
							   c.iso_code_3, 
							   c.address_format, 
							   c.status, 
							   c.sort_order, 
							   cd.name  
						FROM ".$this->db->table_name("countries")." c ";
            }
            $sql .= "LEFT JOIN ".$this->db->table_name("country_descriptions")
                ." cd ON (c.country_id = cd.country_id AND cd.language_id = '".(int)$language_id."') ";

            if (!empty($data['subsql_filter'])) {
                $sql .= " WHERE ".$data['subsql_filter'];
            }

            //If for total, we done building the query
            if ($mode == 'total_only') {
                $query = $this->db->query($sql);
                return $query->row['total'];
            }

            $sort_data = [
                'name'       => 'cd.name',
                'status'     => 'c.status',
                'iso_code_2' => 'c.iso_code_2',
                'iso_code_3' => 'c.iso_code_3',
            ];

            if (isset($data['sort']) && in_array($data['sort'], array_keys($sort_data))) {
                $sql .= " ORDER BY ".$sort_data[$data['sort']];
            } else {
                $sql .= " ORDER BY cd.name";
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
            $cache_key = 'localization.country.lang_'.$language_id;
            $country_data = $this->cache->get($cache_key);

            if ($country_data === null) {
                if ($language_id == $default_language_id) {
                    $query = $this->db->query(
                        "SELECT *
                        FROM ".$this->db->table_name("countries")." c
                        LEFT JOIN ".$this->db->table_name("country_descriptions")." cd 
                            ON (c.country_id = cd.country_id AND cd.language_id = '".(int)$language_id."') 
                        ORDER BY cd.name ASC"
                    );

                } else {
                    //merge text for missing country translations.
                    $query = $this->db->query(
                        "SELECT *, COALESCE( cd1.name,cd2.name) as name
                        FROM ".$this->db->table_name("countries")." c
                        LEFT JOIN ".$this->db->table_name("country_descriptions")." cd1
                            ON (c.country_id = cd1.country_id AND cd1.language_id = '".(int)$language_id."')
                        LEFT JOIN ".$this->db->table_name("country_descriptions")." cd2
                            ON (c.country_id = cd2.country_id AND cd2.language_id = '".(int)$default_language_id."')
                        ORDER BY cd1.name,cd2.name ASC");
                }

                $country_data = $query->rows;

                $this->cache->put($cache_key, $country_data);
            }

            return $country_data;
        }
    }

    /**
     * @param array $data
     *
     * @return int
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getTotalCountries($data = [])
    {
        return $this->getCountries($data, 'total_only');
    }
}
