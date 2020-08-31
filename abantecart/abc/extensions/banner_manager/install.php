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

namespace abc\controllers\admin;

use abc\core\lib\AExtensionManager;
use abc\core\lib\AMenu;
use abc\core\lib\AResourceManager;
use Illuminate\Database\Schema\Blueprint;

if ( ! class_exists( 'abc\core\ABC' ) ) {
    header( 'Location: static_pages/?forbidden='.basename( __FILE__ ) );
}

/**
 * @var AExtensionManager $this
 */
$db_schema = $this->db->database();
$db_schema->create( 'banners', function ( Blueprint $table ) {
    $table->increments( 'banner_id' );
    $table->tinyInteger( 'status' )->default( 0 );
    $table->integer( 'banner_type' )->default( 1 );
    $table->string( 'banner_group_name' );
    $table->dateTime( 'start_date' );
    $table->dateTime( 'end_date' );
    $table->tinyInteger( 'blank' )->default( 0 );
    $table->text( 'target_url' );
    $table->timestamp( 'date_added' );
    $table->timestamp( 'date_modified' )->default( $this->db->CurrentTimeStamp() );
} );
$db_schema->create( 'banner_descriptions', function ( Blueprint $table ) {
    $table->integer( 'banner_id' );
    $table->integer( 'language_id' );
    $table->string( 'name' );
    $table->text( 'description' );
    $table->text( 'meta' );
    $table->timestamp( 'date_added' );
    $table->timestamp( 'date_modified' )->default( $this->db->CurrentTimeStamp() );
    $table->primary( [ 'banner_id', 'language_id' ] );
} );
$db_schema->create( 'banner_stat', function ( Blueprint $table ) {
    $table->integer( 'banner_id' );
    $table->integer( 'store_id' );
    $table->integer( 'type' );
    $table->text( 'user_info' );
    $table->text( 'meta' );
    $table->timestamp( 'time' )->default( $this->db->CurrentTimeStamp() );
    $table->index( [ 'banner_id', 'store_id', 'type', 'time' ], 'banner_stat_idx' );
    /*TODO
    ALTER TABLE `ac_banner_stat`
    ADD INDEX `ac_banner_stat_ibfk_2_idx` (`store_id` ASC);
    ALTER TABLE `ac_banner_stat`
    ADD CONSTRAINT `ac_banner_stat_ibfk_2`
      FOREIGN KEY (`store_id`)
      REFERENCES `ac_stores` (`store_id`)
      ON DELETE CASCADE
      ON UPDATE CASCADE;
     * */
} );

// add new menu item
$rm = new AResourceManager();
$rm->setType( 'image' );

$language_id = $this->language->getContentLanguageID();
$data = [];
$data['resource_code'] = '<i class="fa fa-picture-o"></i>&nbsp;';
$data['name'] = [$language_id => 'Menu Icon Banner Manager'];
$data['title'] = [$language_id => ''];
$data['description'] = [$language_id => ''];
$resource_id = $rm->addResource( $data );

$menu = new AMenu ( "admin" );
$menu->insertMenuItem( [
        "item_id"         => "banner_manager",
        "parent_id"       => "design",
        "item_text"       => "banner_manager_name",
        "item_url"        => "extension/banner_manager",
        "item_icon_rl_id" => $resource_id,
        "item_type"       => "extension",
        "sort_order"      => "6",
    ]
);
$data = [];
$data['resource_code'] = '<i class="fa fa-reply-all"></i>&nbsp;';
$data['name'] = [$language_id => 'Menu Icon Banner Manager Stat'];
$data['title'] = [$language_id => ''];
$data['description'] = [$language_id => ''];
$resource_id = $rm->addResource( $data );

$menu->insertMenuItem( [
        "item_id"         => "banner_manager_stat",
        "parent_id"       => "reports",
        "item_text"       => "banner_manager_name_stat",
        "item_url"        => "extension/banner_manager_stat",
        "item_icon_rl_id" => $resource_id,
        "item_type"       => "extension",
        "sort_order"      => "4",
    ]
);
$exists = $this->db->table('blocks')->select('block_id')->where('block_txt_id', '=', 'banner_block')->get()->count();
if ( ! $exists ) {
    $now = $this->db->getORM()::raw('NOW()');
    $block_id = $this->db->table('block_templates')->insertGetId(
        [ "block_txt_id" => 'banner_block', "controller" => 'blocks/banner_block', "date_added" => $now]
    );


    $this->db->getORM()::table('block_templates')->insert([
        [ "block_id" => $block_id, "parent_block_id" => 1, "template" =>  'blocks/banner_block_header.tpl', "date_added" => $now ],
        [ "block_id" => $block_id, "parent_block_id" => 2, "template" =>  'blocks/banner_block_content.tpl', "date_added" => $now],
        [ "block_id" => $block_id, "parent_block_id" => 3, "template" =>  'blocks/banner_block.tpl', "date_added" => $now],
        [ "block_id" => $block_id, "parent_block_id" => 4, "template" =>  'blocks/banner_block_content.tpl', "date_added" => $now],
        [ "block_id" => $block_id, "parent_block_id" => 5, "template" =>  'blocks/banner_block_content.tpl', "date_added" => $now],
        [ "block_id" => $block_id, "parent_block_id" => 6, "template" =>  'blocks/banner_block.tpl', "date_added" => $now],
        [ "block_id" => $block_id, "parent_block_id" => 7, "template" =>  'blocks/banner_block_content.tpl', "date_added" => $now],
        [ "block_id" => $block_id, "parent_block_id" => 8, "template" =>  'blocks/banner_block_header.tpl', "date_added" => $now],
    ]);

    $this->cache->flush( 'layout' );
}