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

// Load all initial set up and Configuration
if(!defined('DIR_APP')) {
	$dir_app = dirname(__DIR__) . '/app/';
	if( !is_dir($dir_app) ){
		//$dir_app =  __DIR__ . '/../app/';
	}
	// Windows IIS Compatibility
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
		define('IS_WINDOWS', true);
		$dir_app = str_replace('\\', '/', $dir_app);
	}
	define('DIR_APP', $dir_app);
}
define('DIR_PUBLIC', __DIR__ . '/');
define('DIR_ASSETS', __DIR__ . '/assets/');
define('INDEX_FILE', basename(__FILE__));

$config = require DIR_APP.'system/config/config.php';

require DIR_APP.'abc.php';
$app = new ABC($config);
$app->run();
