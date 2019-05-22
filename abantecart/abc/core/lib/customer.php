<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */

namespace abc\core\lib;

use abc\core\ABC;
use abc\core\engine\ALanguage;
use abc\core\engine\Registry;
use abc\models\customer\Address;
use abc\models\customer\Customer;
use abc\models\customer\CustomerNotification;
use abc\models\storefront\ModelToolOnlineNow;
use abc\modules\events\ABaseEvent;
use H;
use ReCaptcha\ReCaptcha;

/**
 * Class ACustomer
 */
class ACustomer extends ALibBase
{
    static $errors = [];
    /**
     * @var int
     */
    protected $customer_id;
    /**
     * @var string
     */
    protected $loginname;
    /**
     * @var string
     */
    protected $firstname;
    /**
     * @var string
     */
    protected $lastname;
    /**
     * @var string
     */
    protected $email;
    /**
     * @var string
     */
    protected $telephone;
    /**
     * @var string
     */
    protected $fax;
    /**
     * @var int
     */
    protected $newsletter;
    /**
     * @var int
     */
    protected $customer_group_id;
    /**
     * @var string
     */
    protected $customer_group_name;
    /**
     * @var bool
     */
    protected $customer_tax_exempt;
    /**
     * @var int
     */
    protected $address_id;
    /**
     * @var int
     */
    protected $store_id;
    /**
     * @var AConfig
     */
    protected $config;
    /**
     * @var \abc\core\cache\ACache
     */
    protected $cache;
    /**
     * @var ADB
     */
    protected $db;
    /**
     * @var \abc\core\engine\ALoader
     */
    protected $load;
    /**
     * @var ARequest
     */
    protected $request;
    /**
     * @var ASession
     */
    protected $session;
    /**
     * @var ADataEncryption
     */
    protected $dcrypt;
    /**
     * @var \abc\core\engine\ExtensionsApi
     */
    protected $extensions;

    /**
     * @var array (unauthenticated customer details)
     */
    protected $unauth_customer = [];

    /**
     * @param  \abc\core\engine\Registry $registry
     *
     * @param int $customer_id
     *
     * @throws AException
     */
    public function __construct($registry, $customer_id = 0)
    {
        $this->cache = $registry->get('cache');
        $config = $registry->get('config');
        $this->db = $registry->get('db');
        $this->request = $registry->get('request');
        $this->session = $registry->get('session');
        $this->dcrypt = $registry->get('dcrypt');
        $this->load = $registry->get('load');
        $this->extensions = $registry->get('extensions');

        $customer_id = (int)$customer_id;
        $customer_id = (!$customer_id && isset($this->session->data['customer_id']))
            ? (int)$this->session->data['customer_id']
            : $customer_id;
        if ($customer_id) {
            $sql = "SELECT c.*, cg.* 
                     FROM ".$this->db->table_name("customers")." c
                     LEFT JOIN ".$this->db->table_name("customer_groups")." cg 
                        ON c.customer_group_id = cg.customer_group_id
                     WHERE customer_id = '".(int)$customer_id."'";
            if (!ABC::env('IS_ADMIN')) {
                $sql .= " AND STATUS = '1'";
            }

            $customer_data = $this->db->query($sql);
            if ($customer_data->num_rows) {
                $this->customerInit($customer_data->row);
            } else {
                $this->logout();
            }
        } elseif (isset($this->request->cookie['customer'])) {
            //we have unauthenticated customer
            /**
             * @var AEncryption $encryption
             */
            $encryption = ABC::getObjectByAlias('AEncryption', [$config->get('encryption_key')]);
            $this->unauth_customer = unserialize($encryption->decrypt($this->request->cookie['customer']));
            //customer is not valid or not from the same store (under the same domain)
            if (
                $this->unauth_customer['script_name'] != $this->request->server['SCRIPT_NAME']
                || !$this->isValidEnabledCustomer()
            ) {
                //clean up
                $this->unauth_customer = [];
                //expire unauth cookie
                unset($_COOKIE['customer']);
                setcookie('customer', '', time() - 3600, dirname($this->request->server['PHP_SELF']));
            }
            //check if unauthenticated customer cart content was found and merge with session
            $saved_cart = $this->getCustomerCart();
            if (!empty($saved_cart) && count($saved_cart)) {
                $this->mergeCustomerCart($saved_cart);
            }
        }

        //Update online customers' activity
        $ip = $this->request->getRemoteIP();
        $url = '';
        if (isset($this->request->server['HTTP_HOST']) && isset($this->request->server['REQUEST_URI'])) {
            $url = 'http://'.$this->request->server['HTTP_HOST'].$this->request->server['REQUEST_URI'];
        }
        $referer = '';
        if (isset($this->request->server['HTTP_REFERER'])) {
            $referer = $this->request->server['HTTP_REFERER'];
        }
        $customer_id = '';
        if ($this->isLogged()) {
            $customer_id = $this->getId();
        } elseif ($this->isUnauthCustomer()) {
            $customer_id = $this->isUnauthCustomer();
        }
        $model = $this->load->model('tool/online_now', 'storefront');
        /**
         * @var ModelToolOnlineNow $model ;
         */
        $model->setOnline($ip, $customer_id, $url, $referer);
        //call hooks
        $this->extensions->hk_ProcessData($this, 'constructor', $customer_id);
    }

    /**
     * @param string $loginname
     * @param string $password
     *
     * @return bool
     * @throws AException
     */
    public function login($loginname, $password)
    {

        $approved_only = '';
        if ($config->get('config_customer_approval')) {
            $approved_only = " AND approved = '1'";
        }

        /**
         * @deprecated !!!
         */
        //Supports older passwords for upgraded/migrated stores prior to 1.2.8
        $add_pass_sql = '';
        if (ABC::env('SALT')) {
            $add_pass_sql = " OR password = '".$this->db->escape(md5($password.ABC::env('SALT')))."'";
        }
        $customer_data = $this->db->query(
            "SELECT *
            FROM ".$this->db->table_name("customers")."
            WHERE LOWER(loginname)  = LOWER('".$this->db->escape($loginname)."')
            AND (
                password = SHA1(CONCAT(salt, SHA1(CONCAT(salt, SHA1('".$this->db->escape($password)."')))
                        ))".$add_pass_sql.") AND status = '1' ".$approved_only);
        if ($customer_data->num_rows) {

            $this->customerInit($customer_data->row);
            $this->session->data['customer_id'] = $this->customer_id;
            //load customer saved cart and merge with session cart before login
            $cart = $this->getCustomerCart();
            $this->mergeCustomerCart($cart);
            //save merged cart
            $this->saveCustomerCart();

            //set cookie for unauthenticated user (expire in 1 year)
            $encryption = new AEncryption($config->get('encryption_key'));
            $customer_data = $encryption->encrypt(serialize([
                'first_name'  => $this->firstname,
                'customer_id' => $this->customer_id,
                'script_name' => $this->request->server['SCRIPT_NAME'],
            ]));
            //Set cookie for this customer to track unauthenticated activity, expire in 1 year
            setcookie('customer',
                $customer_data,
                time() + 60 * 60 * 24 * 365,
                dirname($this->request->server['PHP_SELF']),
                null,
                ABC::env('HTTPS'),
                true
            );
            //set date of login
            $this->setLastLogin($this->customer_id);
            $this->extensions->hk_ProcessData($this, 'login_success', $customer_data);

            return true;
        } else {
            $this->extensions->hk_ProcessData($this, 'login_failed');

            return false;
        }
    }

    /**
     * Init customer
     *
     * @param $data array
     *
     * @return void
     * @throws AException
     */
    private function customerInit($data)
    {

        $this->customer_id = (int)$data['customer_id'];
        $this->store_id = (int)$data['store_id'];
        $this->loginname = $data['loginname'];
        $this->firstname = $data['firstname'];
        $this->lastname = $data['lastname'];
        if ($this->dcrypt->active) {
            $this->email = $this->dcrypt->decrypt_field($data['email'], $data['key_id']);
            $this->telephone = $this->dcrypt->decrypt_field($data['telephone'], $data['key_id']);
            $this->fax = $this->dcrypt->decrypt_field($data['fax'], $data['key_id']);
        } else {
            $this->email = $data['email'];
            $this->telephone = $data['telephone'];
            $this->fax = $data['fax'];
        }
        $this->newsletter = (int)$data['newsletter'];

        $this->customer_group_id = (int)$data['customer_group_id'];
        //save it to use in APromotion class
        $this->session->data['customer_group_id'] = (int)$data['customer_group_id'];

        $this->customer_group_name = $data['name'];

        $this->customer_tax_exempt = $data['tax_exempt'];
        //save this sign to use in ATax lib
        $this->session->data['customer_tax_exempt'] = $data['tax_exempt'];

        $this->address_id = (int)$data['address_id'];

    }

    public function setLastLogin($customer_id)
    {
        $customer_id = (int)$customer_id;
        if (!$customer_id) {
            return false;
        }

        //insert new record
        $this->db->query("UPDATE `".$this->db->table_name("customers")."`
                        SET `last_login` = NOW()
                        WHERE customer_id = ".$customer_id);

        //call event
        H::event(
            'abc\core\lib\customer@login',
            [new ABaseEvent($customer_id)]);

        return true;
    }

    /**
     * @void
     */
    public function logout()
    {
        $customer_id = $this->customer_id;
        unset($this->session->data['customer_id']);
        unset($this->session->data['customer_group_id']);
        unset($this->session->data['customer_tax_exempt']);

        $this->customer_id = '';
        $this->loginname = '';
        $this->firstname = '';
        $this->lastname = '';
        $this->email = '';
        $this->telephone = '';
        $this->fax = '';
        $this->newsletter = '';
        $this->customer_group_id = '';
        $this->customer_group_name = '';
        $this->customer_tax_exempt = '';
        $this->address_id = '';

        //expire unauth cookie
        unset($_COOKIE['customer']);
        setcookie('customer', '', time() - 3600, dirname($this->request->server['PHP_SELF']));
        $this->extensions->hk_ProcessData($this, 'logout');
        //call event
        try {
            H::event(
                'abc\core\lib\customer@logout',
                [new ABaseEvent($customer_id)]);
        } catch (AException $e) {
        }
    }

    /**
     * @param string $token
     *
     * @return bool|int
     */
    public function isLoggedWithToken($token)
    {
        if ((isset($this->session->data['token']) && !isset($token))
            || ((isset($token) && (isset($this->session->data['token']) && ($token != $this->session->data['token']))))
        ) {
            return false;
        } else {
            return $this->customer_id;
        }
    }

    /**
     * @return int
     */
    public function isUnauthCustomer()
    {
        return $this->unauth_customer['customer_id'];
    }

    /**
     * @return string
     */
    public function getUnauthName()
    {
        return $this->unauth_customer['first_name'];
    }

    /**
     * @return int
     */
    public function isLogged()
    {
        return $this->customer_id;
    }

    /**
     * @return bool
     */
    public function isTaxExempt()
    {
        if ($this->customer_tax_exempt) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->store_id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->customer_id;
    }

    /**
     * Validate if loginname is the same as email.
     *
     * @return bool
     */
    public function isLoginnameAsEmail()
    {
        if ($this->loginname == $this->email) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstname;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastname;
    }

    /**
     * @return string
     */
    public function getLoginName()
    {
        return $this->loginname;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * @return string
     */
    public function getFax()
    {
        return $this->fax;
    }

    /**
     * @return mixed
     */
    public function getNewsletter()
    {
        return $this->newsletter;
    }

    /**
     * @return int
     */
    public function getCustomerGroupId()
    {
        return $this->customer_group_id;
    }

    /**
     * @return int
     */
    public function getAddressId()
    {
        return $this->address_id;
    }

    /**
     * @since 1.2.7
     *
     * @param array $data_array
     * @param string $format
     * @param array $locate
     *
     * @return string
     */
    public function getFormattedAddress($data_array, $format = '', $locate = [])
    {
        $data_array = (array)$data_array;
        // Set default format
        if ($format == '') {
            $format = '{firstname} {lastname}'
                ."\n".'{company}'
                ."\n".'{address_1}'
                ."\n".'{address_2}'
                ."\n".'{city} {postcode}'
                ."\n".'{zone}'
                ."\n".'{country}';
        }
        //Set default variable to be set for address based on the data
        if (count($locate) <= 0) {
            $locate = [];
            foreach ($data_array as $key => $value) {
                $locate[] = "{".$key."}";
            }
        }

        return str_replace(
            ["\r\n", "\r", "\n"],
            '<br />',
            preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"],
                '<br />',
                trim(str_replace($locate, $data_array, $format)))
        );

    }

    /**
     * Customer Transactions Section. Track account balance transactions.
     * Return customer account balance in customer currency based on debit/credit calculation
     *
     * @return float|bool
     * @throws \Exception
     */
    public function getBalance()
    {
        if (!$this->isLogged()) {
            return false;
        }

        $query = $this->db->query("SELECT sum(credit) - sum(debit) AS balance
                                    FROM ".$this->db->table_name("customer_transactions")."
                                    WHERE customer_id = '".(int)$this->getId()."'");
        $balance = (float)$query->row['balance'];

        return $balance;
    }

    /**
     * Record debit transaction
     *
     * @param array $tr_details - amount, order_id, transaction_type, description, comments, creator
     *
     * @return bool
     * @throws \Exception
     */
    public function debitTransaction($tr_details)
    {
        return $this->recordTransaction('debit', $tr_details);
    }

    /**
     * Record credit transaction
     *
     * @param array $tr_details - amount, order_id, transaction_type, description, comments, creator
     *
     * @return bool
     * @throws \Exception
     */
    public function creditTransaction($tr_details)
    {
        return $this->recordTransaction('credit', $tr_details);
    }

    /**
     * Record cart content
     */
    public function saveCustomerCart()
    {
        $customer_id = $this->customer_id;
        $store_id = (int)$config->get('config_store_id');
        if (!$customer_id) {
            $customer_id = $this->unauth_customer['customer_id'];
        }
        if (!$customer_id) {
            return null;
        }

        //before write get cart-info from db to non-override cart for other stores of multistore
        $result = $this->db->query("SELECT cart
                                    FROM ".$this->db->table_name("customers")."
                                    WHERE customer_id = '".(int)$customer_id."' AND status = '1'");
        $cart = unserialize($result->row['cart']);
        //check is format of cart old or new
        $new = $this->isNewCartFormat($cart);

        if (!$new) {
            $cart = []; //clean cart from old format
        }
        $cart['store_'.$store_id] = $this->session->data['cart'];
        $this->db->query("UPDATE ".$this->db->table_name("customers")."
                          SET
                                cart = '".$this->db->escape(serialize($cart))."',
                                ip = '".$this->db->escape($this->request->getRemoteIP())."'
                          WHERE customer_id = '".(int)$customer_id."'");
    }

    /**
     * Confirm that current customer is valid
     *
     * @return bool
     * @throws \Exception
     */
    public function isValidEnabledCustomer()
    {
        $customer_id = $this->customer_id;
        if (!$customer_id) {
            $customer_id = $this->unauth_customer['customer_id'];
        }
        if (!$customer_id) {
            return false;
        }

        $sql = "SELECT cart
                FROM ".$this->db->table_name("customers")."
                WHERE customer_id = '".(int)$customer_id."' AND status = '1'";
        $result = $this->db->query($sql);
        if ($result->num_rows) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get cart content
     *
     * @return array()
     * @throws \Exception
     */
    public function getCustomerCart()
    {
        $store_id = (int)$config->get('config_store_id');
        $customer_id = $this->customer_id;
        if (!$customer_id) {
            $customer_id = $this->unauth_customer['customer_id'];
        }
        if (!$customer_id) {
            return [];
        }

        $cart = [];
        $sql = "SELECT cart
                FROM ".$this->db->table_name("customers")."
                WHERE customer_id = '".(int)$customer_id."' AND status = '1'";

        $result = $this->db->query($sql);
        if ($result->num_rows) {
            //load customer saved cart
            if (($result->row['cart']) && (is_string($result->row['cart']))) {
                $cart = unserialize($result->row['cart']);
                //check is format of cart old or new
                $new = $this->isNewCartFormat($cart);
                if (isset($cart['store_'.$store_id])) {
                    $cart = $cart['store_'.$store_id];
                } elseif ($new) {
                    $cart = [];
                }
                //clean products
                if ($cart) {
                    $cart_products = [];
                    foreach ($cart as $key => $val) {
                        $k = explode(':', $key);
                        $cart_products[] = (int)$k[0]; // <-product_id
                    }
                    $sql = "SELECT product_id
                            FROM ".$this->db->table_name('products_to_stores')." pts
                            WHERE store_id = '".$store_id."' AND product_id IN (".implode(', ', $cart_products).")";

                    $result = $this->db->query($sql);
                    $products = [];
                    foreach ($result->rows as $row) {
                        $products[] = $row['product_id'];
                    }

                    $diff = array_diff($cart_products, $products);
                    foreach ($diff as $p) {
                        unset($cart[$p]);
                    }
                }
            }
        }

        return $cart;
    }

    /**
     * Merge cart from session and cart from database content
     *
     * @param array - cart from database
     *
     * @void
     */
    public function mergeCustomerCart($cart)
    {
        $store_id = (int)$config->get('config_store_id');
        $cart = !is_array($cart) ? [] : $cart;
        //check is format of cart old or new
        $new = $this->isNewCartFormat($cart);

        if ($new) {
            $cart = $cart['store_'.$store_id];
        }
        // for case when data format is new but cart for store does not yet created
        $cart = !is_array($cart)
            ? []
            : $cart;

        if ($cart && !is_array($this->session->data['cart'])) {
            $this->session->data['cart'] = [];
        }
        foreach ($cart as $key => $value) {
            if (!array_key_exists($key, $this->session->data['cart'])) {
                $this->session->data['cart'][$key] = $value;
            }
        }

    }

    /**
     * Clear cart from database content
     *
     * @return bool
     * @throws \Exception
     */
    public function clearCustomerCart()
    {

        $cart = [];
        $customer_id = $this->customer_id;
        if (!$customer_id) {
            $customer_id = $this->unauth_customer['customer_id'];
        }
        if (!$customer_id) {
            return false;
        }
        $this->db->query("UPDATE ".$this->db->table_name("customers")."
                        SET
                            cart = '".$this->db->escape(serialize($cart))."'
                        WHERE customer_id = '".(int)$customer_id."'");

        return true;
    }

    /**
     * Recognize cart data format. New format is cart-per-store
     *
     * @param array $cart_data
     *
     * @return bool
     */
    protected function isNewCartFormat($cart_data = [])
    {
        if (empty($cart_data)) {
            return false;
        }
        $keys = array_keys($cart_data);
        if (is_array($keys) && !empty($keys)) {
            foreach ($keys as $k) {
                if (is_int(strpos($k, 'store_'))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Add item to wishlist
     *
     * @param int $product_id
     *
     * @return null
     * @throws \Exception
     */
    public function addToWishList($product_id)
    {
        if (!H::has_value($product_id) || !is_numeric($product_id)) {
            return null;
        }
        $whishlist = $this->getWishList();
        $whishlist[$product_id] = time();
        $this->saveWishList($whishlist);

        return null;
    }

    /**
     * Remove item from wish list
     *
     * @param int $product_id
     *
     * @return null
     * @throws \Exception
     */
    public function removeFromWishList($product_id)
    {
        if (!H::has_value($product_id) || !is_numeric($product_id)) {
            return null;
        }
        $whishlist = $this->getWishList();
        unset($whishlist[$product_id]);
        $this->saveWishList($whishlist);

        return null;
    }

    /**
     * Record wish list content
     *
     * @param array $whishlist
     *
     * @return null
     * @throws \Exception
     */
    public function saveWishList($whishlist = [])
    {
        $customer_id = $this->customer_id;
        if (!$customer_id) {
            $customer_id = $this->unauth_customer['customer_id'];
        }
        if (!$customer_id) {
            return false;
        }
        $this->db->query("UPDATE ".$this->db->table_name("customers")."
                            SET
                                wishlist = '".$this->db->escape(serialize($whishlist))."',
                                ip = '".$this->db->escape($this->request->getRemoteIP())."'
                            WHERE customer_id = '".(int)$customer_id."'");

        return true;
    }

    /**
     * Get cart content
     *
     * @return array()
     * @throws \Exception
     */
    public function getWishList()
    {
        $customer_id = $this->customer_id;
        if (!$customer_id) {
            $customer_id = $this->unauth_customer['customer_id'];
        }
        if (!$customer_id) {
            return [];
        }
        $customer_data = $this->db->query("SELECT wishlist
                                            FROM ".$this->db->table_name("customers")."
                                            WHERE customer_id = '".(int)$customer_id."' AND status = '1'");
        if ($customer_data->num_rows) {
            //load customer saved cart
            if (($customer_data->row['wishlist']) && (is_string($customer_data->row['wishlist']))) {
                return unserialize($customer_data->row['wishlist']);
            }
        }

        return [];
    }

    /**
     * @param string $type
     * @param array $tr_details - amount, order_id, transaction_type, description, comments, creator
     *
     * @return bool
     * @throws \Exception
     */
    protected function recordTransaction($type, $tr_details)
    {

        if (!$this->isLogged()) {
            return false;
        }
        if (!H::has_value($tr_details['transaction_type'])
            || !H::has_value($tr_details['created_by'])
        ) {
            return false;
        }

        if ($type == 'debit') {
            $amount = 'debit = '.(float)$tr_details['amount'];
        } else {
            if ($type == 'credit') {
                $amount = 'credit = '.(float)$tr_details['amount'];
            } else {
                return false;
            }
        }

        //check if this is not a duplicate transaction
        $sql = "SELECT customer_transaction_id
                     FROM ".$this->db->table_name("customer_transactions")." c
                     WHERE customer_id = '".(int)$this->getId()."'
                        AND order_id = '".(int)$tr_details['order_id']."'
                        AND transaction_type = '".$this->db->escape($tr_details['transaction_type'])."'
                        AND '".$amount."'
                ";
        $trnData = $this->db->query($sql);
        if ($trnData->num_rows) {
            return true;
        }

        $this->db->query("INSERT INTO ".$this->db->table_name("customer_transactions")."
                        SET customer_id = '".(int)$this->getId()."',
                            order_id = '".(int)$tr_details['order_id']."',
                            transaction_type = '".$this->db->escape($tr_details['transaction_type'])."',
                            description = '".$this->db->escape($tr_details['description'])."',
                            COMMENT = '".$this->db->escape($tr_details['comment'])."',
                            ".$amount.",
                            section = '".((int)$tr_details['section'] ? (int)$tr_details['section'] : 0)."',
                            created_by = '".(int)$tr_details['created_by']."',
                            date_added = NOW()");
        $transaction_id = $this->db->getLastId();

        //call event
        H::event(
            'abc\core\lib\customer@transaction',
            [new ABaseEvent((int)$this->getId(), $transaction_id)]);

        if ($this->db->getLastId()) {
            return true;
        }

        return false;
    }

    public static function createCustomer($data, $subscribe_only = false)
    {
        /**
         * @var AConfig $config
         */
        $config = Registry::config();
        /**
         * @var ADB $db
         */
        $db = Registry::db();
        if (!$config || !$db) {
            throw new AException('AConfig or ADB not found!');
        }

        if (!(int)$data['customer_group_id']) {
            $data['customer_group_id'] = (int)$config->get('config_customer_group_id');
        }
        if (!isset($data['status'])) {
            // if need to activate via email  - disable status
            if ($config->get('config_customer_email_activation')) {
                $data['status'] = 0;
            } else {
                $data['status'] = 1;
            }
        }
        if (isset($data['approved'])) {
            $data['approved'] = (int)$data['approved'];
        } elseif (!$config->get('config_customer_approval')) {
            $data['approved'] = 1;
        }

        // delete subscription accounts for given email
        Customer::where($db->raw('LOWER(email)'), '=', mb_strtolower($data['email']))
                ->where('customer_group_id', '=', Customer::getSubscribersGroupId())
                ->forceDelete();

        $orm = $db->getORM();
        try {
            $orm::beginTransaction();

            $customer = new Customer($data);
            $customer->save();
            $customer_id = $customer->customer_id;
            if(!$subscribe_only) {
                $address = new Address(
                    [
                        'customer_id' => $customer_id,
                        'firstname'   => $data['firstname'],
                        'lastname'    => $data['lastname'],
                        'company'     => $data['company'],
                        'address_1'   => $data['address_1'],
                        'address_2'   => $data['address_2'],
                        'city'        => $data['city'],
                        'postcode'    => $data['postcode'],
                        'country_id'  => $data['country_id'],
                        'zone_id'     => $data['zone_id'],
                    ]
                );
                $address->save();
                //set address as default
                $customer->update(['address_id' => $address->address_id]);
            }

            if (!$data['approved']) {
                $language = new ALanguage(Registry::getInstance());
                $language->load($language->language_details['directory']);
                $language->load('account/create');

                if ($data['subscriber']) {
                    //notify administrator of pending subscriber approval
                    $msg_text = sprintf($language->get('text_pending_subscriber_approval'),
                        $data['firstname'].' '.$data['lastname'], $customer_id);
                } else {
                    //notify administrator of pending customer approval
                    $msg_text = sprintf($language->get('text_pending_customer_approval'),
                        $data['firstname'].' '.$data['lastname'], $customer_id);
                }
                $msg = new AMessage();
                $msg->saveNotice($language->get('text_new_customer'), $msg_text);
            }

            $orm::commit();
        }catch(\Exception $e){
            $orm::rollback();
            throw new AException(__CLASS__.': '.$e->getMessage(), 0, __FILE__);
        }

        //notify admin
        $language = new ALanguage( Registry::getInstance() );
        $language->load( $language->language_details['directory'] );
        $language->load( 'common/im' );
        if ( $data['subscriber'] ) {
            $lang_key = 'im_new_subscriber_text_to_admin';
        } else {
            $lang_key = 'im_new_customer_text_to_admin';
        }
        $message_arr = [
            1 => [
                'message' => sprintf( $language->get( $lang_key ), $customer_id ),
            ],
        ];
        Registry::im()->send( 'new_customer', $message_arr );

        //call event
        H::event(
            'abc\core\lib\customer@create',
            [new ABaseEvent($customer_id, __FUNCTION__, $data)]);

        return $customer_id;
    }

    public function editCustomer( $data )
    {
        if ( ! $data ) {
            return false;
        }
        /**
         * @var AIM $im
         */
        $im = Registry::im();
        $customer_id = (int)$this->customer_id;

        $language = new ALanguage( Registry::getInstance() );
        $language->load( $language->language_details['directory'] );
        $language->load( 'common/im' );

        if ( ! empty( $data['loginname'] ) ) {
            $message_arr = [
                0 => ['message' => sprintf( $language->get( 'im_customer_account_update_login_to_customer' ), $data['loginname'] )],
            ];
            $im->send( 'customer_account_update', $message_arr );
        }
        //get existing data and compare
        /**
         * @var $customer Customer
         */
        $customer = Customer::find($customer_id);
        foreach ( $customer->toArray() as $rec => $val ) {
            if ( $rec == 'email' && $val != $data['email'] ) {
                $message_arr = [
                    0 => ['message' => sprintf( $language->get( 'im_customer_account_update_email_to_customer' ), $data['email'] )],
                ];
                $im->send( 'customer_account_update', $message_arr );
            }
        }

        //trim and remove double whitespaces
        foreach ( ['firstname', 'lastname'] as $f ) {
            $data[$f] = str_replace( '  ', ' ', trim( $data[$f] ) );
        }

        $customer->update($data);

        //call event
        H::event(
            'abc\core\lib\customer@update',
            [new ABaseEvent($customer_id, __FUNCTION__, $data)]);

        return true;
    }

    /**
     * @param $loginname
     * @param $password
     *
     * @return bool
     * @throws AException
     * @throws \ReflectionException
     */
    public function editPassword( $loginname, $password )
    {
        /**
         * @var Customer $customer
         */
        $customer = Customer::where('loginname', '=', $loginname)->get();
        if(!$customer){
            return false;
        }

        $customer->update(['password' => $password]);
        $language = new ALanguage( Registry::getInstance() );
        $language->load( $language->language_details['directory'] );
        $language->load( 'common/im' );
        $message_arr = [
            0 => ['message' => $language->get( 'im_customer_account_update_password_to_customer' )],
        ];
        Registry::im()->send( 'customer_account_update', $message_arr );
        return true;
    }

    /**
     * @return Customer
     */
    public function model()
    {
        return Customer::find((int)$this->customer_id);
    }



    public function getCustomerNotificationSettings()
    {
        if(!$this->customer_id){
            return [];
        }

        //get only active IM drivers
        $im_protocols = Registry::im()->getProtocols();
        $im_settings = [];
        $cn = CustomerNotification::where('customer', '=', $this->customer_id)->get()->toArray();

        foreach ( $cn as $row ) {
            if ( ! in_array( $row['protocol'], $im_protocols ) ) {
                continue;
            }
            $im_settings[$row['sendpoint']][$row['protocol']] = (int)$row['status'];
        }

        return $im_settings;
    }
    
    
    public static function validateRegistrationData( $data )
    {
        static::$errors = [];
        $config = Registry::config();
        $request = Registry::request();
        $session = Registry::session();
        $language = Registry::language();
        //If captcha enabled, validate
        if ( $config->get( 'config_account_create_captcha' ) ) {
            if ( $config->get( 'config_recaptcha_secret_key' ) ) {
                require_once ABC::env( 'DIR_VENDOR' ).'/google_recaptcha/autoload.php';
                $recaptcha = new ReCaptcha( $config->get( 'config_recaptcha_secret_key' ) );
                $resp = $recaptcha->verify( $data['g-recaptcha-response'],
                    $request->getRemoteIP() );
                if ( ! $resp->isSuccess() && $resp->getErrorCodes() ) {
                    static::$errors['captcha'] = $language->get( 'error_captcha' );
                }
            } else {
                if ( ! isset( $session->data['captcha'] ) || ( $session->data['captcha'] != $data['captcha'] ) ) {
                    static::$errors['captcha'] = $language->get( 'error_captcha' );
                }
            }
        }

        if ( $config->get( 'prevent_email_as_login' ) ) {
            //validate only if email login is not allowed
            $login_name_pattern = '/^[\w._-]+$/i';
            if ( mb_strlen( $data['loginname'] ) < 5
                || mb_strlen( $data['loginname'] ) > 64
                || ! preg_match( $login_name_pattern, $data['loginname'] )
            ) {
                static::$errors['loginname'] = $language->get( 'error_loginname' );
                //validate uniqueness of login name
            } else {
                if (Customer::getCustomers(['filter' => ['search_operator' => 'equal','loginname'=> $data['loginname'] ]],'total_only')) {
                    static::$errors['loginname'] = $language->get( 'error_loginname_notunique' );
                }
            }
        }

        if ( ( mb_strlen( $data['firstname'] ) < 1 ) || ( mb_strlen( $data['firstname'] ) > 32 ) ) {
            static::$errors['firstname'] = $language->get( 'error_firstname' );
        }

        if ( ( mb_strlen( $data['lastname'] ) < 1 ) || ( mb_strlen( $data['lastname'] ) > 32 ) ) {
            static::$errors['lastname'] = $language->get( 'error_lastname' );
        }

        if ( ( mb_strlen( $data['email'] ) > 96 ) || ( ! preg_match( ABC::env( 'EMAIL_REGEX_PATTERN' ), $data['email'] ) ) ) {
            static::$errors['email'] = $language->get( 'error_email' );
        }

        if ( $this->getTotalCustomersByEmail( $data['email'] ) ) {
            static::$errors['warning'] = $language->get( 'error_exists' );
        }

        if ( mb_strlen( $data['telephone'] ) > 32 ) {
            static::$errors['telephone'] = $language->get( 'error_telephone' );
        }

        if ( ( mb_strlen( $data['address_1'] ) < 3 ) || ( mb_strlen( $data['address_1'] ) > 128 ) ) {
            static::$errors['address_1'] = $language->get( 'error_address_1' );
        }

        if ( ( mb_strlen( $data['city'] ) < 3 ) || ( mb_strlen( $data['city'] ) > 128 ) ) {
            static::$errors['city'] = $language->get( 'error_city' );
        }
        if ( ( mb_strlen( $data['postcode'] ) < 3 ) || ( mb_strlen( $data['postcode'] ) > 128 ) ) {
            static::$errors['postcode'] = $language->get( 'error_postcode' );
        }

        if ( $data['country_id'] == 'FALSE' ) {
            static::$errors['country'] = $language->get( 'error_country' );
        }

        if ( $data['zone_id'] == 'FALSE' ) {
            static::$errors['zone'] = $language->get( 'error_zone' );
        }

        //check password length considering html entities (special case for characters " > < & )
        $pass_len = mb_strlen( htmlspecialchars_decode( $data['password'] ) );
        if ( $pass_len < 4 || $pass_len > 20 ) {
            static::$errors['password'] = $language->get( 'error_password' );
        }

        if ( $data['confirm'] != $data['password'] ) {
            static::$errors['confirm'] = $language->get( 'error_confirm' );
        }

        if ( $config->get( 'config_account_id' ) ) {
            $this->load->model( 'catalog/content' );

            $content_info = $this->model_catalog_content->getContent( $config->get( 'config_account_id' ) );

            if ( $content_info ) {
                if ( ! isset( $data['agree'] ) ) {
                    static::$errors['warning'] = sprintf( $language->get( 'error_agree' ), $content_info['title'] );
                }
            }
        }

        //validate IM URIs
        //get only active IM drivers
        $im_drivers = $this->im->getIMDriverObjects();
        if ( $im_drivers ) {
            foreach ( $im_drivers as $protocol => $driver_obj ) {
                /**
                 * @var \abc\core\lib\AMailIM $driver_obj
                 */
                if ( ! is_object( $driver_obj ) || $protocol == 'email' ) {
                    continue;
                }
                $result = $driver_obj->validateURI( $data[$protocol] );
                if ( ! $result ) {
                    static::$errors[$protocol] = implode( '<br>', $driver_obj->errors );
                }

            }
        }

        $this->extensions->hk_ValidateData( $this );

        return static::$errors;
    }
    

}