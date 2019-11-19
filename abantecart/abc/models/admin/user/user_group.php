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

namespace abc\models\admin;

use abc\core\ABC;
use abc\core\engine\Model;


/**
 * Class ModelUserUserGroup
 *
 * @package abc\models\admin
 */
class ModelUserUserGroup extends Model
{
    /**
     * @param array $data
     *
     * @return int
     * @throws \Exception
     */
    public function addUserGroup( $data )
    {

        if ( ! isset( $data['permission'] ) ) {
            $controllers = $this->getAllControllers();
            $types = ['access', 'modify'];
            foreach ( $types as $type ) {
                foreach ( $controllers as $controller ) {
                    $data['permission'][$type][$controller] = 0;
                }
            }
        }
        $this->db->query(
            "INSERT INTO ".$this->db->table_name( "user_groups" )." 
             SET `name` = '".$this->db->escape( $data['name'] )."',
                 `permission` = '".( isset( $data['permission'] ) ? serialize( $data['permission'] ) : '' )."'" );

        return $this->db->getLastId();
    }

    /**
     * @param int $user_group_id
     * @param array $data
     *
     * @throws \Exception
     */
    public function editUserGroup( $user_group_id, $data )
    {
        $user_group_id = ! $user_group_id ? $this->addUserGroup( $data['name'] ) : $user_group_id;
        $user_group = $this->getUserGroup( $user_group_id );

        $update = [];
        if ( isset( $data['name'] ) ) {
            $update[] = "name = '".$this->db->escape( $data['name'] )."'";
        }

        if ( isset( $data['permission'] ) ) {
            $p = $user_group['permission'];
            if ( isset( $data['permission']['access'] ) ) {
                foreach ( $data['permission']['access'] as $controller => $value ) {
                    $value = !in_array($value, [null, 0, 1]) ? 0 : $value;
                    $p['access'][$controller] = $value;
                    if ( ! isset( $p['modify'][$controller] ) && ! isset( $data['permission']['modify'][$controller] ) ) {
                        $p['modify'][$controller] = 0;
                    }
                }
            }
            if ( isset( $data['permission']['modify'] ) ) {
                foreach ( $data['permission']['modify'] as $controller => $value ) {
                    $value = !in_array($value, [null, 0, 1]) ? 0 : $value;
                    $p['modify'][$controller] = $value;
                    if ( ! isset( $p['access'][$controller] ) && ! isset( $data['permission']['access'][$controller] ) ) {
                        $p['access'][$controller] = 0;
                    }
                }
            }
            $update[] = "permission = '".serialize( $p )."'";
        }

        if ( ! empty( $update ) ) {
            $this->db->query(
                "UPDATE ".$this->db->table_name( "user_groups" )." 
                SET ".implode( ',', $update )." 
                WHERE user_group_id = '".(int)$user_group_id."'"
            );
        }
    }

    /**
     * @param int $user_group_id
     *
     * @throws \Exception
     */
    public function deleteUserGroup( $user_group_id )
    {
        $this->db->query(
            "DELETE FROM ".$this->db->table_name( "user_groups" )." 
             WHERE user_group_id = '".(int)$user_group_id."'"
        );
    }

    /**
     * @param int $user_id
     * @param string $type
     * @param string $page
     *
     * @throws \Exception
     */
    public function addPermission( $user_id, $type, $page )
    {
        $user_query = $this->db->query(
             "SELECT DISTINCT user_group_id 
              FROM ".$this->db->table_name( "users" )." 
              WHERE user_id = '".(int)$user_id."'"
        );

        if ( $user_query->num_rows ) {
            $user_group_query = $this->db->query(
                    "SELECT DISTINCT * 
                      FROM ".$this->db->table_name( "user_groups" )." 
                      WHERE user_group_id = '".(int)$user_query->row['user_group_id']."'" );

            if ( $user_group_query->num_rows ) {
                $data = unserialize( $user_group_query->row['permission'] );
                $data[$type][$page] = 1;
                $this->db->query(
                    "UPDATE ".$this->db->table_name( "user_groups" )." 
                    SET permission = '".serialize( $data )."' 
                    WHERE user_group_id = '".(int)$user_query->row['user_group_id']."'" );
            }
        }
    }

    /**
     * @param $user_group_id
     *
     * @return array
     * @throws \Exception
     */
    public function getUserGroup( $user_group_id )
    {
        $query = $this->db->query(
             "SELECT DISTINCT * 
              FROM ".$this->db->table_name( "user_groups" )." 
              WHERE user_group_id = '".(int)$user_group_id."'"
        );

        $user_group = [
            'name'       => $query->row['name'],
            'permission' => unserialize( $query->row['permission'] ),
        ];

        return $user_group;
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws \Exception
     */
    public function getUserGroups( $data = [])
    {
        $sql = "SELECT *
                FROM ".$this->db->table_name( "user_groups" )." 
                ORDER BY name";

        if ( isset( $data['order'] ) && ( $data['order'] == 'DESC' ) ) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if ( isset( $data['start'] ) || isset( $data['limit'] ) ) {
            if ( $data['start'] < 0 ) {
                $data['start'] = 0;
            }

            if ( $data['limit'] < 1 ) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT ".(int)$data['start'].",".(int)$data['limit'];
        }

        $query = $this->db->query( $sql );
        return $query->rows;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getTotalUserGroups()
    {
        $sql = "SELECT COUNT(*) AS total 
                FROM ".$this->db->table_name( "user_groups" )." ";
        $query = $this->db->query( $sql );
        return $query->row['total'];
    }

    /**
     * method returns array with all controllers of admin section
     *
     * @param string $order
     *
     * @return array
     */
    public function getAllControllers( $order = 'asc')
    {

        $ignore = [
            'index/home',
            'common/layout',
            'common/login',
            'common/logout',
            'error/not_found',
            'error/permission',
            'common/footer',
            'common/header',
            'common/menu',
        ];

        $controllers_list = [];
        $files_pages = glob( ABC::env( 'DIR_APP' ).'controllers/admin/pages/*/*.php' );
        $files_response = glob( ABC::env( 'DIR_APP' ).'controllers/admin/responses/*/*.php' );
        $files = array_merge( $files_pages, $files_response );

        // looking for controllers inside core
        foreach ( $files as $file ) {
            $data = explode( '/', dirname( $file ) );
            $controller = end( $data ).'/'.basename( $file, '.php' );
            if ( ! in_array( $controller, $ignore ) ) {
                $controllers_list[] = $controller;
            }
        }
        // looking for controllers inside extensions
        $files_pages = glob( ABC::env( 'DIR_APP_EXTENSIONS' ).'/*/controllers/admin/pages/*/*.php' );
        $files_response = glob( ABC::env( 'DIR_APP_EXTENSIONS' ).'/*/controllers/admin/responses/*/*.php' );
        $files = array_merge( $files_pages, $files_response );
        foreach ( $files as $file ) {
            $data = explode( '/', dirname( $file ) );
            $controller = end( $data ).'/'.basename( $file, '.php' );
            if ( ! in_array( $controller, $ignore ) ) {
                $controllers_list[] = $controller;
            }
        }

        $controllers_list = array_unique( $controllers_list );
        sort( $controllers_list, SORT_STRING );
        if ( $order == 'desc' ) {
            $controllers_list = array_reverse( $controllers_list );
        }

        return $controllers_list;
    }
}
