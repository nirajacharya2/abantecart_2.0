<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 17.09.2018
 * Time: 17:17
 */

namespace abc\models\admin;

use abc\core\engine\Model;
use abc\core\helper\AHelperUtils;

if (!class_exists('abc\core\ABC') || !\abc\core\ABC::env('IS_ADMIN')) {
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