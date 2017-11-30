<?php
use abc\ABC;

$class_list = [
		'core/engine'=> [
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
					'language'],
		'core/helper' => [
					'global',
					'helper',
					'html',
					'utils',
					'system_check'
					],
		'lib'  => [
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
				]
		];
//load classes
$dir_app = ABC::env('DIR_APP');
foreach($class_list as $sub_dir => $files){
	foreach($files as $name){
		require_once $dir_app . $sub_dir.'/'. $name .'.php';
	}
}
unset($class_list);

//load vendors classes
require( ABC::env('DIR_VENDOR').'autoload.php');
