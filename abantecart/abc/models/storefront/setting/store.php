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
namespace abc\models\storefront;
use abc\core\engine\Model;

if (!class_exists('abc\core\ABC')) {
	header('Location: static_pages/?forbidden='.basename(__FILE__));
}
class ModelSettingStore extends Model {
	public function getStore($store_id) {
		$query = $this->db->query("SELECT DISTINCT *, s.store_id
									FROM " . $this->db->table_name("stores") . " s
									LEFT JOIN " . $this->db->table_name("store_descriptions") . " sd
										ON (s.store_id = sd.store_id
										       AND sd.language_id = '" . $this->config->get('storefront_language_id') . "')
									WHERE s.store_id = '" . (int)$store_id . "'");
		return $query->row;
	}
}
