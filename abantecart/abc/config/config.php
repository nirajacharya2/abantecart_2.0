<?php
return array_merge(
	require 'app_config.php',
	require 'database.php',
	array(
		'SERVER_NAME' => 'abolabo.hopto.org',
		'ADMIN_PATH' => 'admin',
		'CACHE_DRIVER' => 'file',
		'UNIQUE_ID' => 'e85f648253246690e44ef7bfc6f26a1f'
	)
);