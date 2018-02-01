<?php
/*------------------------------------------------------------------------------
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
------------------------------------------------------------------------------*/
namespace abc\core\engine;
if (!class_exists('abc\ABC')) {
	header('Location: static_pages/?forbidden='.basename(__FILE__));
}

final class Registry{
	private $data = array ();
	static private $instance = null;

	/**
	 * @return Registry
	 */
	static function getInstance(){
		if (self::$instance == null) {
			self::$instance = new Registry();
		}
		return self::$instance;
	}

	private function __construct(){
	}

	private function __clone(){
	}

	/**
	 * @param $key string
	 * @return \abc\lib\CSRFToken|\abc\lib\ARequest|ALoader|\abc\lib\ADocument|\abc\lib\ADB|\abc\lib\AConfig|AHtml|ExtensionsApi|\abc\lib\AExtensionManager|\abc\lib\ALanguageManager|\abc\lib\ASession|\abc\core\cache\ACache|\abc\lib\AMessage|\abc\lib\ALog|\abc\lib\AResponse|\abc\lib\AUser|ARouter|\abc\lib\ACurrency|\abc\models\admin\ModelLocalisationLanguageDefinitions|\abc\models\admin\ModelLocalisationCountry|\abc\models\admin\ModelSettingSetting|\abc\models\admin\ModelToolOnlineNow|\abc\lib\ADataEncryption|\abc\lib\ADownload|\abc\lib\AOrderStatus|\abc\lib\AIMManager|\abc\lib\ACustomer
	 */
	public function get($key){
		return (isset($this->data[$key]) ? $this->data[$key] : null);
	}

	/**
	 * @param $key string
	 * @param $value mixed
	 */
	public function set($key, $value){
		$this->data[$key] = $value;
	}

	/**
	 * @param $key string
	 * @return bool
	 */
	public function has($key){
		return isset($this->data[$key]);
	}
}