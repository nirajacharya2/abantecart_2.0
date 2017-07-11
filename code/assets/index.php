<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2017 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>
  
 UPGRADE NOTE: 
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.  
------------------------------------------------------------------------------*/

// Required PHP Version
define('MIN_PHP_VERSION', '5.3.0');
if (version_compare(phpversion(), MIN_PHP_VERSION, '<') == TRUE) {
    die( MIN_PHP_VERSION . '+ Required for AbanteCart to work properly! Please contact your system administrator or host service provider.');
}

if (!function_exists('simplexml_load_file')) {
    exit("simpleXML functions are not available. Please contact your system administrator or host service provider.");
}

// Load all initial set up and Configuration
define('DIR_ASSETS', __DIR__.'/');
require_once('../app/core/init/app.php');

// New Installation
if (!defined('DB_DATABASE')) {
	header('Location: install/index.php');
	exit;
}

ADebug::checkpoint('init end');

if (!defined('IS_ADMIN') || !IS_ADMIN ) { // storefront load

	// Relative paths and directories
	define('RDIR_TEMPLATE',  'templates/' . $config->get('config_storefront_template') . '/storefront/');

	// Customer
	$registry->set('customer', new ACustomer($registry));
	
	// Tax
	$registry->set('tax', new ATax($registry));

	// Weight
	$registry->set('weight', new AWeight($registry));

	// Length
	$registry->set('length', new ALength($registry));

	// Cart
	$registry->set('cart', new ACart($registry));

} else {
	// Admin template load
	// Relative paths and directories
	define('RDIR_TEMPLATE',  'templates/default/admin/');
	
	// User
	$registry->set('user', new AUser($registry));
}// end admin load

// Currency
$registry->set('currency', new ACurrency($registry));

//Route to request process
$router = new ARouter($registry);
$registry->set('router', $router);
$router->processRoute(ROUTE);

// Output
$registry->get('response')->output();

if( IS_ADMIN === true && $registry->get('config')->get('config_maintenance') && $registry->get('user')->isLogged() ) {
	$user_id = $registry->get('user')->getId();
	startStorefrontSession($user_id);
}

//Show cache stats if debugging
if($registry->get('config')->get('config_debug')){
    ADebug::variable('Cache statistics: ', $registry->get('cache')->stats() . "\n");
}

ADebug::checkpoint('app end');

//display debug info
if ( $router->getRequestType() == 'page' ) {
    ADebug::display();
}
