<?php
namespace abc;
use abc\core\engine\Registry;
use abc\core\helper\AHelperUtils;
use abc\core\engine\ARouter;
use abc\lib\ADebug;

require 'abc_base.php';

/**
 * Class ABC
 * @package abc
  */
class ABC extends ABCBase{
	/**
	 * ABC constructor.
	 * @param array $config - see folder /abc/config/
	 */
	public function __construct($config){
		//define constants from on config variable
		//NOTE: do not convert this into loop. IDE code-inspector stops to see them!
		define('APP_NAME', $config['APP_NAME']);
		define('MIN_PHP_VERSION', $config['MIN_PHP_VERSION']);
		define('DIR_ROOT', $config['DIR_ROOT']);
		define('DIR_APP', $config['DIR_APP']);
		define('DIR_PUBLIC', $config['DIR_PUBLIC']);
		define('SEO_URL_SEPARATOR', $config['SEO_URL_SEPARATOR']);
		define('EMAIL_REGEX_PATTERN', $config['EMAIL_REGEX_PATTERN']);
		define('POSTFIX_OVERRIDE', $config['POSTFIX_OVERRIDE']);
		define('POSTFIX_PRE', $config['POSTFIX_PRE']);
		define('POSTFIX_POST', $config['POSTFIX_POST']);
		define('APP_CHARSET', $config['APP_CHARSET']);
		define('DB_DRIVER', $config['DB_DRIVER']);
		define('DB_HOSTNAME', $config['DB_HOSTNAME']);
		define('DB_USERNAME', $config['DB_USERNAME']);
		define('DB_PASSWORD', $config['DB_PASSWORD']);
		define('DB_DATABASE', $config['DB_DATABASE']);
		define('DB_PREFIX', $config['DB_PREFIX']);
		define('DB_CHARSET', $config['DB_CHARSET']);
		define('DB_COLLATION', $config['DB_COLLATION']);
		define('SERVER_NAME', $config['SERVER_NAME']);
		define('ADMIN_PATH', $config['ADMIN_PATH']);
		define('CACHE_DRIVER', $config['CACHE_DRIVER']);
		define('UNIQUE_ID', $config['UNIQUE_ID']);
	}

	public function run(){
		$this->_validate_app();

		// New Installation
		if (!defined('DB_DATABASE')) {
			header('Location: install/index.php');
			exit;
		}

		require 'core/init/app.php';
		$registry = Registry::getInstance();
		ADebug::checkpoint('init end');

		//Route to request process
		$router = new ARouter($registry);
		$registry->set('router', $router);
		$router->processRoute(ROUTE);

		// Output
		$registry->get('response')->output();

		if( IS_ADMIN === true && $registry->get('config')->get('config_maintenance') && $registry->get('user')->isLogged() ) {
			$user_id = $registry->get('user')->getId();
			AHelperUtils::startStorefrontSession($user_id);
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
	}

	protected function _validate_app(){

		// Required PHP Version

		if (version_compare(phpversion(), MIN_PHP_VERSION, '<') == TRUE) {
			exit( MIN_PHP_VERSION . '+ Required for AbanteCart to work properly! Please contact your system administrator or host service provider.');
		}

		if (!function_exists('simplexml_load_file')) {
			exit("simpleXML functions are not available. Please contact your system administrator or host service provider.");
		}


	}
}