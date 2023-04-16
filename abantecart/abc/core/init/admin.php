<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2023 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */

use abc\core\ABC;
use abc\core\engine\Registry;
use abc\core\lib\AConfig;
use abc\core\lib\AExtensionManager;

$registry = Registry::getInstance();
$lib_list = [
    'package_manager',
    'form_manager',
    'extension_manager',
    'resource_manager',
    'resource_upload',
    'file_uploads_manager',
    'im_manager',
    'checkoutAdmin',
];
//load admin libraries
$dir_lib = ABC::env('DIR_LIB');
foreach ($lib_list as $lib_name) {
    require_once $dir_lib.$lib_name.'.php';
}
unset($lib_list);

ABC::env('HTTP_DIR_NAME', rtrim(dirname($_SERVER['PHP_SELF']), '/.\\'));
// Admin HTTP
ABC::env('AUTO_SERVER', '//'.ABC::env('REAL_HOST').ABC::env('HTTP_DIR_NAME').'/');
ABC::env('HTTP_SERVER', 'http:'.ABC::env('AUTO_SERVER'));
ABC::env('HTTP_CATALOG', ABC::env('HTTP_SERVER'));
ABC::env('HTTP_EXT', ABC::env('HTTP_SERVER').'extensions/');
ABC::env('HTTP_IMAGE', ABC::env('HTTP_SERVER').'images/');
ABC::env('HTTP_DIR_RESOURCES', ABC::env('HTTP_SERVER').'resources/');
//we use Protocol-relative URLs here
ABC::env('HTTPS_IMAGE', ABC::env('AUTO_SERVER').'images/');
ABC::env('HTTPS_DIR_RESOURCES', ABC::env('AUTO_SERVER').'resources/');
//Admin HTTPS
if (ABC::env('HTTPS')) {
    ABC::env('HTTPS_SERVER', 'https:'.ABC::env('AUTO_SERVER'));
    ABC::env('HTTPS_CATALOG', ABC::env('HTTPS_SERVER'));
    ABC::env('HTTPS_EXT', ABC::env('HTTPS_SERVER').'extensions/');
} else {
    ABC::env('HTTPS_SERVER', ABC::env('HTTP_SERVER'));
    ABC::env('HTTPS_CATALOG', ABC::env('HTTP_CATALOG'));
    ABC::env('HTTPS_EXT', ABC::env('HTTP_EXT'));
}
//Admin specific loads

$registry->set('extension_manager', new AExtensionManager());

//Now we have session, reload config for store if provided or set in session
$session = $registry->get('session');
if (H::has_value($registry::request()?->get['store_id']) || H::has_value($session?->data['current_store_id'])) {
    $config = new AConfig($registry);
    $registry->set('config', $config);
}

// Admin template load
// Relative paths and directories
ABC::env('RDIR_ASSETS', 'templates/default/admin/assets/');
ABC::env('RDIR_TEMPLATE', 'templates/'.(ABC::env('adminTemplate') ? ABC::env('adminTemplate') : 'default').'/admin/');
