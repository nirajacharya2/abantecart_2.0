<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2018 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\lib\AMenu;
use abc\core\lib\AResourceManager;
use Illuminate\Database\Schema\Blueprint;

if (!defined('DIR_CORE')) {
    header('Location: static_pages/');
}
/**
 * @var AController $this
 */

// add new menu item
/**
 * @var AResourceManager $rm
 */
$rm = ABC::getObjectByAlias('AResourceManager');
$rm->setType('image');

$language_id = $this->language->getContentLanguageID();
$data = [];
$data['resource_code'] = '<i class="fa fa-eraser"></i>&nbsp;';
$data['name'] = [$language_id => 'Menu Icon GDPR'];
$data['title'] = [$language_id => ''];
$data['description'] = [$language_id => ''];
$resource_id = $rm->addResource($data);

$menu = new AMenu ("admin");
$menu->insertMenuItem([
        "item_id"         => "gdpr",
        "parent_id"       => "logs",
        "item_text"       => "gdpr_name",
        "item_url"        => "extension/gdpr_history",
        "item_icon_rl_id" => $resource_id,
        "item_type"       => "extension",
        "sort_order"      => "5",
    ]
);

$db_schema = $this->db->database();
if($db_schema->hasTable('gdpr_history')){
    $db_schema->drop('gdpr_history');
}

$db_schema->create( 'gdpr_history', function ( Blueprint $table ) {
    $table->increments( 'id' );
    $table->integer( 'customer_id' )->nullable(false);
    $table->string( 'request_type' )->comment('v - viewed, r - requested erasure, d - downloaded, e - erased');
    $table->text( 'name' );
    $table->string( 'email' );
    $table->text( 'user_agent' );
    $table->text( 'accept_language' );
    $table->string( 'ip' );
    $table->string( 'server_ip' );
    $table->timestamp( 'date_modified' )->default( $this->db->CurrentTimeStamp() );
} );

