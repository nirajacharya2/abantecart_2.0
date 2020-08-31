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

use abc\core\lib\AbcCache;

/**
 * @property \abc\core\lib\AConfig $config
 * @property \abc\core\lib\ADB $db
 * @property AbcCache $cache
 * @property AResource $resource
 * @property \abc\core\view\AView $view
 * @property ALoader $load
 * @property AHtml $html
 * @property \abc\core\lib\ARequest $request
 * @property \abc\core\lib\AResponse $response
 * @property \abc\core\lib\ASession $session
 * @property ExtensionsApi $extensions
 * @property \abc\core\lib\AExtensionManager $extension_manager
 * @property ALayout $layout
 * @property \abc\core\lib\ACurrency $currency
 * @property \abc\core\lib\ACart $cart
 * @property \abc\core\lib\ATax $tax
 * @property \abc\core\lib\AUser $user
 * @property \abc\core\lib\ALog $log
 * @property \abc\core\lib\AMessage $messages
 * @property \abc\core\lib\ACustomer $customer
 * @property \abc\core\lib\ADocument $document
 * @property \abc\core\lib\ALanguageManager $language
 * @property \abc\core\lib\ADataEncryption $dcrypt
 * @property \abc\core\lib\ADownload $download
 * @property \abc\core\lib\AOrderStatus $order_status
 * @property \abc\core\lib\AIMManager $im
 */
abstract class Model
{
    /**
     * @var Registry
     */
    public $registry;

    /**
     * @param $registry Registry
     */
    public function __construct($registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->registry->get($key);
    }

    public function __set($key, $value)
    {
        $this->registry->set($key, $value);
    }

    public function __call($method, $args)
    {
        if (!$this->registry->has('extensions')) {
            return null;
        }
        array_unshift($args, $this);
        $return = call_user_func_array([$this->registry->get('extensions'), $method], $args);
        return $return;
    }
}
