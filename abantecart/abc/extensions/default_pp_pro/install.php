<?php
namespace abc\controllers\admin;
use abc\core\ABC;
use abc\core\lib\AResourceManager;

if (!class_exists('abc\core\ABC')) {
	header('Location: static_pages/?forbidden='.basename(__FILE__));
}

$language_list = $this->model_localisation_language->getLanguages();


$rm = new AResourceManager();
$rm->setType('image');
$resource = [
	'language_id' => $this->config->get('storefront_language_id'),
	'name' => array(),
	'title' => array(),
	'description' => array(),
	'resource_path' => 'secure_paypal_icon.jpg',
	'resource_code' => ''
];

@copy(ABC::env('DIR_APP_EXTENSIONS')
    .'default_pp_pro'.DS
    .ABC::env('DIRNAME_TEMPLATES')
    .'default'.DS
    .ABC::env('DIRNAME_STORE')
    .ABC::env('DIRNAME_ASSETS')
    .'images'.DS
    .'secure_paypal_icon.jpg',
     ABC::env('DIR_RESOURCES')
     .'image'.DS
     .$resource['resource_path']);

foreach($language_list as $lang){
	$resource['name'][$lang['language_id']] = 'secure_paypal_icon.jpg';
	$resource['title'][$lang['language_id']] = 'default_pp_pro_payment_storefront_icon';
	$resource['description'][$lang['language_id']] = 'Default PayPal Pro Default Storefront Icon';
}
$resource_id = $rm->addResource($resource);

if ( $resource_id ) {
	// get hex-path of resource (RL moved given file from rl-image-directory in own dir tree)
	$resource_info = $rm->getResource($resource_id, $this->config->get('admin_language_id'));
	// write it path in settings (array from parent method "install" of extension manager)
	$settings['default_pp_pro_payment_storefront_icon'] =  'image/'.$resource_info['resource_path'];
}
