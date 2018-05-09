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
use abc\core\helper\AHelperUtils;

if (!class_exists('abc\core\ABC')) {
    header('Location: static_pages/?forbidden='.basename(__FILE__));
}

/**
 * Class AUser
 */
final class AUser
{
    /**
     * @var int
     */
    private $user_id;
    private $user_group_id;

    /**
     * @var string
     */
    private $email;
    private $username;
    private $firstname;
    private $lastname;
    private $last_login;
    /**
     * @var \abc\core\lib\ARequest
     */
    private $request;
    /**
     * @var \abc\core\lib\ASession
     */
    private $session;

    /**
     * @var \abc\core\lib\ADB
     */
    private $db;

    /**
     * @var array
     */
    private $permission = array();

    /**
     * @param $registry \abc\core\engine\Registry
     */
    public function __construct($registry)
    {
        $this->db = $registry->get('db');
        $this->request = $registry->get('request');
        $this->session = $registry->get('session');

        if (isset($this->session->data['user_id'])) {
            $user_query = $this->db->query("SELECT * 
                                            FROM ".$this->db->table_name("users")." 
                                            WHERE user_id = '".(int)$this->session->data['user_id']."'");
            if ($user_query->num_rows) {
                $this->user_id = (int)$user_query->row['user_id'];
                $this->user_group_id = (int)$user_query->row['user_group_id'];
                $this->email = $user_query->row['email'];
                $this->username = $user_query->row['username'];
                $this->firstname = $user_query->row['firstname'];
                $this->lastname = $user_query->row['lastname'];
                $this->last_login = $this->session->data['user_last_login'];
                $this->_user_init();
            } else {
                $this->logout();
            }
        } else {
            unset($this->session->data['token']);
        }
    }

    /**
     * @param $username string
     * @param $password string
     *
     * @return bool
     */
    public function login($username, $password)
    {

        $sql = "SELECT *, SHA1(CONCAT(salt, SHA1(CONCAT(salt, SHA1('".$this->db->escape($password)."')))))
                FROM ".$this->db->table_name("users")."
                WHERE username = '".$this->db->escape($username)."'
                AND password = SHA1(CONCAT(salt, SHA1(CONCAT(salt, SHA1('".$this->db->escape($password)."')))))
                AND status = 1";

        $user_query = $this->db->query($sql);

        if ($user_query->num_rows) {
            $this->user_id = $this->session->data['user_id'] = (int)$user_query->row['user_id'];
            $this->user_group_id = (int)$user_query->row['user_group_id'];
            $this->username = $user_query->row['username'];

            $this->last_login = $this->session->data['user_last_login'] = $user_query->row['last_login'];
            if (!$this->last_login || $this->last_login == 'null' || $this->last_login == '0000-00-00 00:00:00') {
                $this->session->data['user_last_login'] = $this->last_login = '';
            }

            $this->_user_init();
            $this->_update_last_login();

            return true;
        } else {
            return false;
        }
    }

    /**
     * Init user
     *
     * @param void
     *
     * @return void
     */
    private function _user_init()
    {
        $this->db->query("UPDATE ".$this->db->table_name("users")." 
                            SET ip = '".$this->db->escape($this->request->getRemoteIP())."'
                            WHERE user_id = '".$this->user_id."';");

        $user_group_query = $this->db->query("SELECT `permission`
                                              FROM ".$this->db->table_name("user_groups")."
                                              WHERE `user_group_id` = '".$this->user_group_id."'");
        if (unserialize($user_group_query->row['permission'])) {
            foreach (unserialize($user_group_query->row['permission']) as $key => $value) {
                $this->permission[$key] = $value;
            }
        }
    }

    private function _update_last_login()
    {
        $this->db->query("UPDATE ".$this->db->table_name("users")." 
                        SET last_login = NOW()
                        WHERE user_id = '".$this->user_id."';");
    }

    public function logout()
    {
        unset($this->session->data['user_id']);
        $this->user_id = '';
        $this->username = '';
    }

    /**
     * @param $key   - route to controller
     * @param $value bool
     *
     * @return bool
     */
    public function hasPermission($key, $value)
    {
        //If top_admin allow all permission. Make sure Top Admin Group is set to ID 1
        if ($this->user_group_id == 1) {
            return true;
        } else {
            if (isset($this->permission[$key])) {
                return $this->permission[$key][$value] == 1 ? true : false;
            } else {
                return false;
            }
        }
    }

    /**
     * @param string $value - route to controller
     *
     * @return bool
     */
    public function canAccess($value)
    {
        return $this->hasPermission('access', $value);
    }

    /**
     * @param string $value route to controller
     *
     * @return bool
     */
    public function canModify($value)
    {
        return $this->hasPermission('modify', $value);
    }

    /**
     * @param string $token
     *
     * @return bool|int
     */
    public function isLoggedWithToken($token)
    {
        if ((isset($this->session->data['token']) && !isset($token))
            || ((isset($token) && (isset($this->session->data['token']) && ($token != $this->session->data['token']))))
        ) {
            return false;
        } else {
            return $this->user_id;
        }
    }

    /**
     * @return bool|int
     */
    public function isLogged()
    {
        if (ABC::env('IS_ADMIN') && $this->request->get['token'] != $this->session->data['token']) {
            return false;
        }

        return $this->user_id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->user_id;
    }

    /**
     * @return int
     */
    public function getUserGroupId()
    {
        return $this->user_group_id;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getLastLogin()
    {
        return $this->last_login;
    }

    /**
     * @param string $username
     * @param string $email
     *
     * @return bool
     */
    public function validate($username, $email)
    {
        $user_query = $this->db->query(
            "SELECT * 
                FROM ".$this->db->table_name("users")."
                WHERE  username = '".$this->db->escape($username)."'
                        AND email = '".$this->db->escape($email)."'");
        if ($user_query->num_rows) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $length
     *
     * @return string
     */
    static function generatePassword($length = 8)
    {
        $chars = "1234567890abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $i = 0;
        $password = "";
        while ($i <= $length) {
            $password .= $chars{mt_rand(0, strlen($chars))};
            $i++;
        }

        return $password;
    }

    /**
     * @return string
     */
    public function getAvatar()
    {
        return AHelperUtils::getGravatar($this->email);
    }

    /**
     * @return string
     */
    public function getUserFirstName()
    {
        return $this->firstname;
    }

    /**
     * @return string
     */
    public function getUserLastName()
    {
        return $this->lastname;
    }
}
