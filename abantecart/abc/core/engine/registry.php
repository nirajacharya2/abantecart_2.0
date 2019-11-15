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
use abc\core\lib\ACart;
use abc\core\lib\AConfig;
use abc\core\lib\AConfigManager;
use abc\core\lib\ACurrency;
use abc\core\lib\ACustomer;
use abc\core\lib\ADataEncryption;
use abc\core\lib\ADB;
use abc\core\lib\ADownload;
use abc\core\lib\AIM;
use abc\core\lib\AIMManager;
use abc\core\lib\ALanguageManager;
use abc\core\lib\ALog;
use abc\core\lib\AMessage;
use abc\core\lib\AOrderStatus;
use abc\core\lib\ARequest;
use abc\core\lib\ASession;
use abc\core\lib\AUser;

/**
 * Class Registry
 *
 * @package abc\core\engine
 * @method static ALanguage|ALanguageManager language()
 * @method static ALog log()
 * @method static ADB db()
 * @method static ADataEncryption dcrypt()
 * @method static AIM|AIMManager im()
 * @method static AConfig|AConfigManager config()
 * @method static ARequest request()
 * @method static ASession session()
 * @method static ALoader load()
 * @method static ExtensionsApi extensions()
 * @method static ACustomer customer()
 * @method static ACart cart()
 * @method static AHtml html()
 * @method static AUser user()
 * @method static ACurrency currency()
 * @method static ADownload download()
 * @method static AbcCache cache()
 * @method static AOrderStatus order_status()
 * @method static AMessage messages()
 */
final class Registry
{
    private $data = [];
    static private $instance = null;

    /**
     * @return Registry
     */
    static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Registry();
        }
        return self::$instance;
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * @param $key string
     *
     * @return \abc\core\lib\CSRFToken|\abc\core\lib\ARequest|ALoader|\abc\core\lib\ADocument|\abc\core\lib\ADB|\abc\core\lib\AConfig|AHtml|ExtensionsApi|\abc\core\lib\AExtensionManager|\abc\core\lib\ALanguageManager|\abc\core\lib\ASession|\abc\core\lib\AbcCache|\abc\core\lib\AMessage|\abc\core\lib\ALog|\abc\core\lib\AResponse|\abc\core\lib\AUser|ARouter|\abc\core\lib\ACurrency|\abc\models\admin\ModelLocalisationLanguageDefinitions|\abc\models\admin\ModelLocalisationCountry|\abc\models\admin\ModelSettingSetting|\abc\models\admin\ModelToolOnlineNow|\abc\core\lib\ADataEncryption|\abc\core\lib\ADownload|\abc\core\lib\AOrderStatus|\abc\core\lib\AIMManager|\abc\core\lib\ACustomer
     */
    public function get($key)
    {
        return (isset($this->data[$key]) ? $this->data[$key] : null);
    }

    /**
     * @param $key string
     * @param $value mixed
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * @param $key string
     *
     * @return bool
     */
    public function has($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Return objects by static call
     * @param $name
     *
     * @return mixed - object or null
     */
    public static function __callStatic($name, $arguments)
    {
        return self::getInstance()->get($name);
    }
}