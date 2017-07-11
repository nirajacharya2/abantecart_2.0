<?php
$lib_list = array(
					'order_manager',
					'layout_manager',
					'content_manager',
					'package_manager',
					'form_manager',
					'extension_manager',
					'resource_manager',
					'resource_upload',
					'listing_manager',
					'attribute_manager',
					'language_manager',
					'backup',
					'file_uploads_manager',
					'admin_commands',
					'im_manager');

// Include Engine
foreach($lib_list as $lib_name){
	require_once DIR_LIB . $lib_name .'.php';
}
unset($lib_list);



define('HTTP_DIR_NAME', rtrim(dirname($_SERVER['PHP_SELF']), '/.\\') );
// Admin HTTP
define('AUTO_SERVER', '//' . REAL_HOST . HTTP_DIR_NAME . '/');
define('HTTP_SERVER', 'http:' . AUTO_SERVER);
define('HTTP_CATALOG', HTTP_SERVER);
//define('HTTP_EXT', HTTP_SERVER . 'extensions/');
define('HTTP_IMAGE', HTTP_SERVER . 'image/');
define('HTTP_DIR_RESOURCE', HTTP_SERVER . 'resources/');
//we use Protocol-relative URLs here
define('HTTPS_IMAGE', AUTO_SERVER . 'image/');
define('HTTPS_DIR_RESOURCE', AUTO_SERVER . 'resources/');
//Admin HTTPS
if ( HTTPS === true) {
	define('HTTPS_SERVER', 'https:' . AUTO_SERVER);
	define('HTTPS_CATALOG', HTTPS_SERVER);
	define('HTTPS_EXT', HTTPS_SERVER . 'extensions/');
} else {
	define('HTTPS_SERVER', HTTP_SERVER);
	define('HTTPS_CATALOG', HTTP_CATALOG);
	define('HTTPS_EXT', HTTP_EXT);
}

//Admin specific loads
$registry->set('extension_manager', new AExtensionManager());

//Now we have session, reload config for store if provided or set in session
$session = $registry->get('session');
if (has_value($request->get['store_id']) || has_value($session->data['current_store_id']) ) {
	$config = new AConfig($registry);
	$registry->set('config', $config);
}