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

/* SSL Based encryption class PHP 5.3 >
   Manual Configuration is required

Requirement: PHP => 5.3 and openSSL enabled

NOTE: Do not confuse SSL data encryption with signed SSL certificates (HTTPS) used for browser access to sites

Configuration:
Add key storage location path.
Add below lines to /abc/config/config.php file. Change path to your specific path on your server
'ENCRYPTION_KEYS_DIR' => '/path/to/keys/'

NOTES:
1. Keep Key in secure location with restricted file permissions for root and apache (webserver)
2. There is no key expiration management.
These needs to be accounted for in key management procedures


Examples:

1. Generate Keys

$password = "123456";
$conf = array (
	'digest_alg'       => 'sha512',
	'private_key_type' => OPENSSL_KEYTYPE_RSA,
	'private_key_bits' => 2048,
	'encrypt_key'      => true
);
$enc = new ASSLEncryption ();
$keys = $enc->generateSslKeyPair($conf, $password);
$enc->save_ssl_key_pair($keys, 'key_with_pass');
H::echoArray($keys);

2. Encrypt

$enc = new ASSLEncryption ('key_with_pass');
$enc_str = $enc->encrypt('test text');
echo $enc_str;

3. Decrypt

$enc = new ASSLEncryption ('', 'key_with_pass', $password);
echo $enc->decrypt($enc_str);


Need help configuring, supporting or extending functionality,
contact www.abantecart.com for forum or paid support




 SSL Based data encryption class based on ASSLEncryption class
 Manual Configuration is required
*/

/**
 * Class ASSLEncryption
 *
 * @package abc\core\lib
 */

final class ASSLEncryption
{
    private $public_key = '';
    private $private_key = '';
    private $key_path;
    private $failed_str = "*****";
    public $active = false;
    private $registry;
    private $message;
    private $log;

    //To generate new keys, class can be initiated with no data passed
    function __construct($public_key_name = '', $private_key_name = '', $pass_phrase = null)
    {
        $this->registry = Registry::getInstance();
        $this->log = $this->registry->get('log');
        $this->message = $this->registry->get('messages');

        //Validate if SSL PHP support is installed
        if (!function_exists('openssl_pkey_get_public')) {
            $error = "Error: PHP OpenSSL is not available on your server! "
                ."Check if OpenSSL installed for PHP and enabled";
            $this->log->write($error);
            $this->message->saveError('OpenSSL Error', $error);
            return null;
        }

        //construct key storage path
        //NOTE: ENCRYPTION_KEYS_DIR needs to be added into configuration file
        //Suggested:  Directory to be secured for read and write ONLY for users root and apache (web server).
        $this->key_path = ABC::env('ENCRYPTION_KEYS_DIR') ?:  ABC::env('DIR_SYSTEM').'keys/';

        if ($public_key_name) {
            $this->public_key = $this->getPublicKey($public_key_name.'.pub');
        }
        if ($private_key_name) {
            $this->loadPrivateKey($private_key_name.'.prv', $pass_phrase);
        }
        $this->active = true;
    }

    /**
     * Generate new Key Private/Public keys pair.
     *
     * @param array         $config      - array with standard openssl_csr_new config-args
     * @param null | string $pass_phrase - is set if want to have a pass-phrase to access private key
     *
     * @return array
     */
    public function generateSslKeyPair($config = [], $pass_phrase = null)
    {
        $default_length = 2048;

        if (!isset($config['private_key_bits'])) {
            $config['private_key_bits'] = $default_length;
        }
        //Set key bits limits
        if ($config['private_key_bits'] < 256) {
            $config['private_key_bits'] = 256;
        } else {
            if ($config['private_key_bits'] > 8192) {
                $config['private_key_bits'] = 8192;
            }
        }

        $config['private_key_type'] = (int)$config['private_key_type'];

        $res = openssl_pkey_new($config);

        //# Do we need to use pass-phrase for the key?
        $private_key = '';
        if ($config['encrypt_key']) {
            openssl_pkey_export($res, $private_key, $pass_phrase);
        } else {
            openssl_pkey_export($res, $private_key);
        }

        $public_key = openssl_pkey_get_details($res);
        $public_key = $public_key["key"];

        return ['public' => $public_key, 'private' => $private_key];
    }

    /**
     * Save Private/Public keys pair to set key_path location
     * Input: Private/Public keys pair array
     *
     * @param array  $keys
     * @param string $key_name
     *
     * @return string
     */
    public function save_ssl_key_pair($keys = [], $key_name = '')
    {
        if (!file_exists($this->key_path)) {
            $result = mkdir($this->key_path, 0700, true); // create dir with nested folders
        } else {
            $result = true;
        }
        if (!$result) {
            $error = "Error: Can't create directory ".$this->key_path." for saving SSL keys!";
            $this->log->write($error);
            $this->message->saveError('Create SSL Key Error', $error);
            return $error;
        }
        if (empty($key_name)) {
            $key_name = 'default_key';
        }

        foreach ($keys as $type => $key) {
            $ext = '';
            if ($type == 'private') {
                $ext = '.prv';
            } else {
                if ($type == 'public') {
                    $ext = '.pub';
                }
            }
            $file = $this->key_path.'/'.$key_name.$ext;
            if (file_exists($file)) {
                $error = "Error: Can't create key ".$key_name."! Already Exists";
                $this->log->write($error);
                $this->message->saveError('Create SSL Key Error', $error);
                return $error;
            }
            $handle = fopen($file, 'w');
            fwrite($handle, $key."\n");
            fclose($handle);
        }
        return '';
    }

    /**
     * Get public key based on key name provided. It is loaded if not yet loaded
     * Key's are stored in the path based on the configuration
     *
     * @param string $key_name
     *
     * @return string
     */
    public function getPublicKey($key_name)
    {
        if (empty($this->public_key)) {
            $this->public_key = openssl_pkey_get_public("file://".$this->key_path.$key_name);
        }
        return $this->public_key;
    }

    /**
     * Load private key based on key name provided.
     * Input : Key name and pass-phrase (if used)
     * Key's are stored in the path based on the configuration
     * NOTE: Private key value never turn back
     *
     * @param string $key_name
     * @param string $pass_phrase
     *
     * @return bool
     */
    public function loadPrivateKey($key_name, $pass_phrase = '')
    {
        $this->private_key = openssl_pkey_get_private("file://".$this->key_path.$key_name, $pass_phrase);
        if ($this->private_key) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Decrypt value based on private key ONLY
     *
     * @param string $crypttext
     *
     * @return string
     */
    public function decrypt($crypttext)
    {
        if (empty($crypttext)) {
            return '';
        }
        //check if encryption is off or this is not encrypted string
        if (!$this->active || !base64_decode($crypttext, true)) {
            return $crypttext;
        }

        $cleartext = '';
        if (empty($this->private_key)) {
            $error = "Error: SSL Decryption failed! Missing private key";
            $this->log->write($error);
            return $this->failed_str;
        }

        if ((openssl_private_decrypt(base64_decode($crypttext), $cleartext, $this->private_key)) === true) {
            return $cleartext;
        } else {
            $error = "Error: SSL Decryption based on private key has failed! '
                      .'Possibly corrupted encrypted data or wrong key!";
            $this->log->write($error);
            return $this->failed_str;
        }
    }

    /**
     * Encrypt value based on public key ONLY
     *
     * @param string $cleartext
     *
     * @return string
     */
    public function encrypt($cleartext)
    {
        if (empty($cleartext)) {
            return '';
        }
        //check if encryption is off or this is not encrypted string
        if (!$this->active) {
            return $cleartext;
        }

        $crypttext = '';
        if (empty($this->public_key)) {
            $error = "Error: SSL Encryption failed! Missing public key";
            $this->log->write($error);
            return '';
        }

        if ((openssl_public_encrypt($cleartext, $crypttext, $this->public_key)) === true) {
            return base64_encode($crypttext);
        } else {
            $error = "Error: SSL Encryption based on public key has failed! Possibly encryption error or wrong key!";
            $this->log->write($error);
            return '';
        }
    }

    public function getKeyPath()
    {
        return $this->key_path;
    }

}