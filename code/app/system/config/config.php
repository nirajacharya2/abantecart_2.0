<?php
/**
	AbanteCart, Ideal OpenSource Ecommerce Solution
	http://www.AbanteCart.com
	Copyright © 2011-2017 Belavier Commerce LLC

	Released under the Open Software License (OSL 3.0)
*/

define('SERVER_NAME', 'abolabo.hopto.org');
// Admin Section Configuration. You can change this value to any name. Will use ?s=name to access the admin
define('ADMIN_PATH', 'admin');

define('CACHE_DRIVER', 'file');
// Unique AbanteCart store ID
define('UNIQUE_ID', '11d6f661c7348ef0a534b3c40d0a3de2');
// Encryption key for protecting sensitive information. NOTE: Change of this key will cause a loss of all existing encrypted information!
define('ENCRYPTION_KEY', '1Nb07O');

require 'app_config.php';
