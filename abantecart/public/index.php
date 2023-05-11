<?php

namespace abc;
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

ob_start();

define('DS', DIRECTORY_SEPARATOR);
require dirname(__DIR__) . DS . 'abc' . DS . 'core' . DS . 'ABC.php';

// Windows IIS Compatibility
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    ABC::env('IS_WINDOWS', true);
}
ABC::env('INDEX_FILE', basename(__FILE__));
$app = new ABC();
$app->run();