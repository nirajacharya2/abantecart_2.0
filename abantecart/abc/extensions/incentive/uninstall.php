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

use abc\core\engine\AController;
use abc\core\lib\AMenu;

//delete menu item
$menu = new AMenu ("admin");
$menu->deleteMenuItem("incentive");

$schema = $this->db->database();
if ($schema->hasTable('incentive_descriptions')) {
    $schema->drop('incentive_descriptions');
}
if ($schema->hasTable('incentive_applied')) {
    $schema->drop('incentive_applied');
}
if ($schema->hasTable('incentives')) {
    $schema->drop('incentives');
}

/**
 * @var AController $this
 */
$this->extension_manager->deleteDependant('incentive_total', $name);
$this->extension_manager->delete('incentive_total');
$this->db->table('order_data_types')->where('name', '=', 'incentive_data')->delete();