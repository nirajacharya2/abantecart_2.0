<?php
// set default encoding for multibyte php mod
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'utf-8');

define('DIR_CORE', DIR_APP . 'core/');
define('DIR_LIB', DIR_APP . 'lib/');
//resources

define('DIR_RESOURCE', DIR_ASSETS . 'resources/');

// SEO URL Keyword separator
define('SEO_URL_SEPARATOR', '-');

// EMAIL REGEXP PATTERN
define('EMAIL_REGEX_PATTERN','/^[A-Z0-9._%-]+@[A-Z0-9.-]{0,61}[A-Z0-9]\.[A-Z]{2,16}$/i');

//postfixes for template override
define('POSTFIX_OVERRIDE', '.override');
define('POSTFIX_PRE', '.pre');
define('POSTFIX_POST', '.post');

require_once 'database.php';



