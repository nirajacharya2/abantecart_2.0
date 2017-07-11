<?php
$lib_list = array(
		'router',
		'page',
		'response',
		'api',
		'task',
		'dispatcher',
		'controller',
		'controller_api',
		'view',
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
		'language');
// Include libs
foreach($lib_list as $lib_name){
	require_once DIR_CORE . 'engine/'. $lib_name .'.php';
}

require_once DIR_CORE . 'helper/html.php';
require_once DIR_CORE . 'helper/utils.php';
require_once DIR_CORE . 'helper/system_check.php';

$lib_list = array(
		'cache',
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
		// Application Classes
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
		'csrf_token'
);

// Include libs
foreach($lib_list as $lib_name){
	require_once DIR_LIB . $lib_name .'.php';
}
unset($lib_list);
