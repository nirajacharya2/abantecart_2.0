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

/**
 * @var AController $this
 */

use abc\core\engine\AController;
use abc\core\lib\AMenu;
use abc\core\lib\AResourceManager;
use Illuminate\Database\Schema\Blueprint;

$schema = $this->db->database();
if (!$schema->hasTable('incentives')) {
    $schema->create('incentives', function (Blueprint $table) {

        $table->integer('incentive_id')->autoIncrement();
        $table->longText('conditions')->nullable(false);
        $table->longText('bonuses')->nullable(false);
        $table->timestamp('start_date')->nullable(false)->useCurrent();
        $table->timestamp('end_date')->nullable();
        $table->mediumInteger('priority')->default(0)->nullable(false);
        $table->char('stop', 1)->nullable(false);
        // $table->enum('incentive_type', ['products', 'global'])->nullable(false)->default('global');
        $table->text('conditions_hash')->nullable(false);
        $table->char('status', 1)->default('A')->nullable(false);
        $table->integer('resource_id')->nullable();
        $table->mediumInteger('limit_of_usages')->nullable(false);
        $table->text('users_conditions_hash')->nullable(false);
        $table->timestamp('date_added')->nullable(true)->useCurrent();
        $table->timestamp('date_modified')->nullable(true)->useCurrent();

        $table->collation = 'utf8_general_ci';
        $table->engine = 'InnoDB';
        $table->charset = 'utf8';
    });
}

if (!$schema->hasTable('incentive_descriptions')) {
    $schema->create('incentive_descriptions', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('incentive_id')->nullable(false);
        $table->integer('language_id')->nullable(false);
        $table->string('name')->nullable(false)->default('n/a');
        $table->text('description_short')->nullable(false);
        $table->mediumText('description')->nullable(false);
        $table->timestamp('date_added')->nullable(true)->useCurrent();
        $table->timestamp('date_modified')->nullable(true)->useCurrent();
        $table->primary(['incentive_id', 'language_id']);

//        alter table tims_incentive_descriptions
//    add constraint tims_incentive_descriptions_fk
//        foreign key (incentive_id) references tims_incentives (incentive_id)
//            on update cascade on delete cascade;

        $table->collation = 'utf8_general_ci';
        $table->engine = 'InnoDB';
        $table->charset = 'utf8';
    });
}
if (!$schema->hasTable('incentive_applied')) {
    $schema->create('incentive_applied', function (Blueprint $table) {
        $table->integer('id')->autoIncrement();
        $table->integer('incentive_id')->nullable(false);
        $table->integer('customer_id')->nullable(false);
        $table->smallInteger('result_code')->default(0)->nullable(false)->comment('0 - success, 1- fail');
        $table->longText('result');
        $table->decimal('bonus_amount', 15, 4)->default(0);
        $table->timestamp('date_added')->nullable(true)->useCurrent();

        $table->index(['incentive_id', 'customer_id', 'date_added']);

        $table->collation = 'utf8_general_ci';
        $table->engine = 'InnoDB';
        $table->charset = 'utf8';
    });
}

//order data type
$table = $this->db->table('order_data_types');
if (!$table->select('*')->where('name', '=', 'incentive_data')->count()) {
    $languages = $this->language->getAvailableLanguages();
    foreach ($languages as $l) {
        $table->insert([
            [
                'language_id' => $l['language_id'],
                'name'        => 'incentive_data',
                'date_added'  => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}

$child_extension_id = $this->extension_manager->add(
    [
        'type'     => 'total',
        'key'      => 'incentive_total',
        'status'   => 1,
        'priority' => 10,
        'version'  => '1.0',
    ]
);
// edit settings
$this->load->model('setting/setting');
//insert incentive_total before total
$sort = $this->config->get('total_sort_order');
$calc = $this->config->get('total_calculation_order');

$this->model_setting_setting->editSetting('total',
    [
        'total_sort_order'        => ($sort + 1),
        'total_calculation_order' => ($calc + 1),
    ]
);

$this->model_setting_setting->editSetting('incentive_total',
    [
        'incentive_total_status'            => 1,
        'incentive_total_sort_order'        => $sort,
        'incentive_total_calculation_order' => $calc,
        'incentive_total_total_type'        => 'incentive',
    ]
);

$this->extension_manager->addDependant('incentive_total', $name);

// add new menu item
$rm = new AResourceManager();
$rm->setType('image');

$language_id = $this->language->getContentLanguageID();
$data = [];
$data['resource_code'] = '<i class="fa fa-rocket"></i>&nbsp;';
$data['name'] = [$language_id => 'Menu Icon Incentives'];
$data['title'] = [$language_id => ''];
$data['description'] = [$language_id => ''];
$resource_id = $rm->addResource($data);

// add new menu item
$menu = new AMenu ("admin");
$menu->insertMenuItem(
    [
        "item_id"         => "incentive",
        "parent_id"       => "sale",
        "item_text"       => "incentive_name",
        "item_url"        => "sale/incentive",
        "item_icon_rl_id" => $resource_id,
        "item_type"       => "extension",
        "sort_order"      => "7",
    ]
);
