<?php
/*
------------------------------------------------------------------------------
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
------------------------------------------------------------------------------  
*/
// Real path (operating system web root) to the directory where abantecart is installed
namespace abc;
use abc\lib\ASession;

$dir_app = dirname(__DIR__) . '/../../abc/';
// Windows IIS Compatibility
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
	define('IS_WINDOWS', true);
	$dir_app = str_replace('\\', '/', $dir_app);
}
// Load all initial set up
define('DIR_APP', $dir_app);
define('DIR_CORE', $dir_app.'core/');
define('DIR_LIB', $dir_app.'lib/');
define('DIR_ASSETS', basename(__DIR__).'/');

// HTTP
$dirname = rtrim(dirname($_SERVER['PHP_SELF']), '/.\\');
$dirname = strip_tags(html_entity_decode($dirname,ENT_QUOTES,'UTF-8'));
// Detect http host
if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
	define('REAL_HOST', $_SERVER['HTTP_X_FORWARDED_HOST']);
} else {
	define('REAL_HOST', $_SERVER['HTTP_HOST']);
}
define('HTTP_SERVER', 'http://' . REAL_HOST . $dirname);
define('HTTP_ABANTECART', 'http://' . $_SERVER['HTTP_HOST'] . dirname(dirname($dirname)));



//check if this is admin and show option to report this issue 
$from_admin = false;
$session_id = '';
if(isset($_GET['mode']) && $_GET['mode'] == 'admin') {
	$from_admin = true;
}
foreach(array_keys($_COOKIE) as $key) {
	if ( $from_admin === true && preg_match("/^AC_CP/", $key) ) {
		$session_id = $key;
		break;
	}
	if ( $from_admin !== true && preg_match("/^AC_SF/", $key) ) {
		$session_id = $key;
		break;
	}
}
define('SESSION_ID', $session_id);

//try to start session. 
require_once(DIR_CORE . 'engine/registry.php');
require_once(DIR_CORE . 'helper/helper.php');
require_once(DIR_CORE . 'helper/utils.php');
require_once(DIR_LIB . 'session.php');
$session = new ASession(SESSION_ID);

$error = 'Please check AbanteCart and webserver error logs for more details. You can check error log in the control panel if it is functional. Otherwise, refer to error log located on your web server';
if($session && isset($session->data['exception_msg']) && $session->data['exception_msg']){
	$error = $session->data['exception_msg'];
	$session->data['exception_msg'] = '';
}

if($from_admin){
	$subject = rawurlencode("AbanteCart Crash Report " . UNIQUE_ID);
	$pos = -2;
	$t ='';
	$count = 0;
	$log_contents_end = "Log file tail: \n\n";
	$log_handle = fopen(DIR_APP . "system/logs/error.txt", "r");
	//read 100 lines backwards from the eof or less 
	$max_lines = 100;
	$max_bytes = filesize(DIR_APP . "system/logs/error.txt");
	$lines = array();
	while ($count < $max_lines) {
		//read one line back
		while ($t != "\n") {
		    if(abs($pos) >= $max_bytes){
		    	break;
		    }
		    fseek($log_handle, $pos, SEEK_END);
		    $t = fgetc($log_handle);
		    $pos = $pos - 1;
		}
		$lines[] = fgets($log_handle);
		$count++;
		$t='';
	}
	fclose ($log_handle);
	
	$body = rawurlencode($log_contents_end . implode("", array_reverse($lines)) );
}

?>
<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>AbanteCart - Error</title>
<link rel="stylesheet" type="text/css" href="view/css/stylesheet.css" />
</head>
<body>
<div id="container">
  <div id="header"><img src="view/images/logo.png" alt="AbanteCart" title="AbanteCart" /></div>
  <div id="content">
    <div id="content_top"></div>
    <div id="content_middle">
	<h1 class="error">There has been a critical error processing your request</h1>
	<div style="width: 100%; display: inline-block;">
		<?php echo $error; ?>
	</div>
	<br><br>
	<center>
<?php 
	if($from_admin){
?>
		<div style="font-size: 16px;">
			<b><a href="mailto:help@abantecart.com?subject=<?php echo $subject ?>&body=<?php echo $body ?>">Report this problem to AbanteCart team (do not change email subject)</a></b>
		</div>
		<br><br>
<?php 
	}
?>	
		<div>
			<b><a href="http://docs.abantecart.com/pages/tips/troubleshooting.html" target="_docs">Check AbanteCart Troubleshooting Guide</a></b>
		</div>
		<br><br>
		<div>
			<b><a href="http://www.abantecart.com/contact-us" target="_abantecart">Need help? Contact for support</a></b>
		</div>

		<br><br>
		<div>
			<a href="<?php echo HTTP_ABANTECART; ?>">Go to main page</a>
		</div>
	</center>
    </div>
    <div id="content_bottom"></div>
  </div>
  <div id="footer"><a onclick="window.open('http://www.abantecart.com');">Project Homepage</a>|<a onclick="window.open('http://docs.abantecart.com');">Documentation</a>|<a onclick="window.open('http://forum.abantecart.com');">Support Forums</a>|<a onclick="window.open('http://marketplace.abantecart.com');">Marketplace</a></div>
</div>
</body>
</html>