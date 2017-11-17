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
if (!defined ( 'DIR_APP' )) {
	header('Location: assets/static_pages/');
}

/**
 * @property \abc\lib\AConfig $config
 * @property \abc\lib\ADB $db
 * @property \abc\lib\ACache $cache
 * @property AResource $resource
 * @property \abc\core\engine\AView $view
 * @property ALoader $load
 * @property AHtml $html
 * @property \abc\lib\ARequest $request
 * @property \abc\lib\AResponse $response
 * @property \abc\lib\ASession $session
 * @property ExtensionsApi $extensions
 * @property \abc\lib\AExtensionManager $extension_manager
 * @property ALayout $layout
 * @property \abc\lib\ACurrency $currency
 * @property \abc\lib\ACart $cart
 * @property \abc\lib\ATax $tax
 * @property \abc\lib\AUser $user
 * @property \abc\lib\ALog $log
 * @property \abc\lib\AMessage $messages
 * @property \abc\lib\ACustomer $customer
 * @property \abc\lib\ADocument $document
 * @property \abc\lib\ALanguageManager $language
 * @property \abc\lib\ADataEncryption $dcrypt
 * @property \abc\models\admin\ModelCatalogCategory | \abc\models\storefront\ModelCatalogCategory $model_catalog_category
 * @property \abc\lib\ADownload $download
 * @property \abc\lib\AOrderStatus $order_status
 * @property \abc\lib\AIMManager $im
 */
abstract class Model{
	/**
	 * @var Registry
	 */
	public $registry;
	/**
	 * @param $registry Registry
	 */
	public function __construct($registry){
		$this->registry = $registry;
	}

	/**
	 * @param $key
	 * @return mixed
	 */
	public function __get($key){
		return $this->registry->get($key);
	}

	public function __set($key, $value){
		$this->registry->set($key, $value);
	}

	public function __call($method, $args){
		if (!$this->registry->has('extensions')) {
			return null;
		}
		array_unshift($args, $this);
		$return = call_user_func_array(array ($this->registry->get('extensions'), $method), $args);
		return $return;
	}
}
