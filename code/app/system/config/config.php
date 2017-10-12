<?php

return array_merge(
	require 'app_config.php',
	require 'database.php',
	array(
		'SERVER_NAME' => 'abolabo.hopto.org',
		'ADMIN_PATH' => 'admin',
		'CACHE_DRIVER' => 'file',
		'UNIQUE_ID' => '',
		'ENCRYPTION_KEY' => '1Nb07O',
	)
);
