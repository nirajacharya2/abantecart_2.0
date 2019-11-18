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

namespace abc\core\lib;

use abc\core\ABC;
use abc\core\engine\Registry;

class AListingManager extends AListing
{
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var int
     */
    public $errors = 0;
    /**
     * @var int
     */
    protected $custom_block_id;
    /**
     * @var array
     */
    public $data_sources;

    //NOTE: This class is loaded in INIT for admin only

    /**
     * @param int $custom_block_id
     *
     * @throws AException
     */
    public function __construct($custom_block_id)
    {
        parent::__construct($custom_block_id);
        if (!ABC::env('IS_ADMIN')) { // forbid for non admin calls
            throw new AException (
                'Error: permission denied to access class AListingManager',
                AC_ERR_LOAD
            );
        }
    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws \Exception
     */
    public function saveCustomListItem($data)
    {
        $custom_block_id = (int)$this->custom_block_id;
        if(!$custom_block_id){
            return false;
        }
        $data['store_id'] = (int)$data['store_id'];

        if (!isset($data['data_type']) && isset($data['listing_datasource'])) {
            $listing_properties = $this->getListingDataSources();
            $data['data_type'] = $listing_properties[$data['listing_datasource']]['data_type'];
        }

        $result = $this->db->query(
            "SELECT *
            FROM  ".$this->db->table_name("custom_lists")." 
            WHERE custom_block_id = '".$custom_block_id."'
                    AND id='".(int)$data['id']."'
                    AND data_type='".$this->db->escape($data['data_type'])."'
                    AND store_id='".(int)$data['store_id']."'"
        );

        if ($result->num_rows && $custom_block_id) {
            $this->db->query(
                "UPDATE ".$this->db->table_name("custom_lists")."
                SET custom_block_id = '".$custom_block_id."'
                ".(!is_null($data['sort_order']) ? ", sort_order = '".(int)$data['sort_order']."'" : "")."
                WHERE custom_block_id = '".$custom_block_id."'
                      AND id='".$data['id']."'
                        AND data_type='".$this->db->escape($data['data_type'])."'
                        AND store_id='".(int)$data['store_id']."'"
            );
        } else {
            $this->db->query(
                "INSERT INTO ".$this->db->table_name("custom_lists")." 
                ( custom_block_id,
                  data_type,
                  id,
                  sort_order,
                  store_id,
                  date_added )
                VALUES ('".$custom_block_id."',
                      '".$data['data_type']."',
                      '".(int)$data['id']."',
                      '".( int )$data ['sort_order']."',
                      '".( int )$data ['store_id']."',
                      NOW())"
            );
        }

        $this->cache->flush('blocks.custom.'.$custom_block_id.$data ['store_id']);
        return true;
    }

    // delete one item from custom list of custom listing block

    /**
     * @param array $data
     *
     * @throws \Exception
     */
    public function deleteCustomListItem($data)
    {

        $listing_properties = $this->getListingDataSources();
        if (!isset($data['data_type']) && isset($data['listing_datasource'])) {
            $data['data_type'] = $listing_properties[$data['listing_datasource']]['data_type'];
        }
        $custom_block_id = (int)$this->custom_block_id;

        $sql = "DELETE FROM  ".$this->db->table_name("custom_lists")." 
                                    WHERE custom_block_id = '".$custom_block_id."'
                                            AND id='".$data['id']."'
                                            AND data_type='".$data['data_type']."'";
        $this->db->query($sql);
        $this->cache->flush('blocks.custom.'.$custom_block_id);
    }

    // delete all custom list of custom listing block

    /**
     * @param $store_id
     *
     * @throws \Exception
     */
    public function deleteCustomListing($store_id)
    {
        $store_id = (int)$store_id;
        $custom_block_id = (int)$this->custom_block_id;
        $sql = "DELETE FROM  ".$this->db->table_name("custom_lists")."
                WHERE custom_block_id = '".$custom_block_id."'
                    AND store_id = '".$store_id."'";
        $this->db->query($sql);
        $this->cache->flush('blocks.custom.'.$custom_block_id.$store_id);
    }
}