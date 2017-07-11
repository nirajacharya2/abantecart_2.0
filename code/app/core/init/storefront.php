<?php
// Storefront HTTP
$config = Registry::getInstance()->get('config');
$store_url = $config->get('config_url');
define('HTTP_SERVER', $store_url);
define('HTTP_IMAGE', HTTP_SERVER . 'image/');
define('HTTP_EXT', HTTP_SERVER . 'extensions/');
define('HTTP_DIR_RESOURCE', HTTP_SERVER . 'resources/');
// Storefront HTTPS
if ($config->get('config_ssl') || HTTPS === true) {
	if ( $config->get('config_ssl_url') ) {
		$store_url = $config->get('config_ssl_url');
	}
	define('AUTO_SERVER', '//' . preg_replace('/\w+:\/\//','', $store_url));
	define('HTTPS_SERVER', 'https:' . AUTO_SERVER);
	define('HTTPS_EXT', HTTPS_SERVER . 'extensions/');
} else {
	define('AUTO_SERVER', '//' . preg_replace('/\w+:\/\//','', $store_url));
	define('HTTPS_SERVER', HTTP_SERVER);
	define('HTTPS_EXT', HTTP_EXT);
}
//we use Protocol-relative URLs here
define('HTTPS_DIR_RESOURCE', AUTO_SERVER . 'resources/');
define('HTTPS_IMAGE', AUTO_SERVER . 'image/');

//set internal sign of shared ssl domains
if(preg_replace('/\w+:\/\//','',HTTPS_SERVER) != preg_replace('/\w+:\/\//','',HTTP_SERVER) ){
	$registry->get('config')->set('config_shared_session',true);
}