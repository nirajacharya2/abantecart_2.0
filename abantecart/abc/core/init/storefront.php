<?php
// Storefront HTTP
use abc\ABC;
use abc\core\engine\Registry;

$config = Registry::getInstance()->get('config');
$store_url = $config->get('config_url');
ABC::env('HTTP_SERVER', $store_url);
ABC::env('HTTP_IMAGE', ABC::env('HTTP_SERVER') . 'assets/images/');
ABC::env('HTTP_EXT', ABC::env('HTTP_SERVER') . 'assets/extensions/');
ABC::env('HTTP_DIR_RESOURCE', ABC::env('HTTP_SERVER') . 'assets/resources/');
// Storefront HTTPS
if ($config->get('config_ssl') || ABC::env('HTTPS')) {
	if ( $config->get('config_ssl_url') ) {
		$store_url = $config->get('config_ssl_url');
	}
	ABC::env('AUTO_SERVER', '//' . preg_replace('/\w+:\/\//','', $store_url));
	ABC::env('HTTPS_SERVER', 'https:' . ABC::env('AUTO_SERVER'));
	ABC::env('HTTPS_EXT', ABC::env('HTTPS_SERVER') . 'assets/extensions/');
} else {
	ABC::env('AUTO_SERVER', '//' . preg_replace('/\w+:\/\//','', $store_url));
	ABC::env('HTTPS_SERVER', ABC::env('HTTP_SERVER'));
	ABC::env('HTTPS_EXT', ABC::env('HTTP_EXT'));
}
//we use Protocol-relative URLs here
ABC::env('HTTPS_DIR_RESOURCE', ABC::env('AUTO_SERVER') . 'assets/resources/');
ABC::env('HTTPS_IMAGE', ABC::env('AUTO_SERVER') . 'assets/images/');

//set internal sign of shared ssl domains
if(preg_replace('/\w+:\/\//','',ABC::env('HTTPS_SERVER')) != preg_replace('/\w+:\/\//','',ABC::env('HTTP_SERVER')) ){
	$config->set('config_shared_session',true);
}

// Relative paths and directories
ABC::env('RDIR_ASSETS', 'assets/templates/' . $config->get('config_storefront_template') . '/storefront/');
ABC::env('RDIR_TEMPLATE', 'templates/' . $config->get('config_storefront_template') . '/storefront/');