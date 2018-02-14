<?php

use abc\core\ABC;

$class_list = [
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
        'promotion',
        'language',
    ],
    'core/cache'   => [
        'cache',
    ],
    'core/view'   => [
        'view',
    ],
    'core/helper' => [
        'global',
        'helper',
        'html',
        'utils',
        'system_check',
    ],
    'core/lib'         => [
        'config',
        'db',
        'connect',
        'document',
        'image',
        'log',
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
        'order',
        'order_status',
        'currency',
        'tax',
        'weight',
        'length',
        'cart',
        'user',
        'dataset',
        'encryption',
        'menu_control',
        'menu_control_storefront',
        'rest',
        'filter',
        'listing',
        'task_manager',
        'im',
        'csrf_token',
    ],
];
//load classes
$dir_app = ABC::env('DIR_APP');
foreach ($class_list as $sub_dir => $files) {
    $sub_dir = DIRECTORY_SEPARATOR != '/' ? str_replace('/',DIRECTORY_SEPARATOR,$sub_dir) : $sub_dir;
    foreach ($files as $name) {
        require_once $dir_app.$sub_dir.DIRECTORY_SEPARATOR.$name.'.php';
    }
}
unset($class_list);

//load vendors classes
@include(ABC::env('DIR_VENDOR').'autoload.php');
