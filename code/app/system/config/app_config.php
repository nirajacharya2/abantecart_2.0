<?php
return array(
		'APP_NAME' => 'AbanteCart',
		'MIN_PHP_VERSION' => '7.0',
		'DIR_CORE' => DIR_APP . 'core/',
		'DIR_LIB' => DIR_APP . 'lib/',
		//resources
		'DIR_RESOURCE' => DIR_ASSETS . 'resources/',
		// SEO URL Keyword separator
		'SEO_URL_SEPARATOR' => '-',
		// EMAIL REGEXP PATTERN
		'EMAIL_REGEX_PATTERN' => '/^[A-Z0-9._%-]+@[A-Z0-9.-]{0,61}[A-Z0-9]\.[A-Z]{2,16}$/i',
		//postfixes for template override
		'POSTFIX_OVERRIDE' => '.override',
		'POSTFIX_PRE' => '.pre',
		'POSTFIX_POST' => '.post'
);