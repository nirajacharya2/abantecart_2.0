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
use abc\core\engine\ALoader;
use abc\core\engine\ExtensionsApi;
use abc\core\engine\Registry;
use abc\models\customer\Address;
use abc\models\customer\Customer;
use abc\models\customer\CustomerGroup;
use abc\models\customer\CustomerNotification;
use abc\models\customer\CustomerTransaction;
use abc\models\storefront\ModelCatalogContent;
use abc\models\storefront\ModelToolOnlineNow;
use abc\modules\events\ABaseEvent;
use Exception;
use H;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Psr\SimpleCache\InvalidArgumentException;
use ReCaptcha\ReCaptcha;
use ReflectionException;

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
     * @var AbcCache
     */
    protected $cache;
    /**
     * @var ADB
     */
    protected $db;
    /**
     * @var ALoader
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
     * @var ExtensionsApi
     */
    protected $extensions;

    /**
     * @var array (unauthenticated customer details)
     */
    protected $unauth_customer = [];

    /**
     * @param Registry $registry
     *
     * @param int $customer_id
     *
     * @throws AException
     * @throws InvalidArgumentException
     * @throws ReflectionException
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
            $query = Customer::where('customer_id', '=',$customer_id);
            if (!ABC::env('IS_ADMIN')) {
                $query->where('status', '=', 1);
            }
            $customer = $query->first();
            $customer_data = [];
            if($customer){
                $customer_data = $customer->toArray();
                if($customer_data['customer_group_id']){
                    $cg = CustomerGroup::find($customer_data['customer_group_id']);
                    $customer_data['customer_group_name'] = $cg->name;
                }
            }

            if ($customer_data) {
                $this->customerInit($customer_data);
            } else {
                $this->logout();
            }
        } elseif (isset($this->request->cookie['customer'])) {
            //we have unauthenticated customer
            /**
             * @var AEncryption $encryption
             */
            $encryption = ABC::getObjectByAlias('AEncryption', [$config->get('encryption_key')]);
            $this->unauth_customer = unserialize($encryption->decrypt((string)$this->request->cookie['customer']));
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
        if(!$loginname || !$password){
            return false;
        }
        $config = Registry::config();
        $filter = [
            'search_operator' => 'equal',
            'loginname'       => $loginname,
            'password'        => $password,
            'status'          => 1,

        ];
        if ($config->get('config_customer_approval')) {
            $filter['approved'] = 1;
        }

        /** @var Collection $customer_data */
        $customer_data = Customer::search(['filter' => $filter])->first();
        if ($customer_data) {
            $this->customerInit($customer_data->toArray());

            $this->session->data['customer_id'] = $this->customer_id;
            //load customer saved cart and merge with session cart before login
            $cart = $this->getCustomerCart();
            $this->mergeCustomerCart($cart);

            //save merged cart
            $this->saveCustomerCart();

            //set cookie for unauthenticated user (expire in 1 year)
            /**
             * @var AEncryption $encryption
             */
            $encryption = ABC::getObjectByAlias('AEncryption', [$config->get('encryption_key')]);
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

        $this->customer_group_name = $data['customer_group_name'];

        $this->customer_tax_exempt = $data['tax_exempt'];
        //save this sign to use in ATax lib
        $this->session->data['customer_tax_exempt'] = $data['tax_exempt'];

        $this->address_id = (int)$data['address_id'];
        //if customer have no default address - take first
        if($this->customer_id && !$this->address_id){
            $address = Address::where('customer_id','=', $this->customer_id)->first();
            if($address){
                $this->address_id = $address->address_id;
            }
        }
    }

    /**
     * @param $customer_id
     *
     * @return bool
     * @throws AException
     */
    public function setLastLogin($customer_id)
    {
        $customer_id = (int)$customer_id;
        if (!$customer_id) {
            return false;
        }

        //insert new record
        $customer = Customer::find($customer_id);
        $customer->update(['last_login' => date('Y-m-d H:i:s')]);

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
            || (isset($token)
                && isset($this->session->data['token'])
                && $token != $this->session->data['token'])
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
     * @throws Exception
     */
    public function getBalance()
    {
        if (!$this->isLogged()) {
            return false;
        }

        return CustomerTransaction::getBalance($this->customer_id);
    }

    /**
     * Record debit transaction
     *
     * @param array $data - amount, order_id, transaction_type, description, comments, creator
     *
     * @return bool
     * @throws Exception|ValidationException
     * @throws InvalidArgumentException
     */
    public function debitTransaction($data)
    {
        $data['debit'] = $data['amount'];
        unset($data['amount']);
        return $this->recordTransaction($data);
    }

    /**
     * Record credit transaction
     *
     * @param array $data - amount, order_id, transaction_type, description, comments, creator
     *
     * @return bool
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function creditTransaction($data)
    {
        $data['credit'] = $data['amount'];
        unset($data['amount']);
        return $this->recordTransaction($data);
    }

    /**
     * Record cart content
     */
    public function saveCustomerCart()
    {
        $config = Registry::config();
        $customer_id = $this->customer_id;
        $store_id = (int)$config->get('config_store_id');
        if (!$customer_id) {
            $customer_id = $this->unauth_customer['customer_id'];
        }
        if (!$customer_id) {
            return false;
        }

        $cart = [];

        //before write get cart-info from db to non-override cart for other stores of multistore
        $customer = $this->model();
        if($customer && $customer->status == 1){
            $cart = $customer->cart;
        }

        $cart['store_'.$store_id] = $this->session->data['cart'];
        $customer->update(
                [
                    'cart' => $cart,
                    'ip' => $this->request->getRemoteIP()
                ]
        );

        return true;
    }

    /**
     * Confirm that current customer is valid
     *
     * @return bool
     * @throws Exception
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

        $customer = $this->model();
        if($customer && $customer->status == 1){
            return true;
        }
        return false;
    }

    /**
     * Get cart content
     *
     * @return array()
     * @throws Exception
     */
    public function getCustomerCart()
    {
        $config = Registry::config();
        $store_id = (int)$config->get('config_store_id');
        $customer_id = $this->customer_id;
        if (!$customer_id) {
            $customer_id = $this->unauth_customer['customer_id'];
        }
        if (!$customer_id) {
            return [];
        }

        $cart = [];
        $customer = $this->model();
        if($customer && $customer->status == 1){
            $cart = $customer->cart;
        }

        //clean products
        if ($cart) {
            $cart_products = [];
            foreach ($cart as $key => $val) {
                $k = explode(':', $key);
                $cart_products[] = (int)$k[0]; // <-product_id
            }

            $product_list = $this->db->table('products_to_stores')
                        ->select('product_id')
                        ->where('store_id', '=',$store_id)
                        ->whereIn('product_id', $cart_products)
                        ->get();
            $products = [];
            if($product_list->count()){
                $products = array_column($product_list->toArray(),'product_id');
            }

            $diff = array_diff($cart_products, $products);
            foreach ($diff as $p) {
                unset($cart[$p]);
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
        $config = Registry::config();
        $store_id = (int)$config->get('config_store_id');
        $cart = !is_array($cart) ? [] : $cart;
        $cart = $cart['store_'.$store_id];

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
     * @throws Exception
     */
    public function clearCustomerCart()
    {

        $customer_id = $this->customer_id;
        if (!$customer_id) {
            $customer_id = $this->unauth_customer['customer_id'];
        }
        if (!$customer_id) {
            return false;
        }
        $customer = $this->model();
        $customer->update( ['cart' => [] ]);
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
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
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
        $this->model()->update( ['wishlist' => $whishlist, 'ip' =>  $this->request->getRemoteIP() ] );
        return true;
    }

    /**
     * Get cart content
     *
     * @return array()
     * @throws Exception
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

        $customer = $this->model();
        if($customer && $customer->status == 1){
            return (array)$customer->wishlist;
        }

        return [];
    }

    /**
     * @param array $data - amount, order_id, transaction_type, description, comments, creator
     *
     * @return bool
     * @throws Exception|ValidationException
     * @throws InvalidArgumentException
     */
    protected function recordTransaction($data)
    {

        if (!$this->isLogged()
            || ( (float)$data['credit']===0.0 && (float)$data['debit']===0.0 )
        ) {
            return false;
        }

        $data['customer_id']  = $this->customer_id;
        $data['section']  = (int)$data['section'] ?? 0;
        $transaction = new CustomerTransaction();
        try {
            $transaction->validate($data);
            //use firstOrNew to prevent duplicates
            $transaction = CustomerTransaction::updateOrCreate($data);
            $transaction_id = $transaction->customer_transaction_id;
        } catch (ValidationException $e) {
            $errors = [];
            H::SimplifyValidationErrors($transaction->errors()['validation'], $errors);
            Registry::log()->write(var_export($errors, true));
            return false;
        }

        return $transaction_id;
    }

    /**
     * @param $data
     * @param bool $subscribe_only
     *
     * @return Customer
     * @throws AException
     * @throws ValidationException
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
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
        Customer::where('email', '=', mb_strtolower($data['email']))
                ->where('customer_group_id', '=', Customer::getSubscribersGroupId())
                ->forceDelete();

        try {
            $db->beginTransaction();

            $customer = new Customer($data);
            $customer->save();

            $customer_id = $customer->customer_id;
            if(!$subscribe_only) {
                $address = new Address();
                $newData = [];
                foreach ($address->getFillable() as $key){
                    if(isset($data[$key])){
                        $newData[$key] = $data[$key];
                    }
                }
                if($newData) {
                    $newData['customer_id'] = $customer_id;
                    $address->fill($newData);
                    $address->save();
                    //set address as default
                    $customer->update(['address_id' => $address->address_id]);
                }
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

            $db->commit();
        } catch (ValidationException $e) {
            $db->rollback();
            throw $e;
        } catch (Exception $e) {
            $db->rollback();
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

        return $customer;
    }

    /**
     * @param $data
     *
     * @return bool
     * @throws AException
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
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

        //get existing data and compare
        /**
         * @var $customer Customer
         */
        $customer = Customer::find($customer_id);
        foreach ( $customer->toArray() as $rec => $val ) {
            if (!empty($data['loginname']) && $rec == 'loginname' && $val != $data['loginname']) {
                $message_arr = [
                    0 => ['message' => sprintf( $language->get( 'im_customer_account_update_login_to_customer' ), $data['loginname'] )],
                ];
                $im->send( 'customer_account_update', $message_arr );
            }
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
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    public function editPassword( $loginname, $password )
    {
        /**
         * @var Customer $customer
         */
        $customer = Customer::where('loginname', '=', $loginname)->first();
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

    /**
     * @return array
     */
    public function getCustomerNotificationSettings()
    {
        if(!$this->customer_id){
            return [];
        }

        //get only active IM drivers
        $im_protocols = Registry::im()->getProtocols();
        $im_settings = [];
        $cn = CustomerNotification::where('customer_id', '=', $this->customer_id)->get()->toArray();

        foreach ( $cn as $row ) {
            if ( ! in_array( $row['protocol'], $im_protocols ) ) {
                continue;
            }
            $im_settings[$row['sendpoint']][$row['protocol']] = (int)$row['status'];
        }

        return $im_settings;
    }

    /**
     * @param $data
     *
     * @return array
     * @throws AException
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    public static function validateRegistrationData( $data )
    {
        static::$errors = [];
        $config = Registry::config();
        $request = Registry::request();
        $session = Registry::session();
        $language = Registry::language();

        $isLogged = Registry::customer() ? Registry::customer()->isLogged() : false;

        //load storefront language if not loaded
        if(!$language->load('account/create')) {
            $language = new ALanguage(Registry::getInstance(), Registry::language()->getLanguageCode(), 0);
        }
        $language->load('account/create');

        $customer_id = $data['customer_id'] ? (int)$data['customer_id'] : null;
        if($data['password']) {
            $data['password'] = htmlspecialchars_decode($data['password']);
        }

        //If captcha enabled, validate
        if ( $config->get( 'config_account_create_captcha' ) && !$isLogged) {
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

        $customer = $customer_id ? Customer::find($customer_id) : new Customer();

        //validate customer model data
        try{
            $customer->validate($data);
        }catch(ValidationException $e){
            H::SimplifyValidationErrors($customer->errors()['validation'], static::$errors);
        }

        //validate address model data
        $address = new Address();
        try{
            $address->validate($data);
        }catch(ValidationException $e){
            H::SimplifyValidationErrors($address->errors()['validation'], static::$errors);
        }

        if (!$isLogged && $config->get('config_account_id')) {
            Registry::load()->model('catalog/content');
            /**
             * @var ModelCatalogContent $model_catalog_content
             TODO: replace it in the future */
            $content_info = Registry::model_catalog_content()->getContent($config->get('config_account_id'));

            if ($content_info) {
                if (!isset($data['agree'])) {
                    static::$errors['warning'] = sprintf($language->get('error_agree'), $content_info['title']);
                }
            }
        }

        //validate IM URIs
        //get only active IM drivers
        $im_drivers = Registry::im()->getIMDriverObjects();
        if ($im_drivers) {
            foreach ($im_drivers as $protocol => $driver_obj) {
                /**
                 * @var AMailIM $driver_obj
                 */
                if (!is_object($driver_obj) || $protocol == 'email') {
                    continue;
                }
                $result = $driver_obj->validateURI($data[$protocol]);
                if (!$result) {
                    static::$errors[$protocol] = implode('<br>', $driver_obj->errors);
                }
            }
        }

        Registry::extensions()->hk_ValidateData( Registry::customer(), [ __METHOD__ ] );
        return static::$errors;
    }

    /**
     * @param $data
     *
     * @return array
     * @throws AException
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    public static function validateSubscribeData( $data )
    {
        static::$errors = [];
        $config = Registry::config();
        $request = Registry::request();
        $session = Registry::session();
        $language = Registry::language();
        //load storefront language if not loaded
        if(!$language->load('account/create')) {
            $language = new ALanguage(Registry::getInstance(), Registry::language()->getLanguageCode(), 0);
        }
        $language->load('account/create');
        if($data['password']) {
            $data['password'] = htmlspecialchars_decode($data['password']);
        }

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

        $customer = $data['customer_id'] ? Customer::find($data['customer_id']) : new Customer();
        //validate customer model data
        try{
            $customer->validate($data);
        }catch(ValidationException $e){
            H::SimplifyValidationErrors($customer->errors()['validation'], static::$errors);
        }

        //validate IM URIs
        //get only active IM drivers
        $im_drivers = Registry::im()->getIMDriverObjects();
        if ($im_drivers) {
            foreach ($im_drivers as $protocol => $driver_obj) {
                /**
                 * @var AMailIM $driver_obj
                 */
                if (!is_object($driver_obj) || $protocol == 'email') {
                    continue;
                }
                $result = $driver_obj->validateURI($data[$protocol]);
                if (!$result) {
                    static::$errors[$protocol] = implode('<br>', $driver_obj->errors);
                }
            }
        }

        Registry::extensions()->hk_ValidateData( Registry::customer(), [ __METHOD__ ] );
        return static::$errors;
    }
}