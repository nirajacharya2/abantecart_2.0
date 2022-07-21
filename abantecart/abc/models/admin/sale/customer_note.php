<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2022 Belavier Commerce LLC

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

if (!class_exists('abc\core\ABC') || !ABC::env('IS_ADMIN')) {
    header('Location: static_pages/?forbidden='.basename(__FILE__));
}
/**
* Class ModelSaleCustomerNote
*/

class ModelSaleCustomerNote extends Model
{
    private $table_name = "customer_notes";

    public function addNote(array $data): bool
    {
      if (empty($data['note'])) return false;
      if (empty($data['user_id'])) return false;
      if (empty($data['customer_id'])) return false;
      return  $this->db->table($this->table_name)->insert(
          [
              'customer_id' => $data['customer_id'],
              'user_id' => $data['user_id'],
              'note' => $data['note'],
          ]
        );
    }

    public function getNotes(int $customer_id): array
    {
        $collection = $this->db
            ->table($this->table_name)
            ->select([$this->table_name.'.note', $this->table_name.'.date_added as note_added', $this->table_name.'.customer_id',
                'users.lastname', 'users.firstname', 'users.username'])
            ->leftJoin('users', function($join) {
                $join->on('customer_notes.user_id', '=', 'users.user_id');
            })
            ->get()
            ->where('customer_id', '=', $customer_id)
            ->sortBy('date_added');
        return $collection->all();
    }

}