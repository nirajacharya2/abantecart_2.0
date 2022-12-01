<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2022 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\core\lib;

use abc\core\ABC;
use abc\core\engine\Registry;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

/**
 * Class ASession
 */
final class ASession
{
    public $config = null;
    public $data = [];
    public $ses_name = '';

    /**
     * @param string $ses_name
     * @throws AException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function __construct($ses_name)
    {


        $this->config = Registry::config();
        if (!session_id() || $ses_name) {
            $this->ses_name = $ses_name;
            $this->init($this->ses_name);
        }

        if ($this->config) {
            $session_ttl = $this->config->get('config_session_ttl');
            if ((isset($_SESSION['user_id']) || isset($_SESSION['customer_id']))
                && isset($_SESSION['LAST_ACTIVITY'])
                && ((time() - $_SESSION['LAST_ACTIVITY']) / 60 > $session_ttl)
            ) {
                // last request was more than 30 minutes ago
                $this->clear();
                abc_redirect(Registry::html()->currentURL(['token']));
            }
        }
        // update last activity time stamp
        $_SESSION['LAST_ACTIVITY'] = time();
        $this->data =& $_SESSION;
    }

    /**
     * @param string $session_name
     */
    public function init(string $session_name)
    {
        $session_mode = '';
        $path = '';
        if (ABC::env('IS_API')) {
            //set up session specific for API based on the token or create new
            $token = $this->getTokenFromHeaders() ?: $_GET['token'] ?: $_POST['token'];
            $final_session_id = $this->prepareSessionId($token);
            session_id($final_session_id);
        } else {
            $path = dirname($_SERVER['PHP_SELF']);
            if (php_sapi_name() != 'cli') {
                session_set_cookie_params(
                    0,
                    $path,
                    null,
                    false,
                    true);
            }
            if (!headers_sent()) {
                session_name($session_name);
            }
            if (php_sapi_name() != 'cli') {
                // for shared ssl domain set session id of non-secure domain
                if ($this->config) {
                    if ($this->config->get('config_shared_session') && isset($_GET['session_id'])) {
                        header('P3P: CP="CAO COR CURa ADMa DEVa OUR IND ONL COM DEM PRE"');
                        session_id($_GET['session_id']);
                        setcookie($session_name, $_GET['session_id'], 0, $path, null, false, true);
                    }
                }

                if (ABC::env('EMBED_TOKEN_NAME') && isset($_GET[ABC::env('EMBED_TOKEN_NAME')])
                    && !isset($_COOKIE[$session_name])) {
                    //check and reset session if it is not valid
                    $final_session_id = $this->prepareSessionId($_GET[ABC::env('EMBED_TOKEN_NAME')]);
                    session_id($final_session_id);
                    setcookie($session_name, $final_session_id, 0, $path, null, false);
                    $session_mode = 'embed_token';
                }
            }
        }

        //check if session can not be started. Try one more time with new generated session ID
        if (!headers_sent()) {
            $is_session_ok = session_start();
            if (!$is_session_ok) {
                //auto generating session id and try to start session again
                $final_session_id = $this->prepareSessionId();
                session_id($final_session_id);
                if (php_sapi_name() != 'cli') {
                    setcookie($session_name, $final_session_id, 0, $path, null, false);
                }
                session_start();
            }
        }
        $_SESSION['session_mode'] = $session_mode;
    }

    private function getHeaders()
    {
        return getallheaders();
    }

    private function getTokenFromHeaders()
    {
        $headers = $this->getHeaders();
        if (!$headers || !$headers['Authorization']) {
            return '';
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);
        if (!$token) {
            return '';
        }
        return $token;
    }

    public function clear()
    {
        session_unset();
        session_destroy();
        $_SESSION = [];
    }

    /**
     * This function to return clean validated session ID
     *
     * @param string|null $session_id
     *
     * @return string
     */
    protected function prepareSessionId(?string $session_id = '')
    {
        if (!$session_id || !$this->isSessionIdValid($session_id)) {
            //if session ID is invalid, generate new one
            $session_id = uniqid(substr(ABC::env('UNIQUE_ID'), 0, 4), true);
            return preg_replace("/[^-,a-zA-Z0-9]/", '', $session_id);
        } else {
            return $session_id;
        }
    }

    /**
     * This function is to validate session id
     *
     * @param string $session_id
     *
     * @return bool
     */
    protected function isSessionIdValid($session_id)
    {
        $reserved = [
            'null',
            'undefined',
        ];
        if (empty($session_id) || in_array($session_id, $reserved)) {
            return false;
        } else {
            return preg_match('/^[-,a-zA-Z0-9]{25,128}$/', $session_id) > 0;
        }
    }
}
