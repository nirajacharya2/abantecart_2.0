<?php

namespace abc\models\admin;

use abc\core\ABC;
use abc\core\engine\ALanguage;
use abc\core\engine\Model;
use abc\core\lib\AEncryption;
use H;

class ModelUserUser extends Model
{
    public function addUser($data)
    {
        $salt_key = H::genToken(8);
        /**
         * @var AEncryption $enc
         */
        $enc = ABC::getObjectByAlias('AEncryption');
        $this->db->query("INSERT INTO ".$this->db->table_name("users")." 
						  SET username = '".$this->db->escape($data['username'])."',
						      firstname = '".$this->db->escape($data['firstname'])."',
						      lastname = '".$this->db->escape($data['lastname'])."',
						      email = '".$this->db->escape($data['email'])."',
						      user_group_id = '".(int)$data['user_group_id']."',
						      status = '".(int)$data['status']."',
							  salt = '".$this->db->escape($salt_key)."', 
						      password = '".$enc::getHash($data['password'], $salt_key, 'admin')."',
						      date_added = NOW()");
        return $this->db->getLastId();
    }

    public function editUser($user_id, $data)
    {
        $fields = ['username', 'firstname', 'lastname', 'email', 'user_group_id', 'status'];
        $update = [];
        foreach ($fields as $f) {
            if (isset($data[$f])) {
                $update[] = $f." = '".$this->db->escape($data[$f])."'";
            }
        }

        if ($data['password'] || $data['email'] || $data['username']) {
            //notify admin user of important information change
            $language = new ALanguage($this->registry, '', 1);
            $language->load('common/im');
            $message_arr = [
                1 => ['message' => $language->get('im_account_update_text_to_admin')],
            ];

            $this->im->sendToUser($user_id, 'account_update', $message_arr);
        }

        if ($data['password']) {
            $salt_key = H::genToken(8);
            /**
             * @var AEncryption $enc
             */
            $enc = ABC::getObjectByAlias('AEncryption');
            $update[] = "salt = '".$this->db->escape($salt_key)."'";
            $update[] = "password = '".$enc::getHash($data['password'], $salt_key, 'admin')."'";
        }

        if (!empty($update)) {
            $sql = "UPDATE ".$this->db->table_name("users")." SET ".implode(',', $update)." WHERE user_id = '"
                .(int)$user_id."'";
            $this->db->query($sql);
        }
    }

    public function deleteUser($user_id)
    {
        $this->db->query("DELETE FROM ".$this->db->table_name("users")." WHERE user_id = '".(int)$user_id."'");
    }

    public function getUser($user_id)
    {
        $query =
            $this->db->query("SELECT * FROM ".$this->db->table_name("users")." WHERE user_id = '".(int)$user_id."'");

        return $query->row;
    }

    public function getUsers($data = [], $mode = 'default')
    {
        if ($mode == 'total_only') {
            $sql = "SELECT count(*) as total FROM ".$this->db->table_name("users")." ";
        } else {
            $sql = "SELECT * FROM ".$this->db->table_name("users")." ";
        }
        if (!empty($data['subsql_filter'])) {
            $sql .= " WHERE ".$data['subsql_filter'];
        }

        //If for total, we done building the query
        if ($mode == 'total_only') {
            $query = $this->db->query($sql);
            return $query->row['total'];
        }

        $sort_data = [
            'username',
            'user_group_id',
            'status',
            'date_added',
        ];

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY ".$data['sort'];
        } else {
            $sql .= " ORDER BY username";
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
    }

    public function getTotalUsers($data = [])
    {
        return $this->getUsers($data, 'total_only');
    }

    public function getTotalUsersByGroupId($user_group_id)
    {
        $query =
            $this->db->query(
                "SELECT COUNT(*) AS total 
                FROM ".$this->db->table_name("users")." 
                WHERE user_group_id = '".(int)$user_group_id."'"
            );

        return $query->row['total'];
    }
}
