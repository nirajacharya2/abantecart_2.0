<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */

use abc\core\ABC;

//load vendors classes
@include(ABC::env('DIR_VENDOR').'autoload.php');

$class_list = [
    'models' => [ 'BaseModel' ],
    'core/engine' => [
        'router',
        'page',
        'response',
        'api',
        'task',
        'dispatcher',
        'controller',
        'controller_api',
        'loader',
        'model',
        'registry',
        'resources',
        'html',
        'layout',
        'form',
        'extensions',
        'hook',
        'attribute',
        'language',
    ],
    'core/view'   => [
        'view',
    ],
    'core/helper' => [
        'helper',
        'html',
        'utils',
        'system_check',
        'global',
    ],
    'core/lib'    => [
        'config',
        'db',
        'connect',
        'document',
        'image',
        'mail',
        'message',
        'pagination',
        'request',
        'response',
        'session',
        'template',
        'xml2array',
        'data',
        'file',
        'download',
        'customer',
        'checkoutBase',
        'checkout',
        'order_status',
        'currency',
        'tax',
        'weight',
        'length',
        'cart',
        'user',
        'dataset',
        'menu_control',
        'menu_control_storefront',
        'rest',
        'filter',
        'listing',
        'task_manager',
        'im',
        'csrf_token',
        'promotion',
        'json',
    ],
];
//load classes

$dir_app = ABC::env('DIR_APP');
require_once $dir_app.'core'.DS.'lib'.DS.'libBase.php';

foreach ($class_list as $sub_dir => $files) {
    $sub_dir = DS != '/' ? str_replace('/', DS, $sub_dir) : $sub_dir;
    foreach ($files as $name) {
        require_once $dir_app.$sub_dir.DS.$name.'.php';
    }
}

unset($class_list);
