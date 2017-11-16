<?php
// Storefront HTTP
use abc\core\Registry;

$config = Registry::getInstance()->get('config');
$store_url = $config->get('config_url');
define('HTTP_SERVER', $store_url);
define('HTTP_IMAGE', HTTP_SERVER . 'assets/images/');
define('HTTP_EXT', HTTP_SERVER . 'assets/extensions/');
define('HTTP_DIR_RESOURCE', HTTP_SERVER . 'assets/resources/');
// Storefront HTTPS
if ($config->get('config_ssl') || HTTPS === true) {
	if ( $config->get('config_ssl_url') ) {
		$store_url = $config->get('config_ssl_url');
	}
	define('AUTO_SERVER', '//' . preg_replace('/\w+:\/\//','', $store_url));
	define('HTTPS_SERVER', 'https:' . AUTO_SERVER);
	define('HTTPS_EXT', HTTPS_SERVER . 'assets/extensions/');
} else {
	define('AUTO_SERVER', '//' . preg_replace('/\w+:\/\//','', $store_url));
	define('HTTPS_SERVER', HTTP_SERVER);
	define('HTTPS_EXT', HTTP_EXT);
}
//we use Protocol-relative URLs here
define('HTTPS_DIR_RESOURCE', AUTO_SERVER . 'assets/resources/');
define('HTTPS_IMAGE', AUTO_SERVER . 'assets/images/');

//set internal sign of shared ssl domains
if(preg_replace('/\w+:\/\//','',HTTPS_SERVER) != preg_replace('/\w+:\/\//','',HTTP_SERVER) ){
	$registry->get('config')->set('config_shared_session',true);
}

// Relative paths and directories
define('RDIR_TEMPLATE',  'assets/templates/' . $config->get('config_storefront_template') . '/storefront/');