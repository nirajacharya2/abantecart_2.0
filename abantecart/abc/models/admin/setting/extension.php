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

if (!class_exists('abc\core\ABC') || !\abc\core\ABC::env('IS_ADMIN')) {
	header('Location: static_pages/?forbidden='.basename(__FILE__));
}
class ModelSettingExtension extends Model {
	/*
	* Get enabled payment extensions. Used in configuration for shipping extensions
	*/
	public function getEnabledPayments() {
		$query = $this->db->query("SELECT *
								   FROM " . $this->db->table_name("extensions") . "
								   WHERE `type` = 'payment' and status = 1");
		return $query->rows;
	}

	/*
	* Get enabled payment extensions that support handler class. New arch. 
	*/
	public function getPaymentsWithHandler() {
		$query = $this->db->query("SELECT *
								   FROM " . $this->db->table_name("extensions") . "
								   WHERE `type` = 'payment' and status = 1");
		$output = array();
		$output[] = array('' => '');
		foreach($query->rows as $row){
			if(file_exists(ABC::env('DIR_APP_EXTENSIONS').$row['key'].ABC::env('DIRNAME_CORE').'lib/handler.php')){
				$output[] = $row;
			}
		}
		return $output;
	}
	
	
	public function install($type, $key) {
		$this->db->query("INSERT INTO " . $this->db->table_name("extensions") . "
							SET
								`type` = '" . $this->db->escape($type) . "',
								`key` = '" . $this->db->escape($key) . "'");
	}
	
	public function uninstall($type, $key) {
		$this->db->query("DELETE FROM " . $this->db->table_name("extensions") . "
						WHERE `type` = '" . $this->db->escape($type) . "'
								AND `key` = '" . $this->db->escape($key) . "'");
	}
}
