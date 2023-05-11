<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2023 Belavier Commerce LLC

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
use abc\models\layout\CustomList;

class AListingManager extends AListing
{

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
     * @param $data
     * @return CustomList|false
     */
    public function saveCustomListItem($data)
    {

        if (!$this->custom_block_id) {
            return false;
        }
        $data['store_id'] = (int)$data['store_id'];

        if (!isset($data['data_type']) && isset($data['listing_datasource'])) {
            $listingSourcesInfo = $this->getListingDataSources();
            $data['data_type'] = $listingSourcesInfo[$data['listing_datasource']]['data_type'];
        }

        $result = CustomList::updateOrCreate(
            [
                'id'              => (int)$data['id'],
                'custom_block_id' => $this->custom_block_id,
                'data_type'       => $data['data_type'],
                'store_id'        => $data['store_id']
            ],
            [
                'sort_order' => $data['sort_order']
            ]
        );

        Registry::cache()->flush('layout');
        return $result;
    }

    // delete one item from custom list of custom listing block

    /**
     * @param array $data
     * @return void
     */
    public function deleteCustomListItem(array $data)
    {

        $listingSourcesInfo = $this->getListingDataSources();
        if (!isset($data['data_type']) && isset($data['listing_datasource'])) {
            $data['data_type'] = $listingSourcesInfo[$data['listing_datasource']]['data_type'];
        }

        CustomList::where(
            [
                'custom_block_id' => $this->custom_block_id,
                'id'              => $data['id'],
                'data_type'       => $data['data_type']
            ]
        )?->delete();

        Registry::cache()->flush('layout');
    }

    // delete all custom list of custom listing block

    /**
     * @param int $store_id
     * @return void
     */
    public function deleteCustomListing(int $store_id)
    {
        CustomList::where(
            [
                'custom_block_id' => $this->custom_block_id,
                'store_id'        => $store_id
            ]
        )?->delete();

        Registry::cache()->flush('layout');
    }
}