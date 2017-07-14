<?php

include 'abc_base.php';
class ABC extends ABCBase{
	/**
	 * ABC constructor.
	 * @param array $config
	 */
	public function __construct($config){
		//define constants from on config variable
		foreach($config as $name=>$value){
			define($name, $value);
		}
	}

	public function run(){
		$this->_validate_app();

		// New Installation
		if (!defined('DB_DATABASE')) {
			header('Location: install/index.php');
			exit;
		}

		$registry = require 'core/init/app.php';
		ADebug::checkpoint('init end');

		//Route to request process
		$router = new ARouter($registry);
		$registry->set('router', $router);
		$router->processRoute(ROUTE);

		// Output
		$registry->get('response')->output();

		if( IS_ADMIN === true && $registry->get('config')->get('config_maintenance') && $registry->get('user')->isLogged() ) {
			$user_id = $registry->get('user')->getId();
			startStorefrontSession($user_id);
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