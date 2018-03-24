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
use Illuminate\Support\Facades\DB;

if (!class_exists('abc\core\ABC')) {
	header('Location: static_pages/?forbidden='.basename(__FILE__));
}

/**
 * @var AExtensionManager $this
 */
$db_schema = $this->db->getSchema();

$db_schema->create('banners', function (Blueprint $table) {
        $table->increments('banner_id');
        $table->tinyInteger('status')->default(0);
        $table->integer('banner_type')->default(1);
        $table->string('banner_group_name');
        $table->dateTime('start_date');
        $table->dateTime('end_date');
        $table->tinyInteger('blank')->default(0);
        $table->text('target_url');
        $table->timestamp('date_added');
        $table->timestamp('date_modified')->default($this->db->CurrentTimeStamp());
});
$db_schema->create('banner_descriptions', function (Blueprint $table) {
        $table->integer('banner_id');
        $table->integer('language_id');
        $table->string('name');
        $table->text('description');
        $table->text('meta');
        $table->timestamp('date_added');
        $table->timestamp('date_modified')->default($this->db->CurrentTimeStamp());
        $table->primary(['banner_id','language_id']);
});
$db_schema->create('banner_stat', function (Blueprint $table) {
        $table->integer('banner_id');
        $table->integer('store_id');
        $table->integer('type');
        $table->text('user_info');
        $table->text('meta');
        $table->timestamp('time')->default($this->db->CurrentTimeStamp());
        $table->index(['banner_id', 'store_id', 'type', 'time'],'banner_stat_idx');
});




// add new menu item
$rm = new AResourceManager();
$rm->setType('image');

$language_id = $this->language->getContentLanguageID();
$data = array();
$data['resource_code'] = '<i class="fa fa-picture-o"></i>&nbsp;';
$data['name'] = array($language_id => 'Menu Icon Banner Manager');
$data['title'] = array($language_id => '');
$data['description'] = array($language_id => '');
$resource_id = $rm->addResource($data);

$menu = new AMenu ( "admin" );
$menu->insertMenuItem ( array (  "item_id" => "banner_manager",
								 "parent_id"=>"design",
								 "item_text" => "banner_manager_name",
								 "item_url" => "extension/banner_manager",
								 "item_icon_rl_id" => $resource_id,
								 "item_type"=>"extension",
								 "sort_order"=>"6")
								);
$data = array();
$data['resource_code'] = '<i class="fa fa-reply-all"></i>&nbsp;';
$data['name'] = array($language_id => 'Menu Icon Banner Manager Stat');
$data['title'] = array($language_id => '');
$data['description'] = array($language_id => '');
$resource_id = $rm->addResource($data);

$menu->insertMenuItem ( array (  "item_id" => "banner_manager_stat",
								 "parent_id"=>"reports",
								 "item_text" => "banner_manager_name_stat",
								 "item_url" => "extension/banner_manager_stat",
								 "item_icon_rl_id" => $resource_id,
								 "item_type"=>"extension",
								 "sort_order"=>"4")
								);

$sql = "SELECT block_id FROM ".$this->db->table('blocks')." WHERE block_txt_id='banner_block'";
$result = $this->db->query($sql);
if(!$result->num_rows){
	$this->db->query("INSERT INTO ".$this->db->table('blocks')." (`block_txt_id`, `controller`, `date_added`)
					  VALUES ('banner_block', 'blocks/banner_block', NOW() );");
	$block_id = $this->db->getLastId();

	$sql = "INSERT INTO ".$this->db->table('block_templates')." (`block_id`, `parent_block_id`, `template`, `date_added`)
			VALUES
		(".$block_id.", 1, 'blocks/banner_block_header.tpl', NOW() ),
		(".$block_id.", 2, 'blocks/banner_block_content.tpl', NOW() ),
		(".$block_id.", 3, 'blocks/banner_block.tpl', NOW() ),
		(".$block_id.", 4, 'blocks/banner_block_content.tpl', NOW() ),
		(".$block_id.", 5, 'blocks/banner_block_content.tpl', NOW() ),
		(".$block_id.", 6, 'blocks/banner_block.tpl', NOW() ),
		(".$block_id.", 7, 'blocks/banner_block_content.tpl', NOW() ),
		(".$block_id.", 8, 'blocks/banner_block_header.tpl', NOW() )";
	$this->db->query($sql);
	$this->cache->remove('layout');
}