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

namespace abc\core\lib;

use abc\core\ABC;
use abc\core\engine\Registry;
use abc\core\lib\contracts\AEncryptionInterface;

final class AEncryption implements AEncryptionInterface
{
    private $key;

    /**
     * AEncryption constructor.
     *
     * @param string $key
     */
    function __construct( $key)
    {
        $this->key = (string)$key;
    }

    /**
     * Encode function
     *
     * @param string $str
     *
     * @return string
     */
    function encrypt(string $str)
    {
        if (!$this->key) {
            return $str;
        }

        $enc_str = '';
        if (!$this->checkOpenssl()) {
            //non openssl basic encryption
            for ($i = 0; $i < strlen($str); $i++) {
                $char = substr($str, $i, 1);
                $keychar = substr($this->key, ($i % strlen($this->key)) - 1, 1);
                $char = chr(ord($char) + ord($keychar));
                $enc_str .= $char;
            }
            $enc_str = base64_encode($enc_str);
        } else {
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
            $enc_str = base64_encode(openssl_encrypt($str, 'aes-256-cbc', $this->key, 0, $iv).'::'.$iv);
        }
        return str_replace('==', '', strtr($enc_str, '+/', '-_'));
    }

    /**
     * @param string $dbDriver
     * @param string $tableAlias
     * @param string $password
     *
     * @param string $section - can be 'storefront' or 'admin'
     *
     * @return string
     */
    public function getRawSqlHash(string $dbDriver, string $tableAlias, string $password, string $section = 'storefront'){
        $db = Registry::db();
        if($section == 'admin') {
            switch ($dbDriver) {
                case 'mysql':
                default:
                    return "SHA1(
                                CONCAT(".$db->table_name($tableAlias).".salt, 
                                        SHA1(CONCAT(".$db->table_name($tableAlias).".salt, 
                                             SHA1('".$db->escape($password)."')))
                                       )
                                )";
            }
        }else{
            switch ($dbDriver) {
                case 'mysql':
                default:
                    return "SHA1(
                                CONCAT(".$db->table_name($tableAlias).".salt, 
                                        SHA1(CONCAT(".$db->table_name($tableAlias).".salt, 
                                             SHA1('".$db->escape($password)."')))
                                       )
                                )";
            }
        }
    }

    /**
     * Decode function
     *
     * @param string $enc_str
     *
     * @return string
     */
    function decrypt(string $enc_str)
    {
        if (!$this->key) {
            return $enc_str;
        }

        $str = '';
        $enc_str = base64_decode(strtr($enc_str, '-_', '+/').'==');
        if (!$this->checkOpenssl()) {
            //non openssl basic decryption
            for ($i = 0; $i < strlen($enc_str); $i++) {
                $char = substr($enc_str, $i, 1);
                $keychar = substr($this->key, ($i % strlen($this->key)) - 1, 1);
                $char = chr(ord($char) - ord($keychar));
                $str .= $char;
            }
        } else {
            list($encrypted_data, $iv) = explode('::', $enc_str, 2);
            $str = openssl_decrypt($encrypted_data, 'aes-256-cbc', $this->key, 0, $iv);
        }
        return trim($str);
    }

    /**
     * @return bool
     */
    private function checkOpenssl()
    {
        if (!function_exists('openssl_encrypt')) {
            $error_text = 'openssl php-library did not load. It is recommended to '
                .'enable PHP openssl for system to function properly.';
            $registry = Registry::getInstance();
            $log = $registry->get('log');
            if (!is_object($log) || !method_exists($log, 'write')) {
                $log = ABC::getObjectByAlias('ALog');
                $registry->set('log', $log);
            }
            $log->write($error_text);
            return false;
        }
        return true;
    }

    /**
     * @param string $keyword
     * @param string $salt_key
     *
     * @param string $section
     *
     * @return string
     */
    static function getHash(string $keyword, string $salt_key, string $section = 'storefront')
    {
        return  sha1($salt_key.sha1($salt_key.sha1($keyword)));

    }
}




