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

use abc\core\lib\AExtensionManager;
use abc\core\lib\AMenu;

/**
 * @var AExtensionManager $this
 */

//delete menu item
$menu = new AMenu ( "admin" );
$menu->deleteMenuItem( "banner_manager" );
$menu->deleteMenuItem( "banner_manager_stat" );

$db_schema = $this->db->database();
$tables = [ 'banner_stat', 'banner_descriptions', 'banners' ];
foreach ( $tables as $table_name ) {
    if ( $db_schema->hasTable( $table_name ) ) {
        $db_schema->drop( $table_name );
    }
}