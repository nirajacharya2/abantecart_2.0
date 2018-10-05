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
use abc\core\engine\AAttribute;
use abc\core\engine\ALanguage;
use abc\core\engine\HtmlElementFactory;
use abc\core\engine\Registry;
use H;

/**
 * Class ACart
 *
 * @property \abc\models\storefront\ModelCatalogProduct    $model_catalog_product
 * @property ATax                                          $tax
 * @property ADB                                           $db
 * @property AWeight                                       $weight
 * @property AConfig                                       $config
 * @property \abc\core\engine\ALoader                      $load
 * @property \abc\models\storefront\ModelCheckoutExtension $model_checkout_extension
 * @property ADownload                                     $download
 */
class ACart  extends ALibBase
{
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var ASession
     */
    protected $session;
    /**
     * @var ALanguage
     */
    protected $language;
    protected $cart_data = [];
    /**
     * @var array
     */
    protected $cust_data = [];
    /**
     * @var float
     */
    protected $sub_total;
    /**
     * @var array
     */
    protected $taxes = [];
    /**
     * @var float
     */
    protected $total_value;
    /**
     * @var float
     */
    protected $final_total;
    /**
     * @var array
     */
    protected $total_data;
    /**
     * @var ACustomer
     */
    protected $customer;
    /**
     * @var AAttribute
     */
    protected $attribute;
    /**
     * @var APromotion
     */
    protected $promotion;

    public $conciergeMode = false;

    /**
     * @param $registry Registry
     * @param $c_data   array  - ref (Customer data array passed by ref)
     *
     */
    public function __construct($registry, &$c_data = null)
    {
        $this->registry = $registry;
        $this->attribute = ABC::getObjectByAlias('AAttribute',['product_option']);
        $this->customer = $registry->get('customer') ?: $c_data['customer'];
        $this->session = $registry->get('session');
        $this->language = $registry->get('language') ?: $c_data['language'];

        //if nothing is passed (default) use session array. Customer session, can function on storefront only
        if ($c_data == null) {
            $this->cust_data =& $this->session->data;
        } else {
            $this->cust_data =& $c_data;
        }
        //can load promotion if customer_group_id is provided
        $this->promotion = ABC::getObjectByAlias('APromotion',[$this->cust_data['customer_group_id']]);

        if (!isset($this->cust_data['cart']) || !is_array($this->cust_data['cart'])) {
            $this->cust_data['cart'] = [];
        }
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

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value)
    {
        $this->registry->set($key, $value);
    }

    /**
     * @return array
     */
    public function getCartData()
    {
        return $this->cust_data['cart'];
    }

    /**
     * Returns all products in the cart
     * To force recalculate pass argument as TRUE
     *
     * @param bool $recalculate
     *
     * @return array
     * @throws AException
     */
    public function getProducts($recalculate = false)
    {

        //check if cart data was built before
        if (count($this->cart_data) && !$recalculate) {
            return $this->cart_data;
        }

        $product_data = [];
        //process data in the cart session per each product in the cart

        foreach ($this->cust_data['cart'] as $key => $data) {
            if ($key == 'virtual') {
                continue;
            }
            $array = explode(':', $key);
            $product_id = $array[0];
            $quantity = $data['qty'];

            if (isset($data['options'])) {
                $options = (array)$data['options'];
            } else {
                $options = [];
            }

            $custom_price = ($this->conciergeMode && isset($data['custom_price']))
                            ? $data['custom_price']
                            : null;

            $product_result = $this->buildProductDetails($product_id, $quantity, $options, $custom_price);
            if (count($product_result)) {
                $product_data[$key] = $product_result;
                $product_data[$key]['key'] = $key;

                //apply min and max for quantity once we have product details.
                if ($quantity < $product_result['minimum']) {
                    $this->language->load('checkout/cart', 'silent');
                    $this->cust_data['error'] = $this->language->get('error_quantity_minimum');
                    $this->update($key, $product_result['minimum']);
                }
                if ($product_result['maximum'] > 0) {
                    $this->language->load('checkout/cart', 'silent');
                    if ($quantity > $product_result['maximum']) {
                        $this->cust_data['error'] = $this->language->get('error_quantity_maximum');
                        $this->update($key, $product_result['maximum']);
                    }
                }
            } else {
                $this->remove($key);
            }
        }
        //save complete cart details in the class for future access
        $this->cart_data = $product_data;
        return $this->cart_data;
    }

    /**
     * @param string $key
     * @param bool $recalculate
     *
     * @return array
     * @throws AException
     */
    public function getProduct($key, $recalculate = false)
    {
        if ($recalculate) {
            $this->getProducts(true);
        }
        return H::has_value($this->cust_data['cart'][$key]) ? $this->cust_data['cart'][$key] : [];
    }

    /**
     * Collect product information for cart based on user selections
     * Function can be used to get totals and other product information
     * (based on user selection) as it is before getting into cart or after
     *
     * @param int $product_id
     * @param int $quantity
     * @param array $options
     *
     * @param null $custom_price
     *
     * @return array
     * @throws AException
     */
    public function buildProductDetails($product_id, $quantity = 0, $options = [], $custom_price=null)
    {

        if (!H::has_value($product_id) || !is_numeric($product_id) || $quantity == 0) {
            return [];
        }

        $options = !is_array($options) ? [] : $options;

        $stock = true;
        /**
         * @var  \abc\models\storefront\ModelCatalogProduct $sf_product_mdl
         */
        $sf_product_mdl = $this->load->model('catalog/product', 'storefront');
        //remove restrictions of model in concierge mode
        if ($this->conciergeMode) {
            $sf_product_mdl->filter = [];
            if ($custom_price !== null) {
                $custom_price = (float)$custom_price;
            }
        } else {
            $custom_price = null;
        }

        $elements_with_options = HtmlElementFactory::getElementsWithOptions();

        $product_query = $sf_product_mdl->getProductDataForCart($product_id);
        if (count($product_query) <= 0 || $product_query['call_to_order']) {
            return [];
        }

        $stock_checkout = $product_query['stock_checkout'];
        if (!H::has_value($stock_checkout)) {
            $stock_checkout = $this->config->get('config_stock_checkout');
        }

        $option_price = 0;
        $option_data = [];
        $groups = [];
        $op_stock_trackable = 0;
        //Process each option and value
        foreach ($options as $product_option_id => $product_option_value_id) {
            //skip empty values
            if ($product_option_value_id == '' || (is_array($product_option_value_id) && !$product_option_value_id)) {
                continue;
            }

            $option_query = $sf_product_mdl->getProductOption($product_id, $product_option_id);
            $element_type = $option_query['element_type'];
            $option_value_query = [];
            $option_value_queries = [];

            if (!in_array($element_type, $elements_with_options)) {
                //This is single value element, get all values and expect only one
                $option_value_query = $sf_product_mdl->getProductOptionValues($product_id, $product_option_id);
                $option_value_query = $option_value_query[0];
                //Set value from input
                $option_value_query['name'] = $this->db->escape($options[$product_option_id]);
            } else {
                //is multivalue option type
                if (is_array($product_option_value_id)) {
                    foreach ($product_option_value_id as $val_id) {
                        $option_value_queries[$val_id] = $sf_product_mdl->getProductOptionValue($product_id, $val_id);
                    }
                } else {
                    $option_value_query = $sf_product_mdl->getProductOptionValue(
                                                                            $product_id,
                                                                            (int)$product_option_value_id
                    );
                }
            }

            if ($option_value_query) {
                //if group option load price from parent value
                if ($option_value_query['group_id'] && !in_array($option_value_query['group_id'], $groups)) {
                    $group_value_query = $sf_product_mdl->getProductOptionValue(
                                                                            $product_id,
                                                                            $option_value_query['group_id']
                    );
                    $option_value_query['prefix'] = $group_value_query['prefix'];
                    $option_value_query['price'] = $group_value_query['price'];
                    $groups[] = $option_value_query['group_id'];
                }
                $option_data[] = [
                    'product_option_value_id' => $option_value_query['product_option_value_id'],
                    'product_option_id'       => $product_option_id,
                    'name'                    => $option_query['name'],
                    'element_type'            => $element_type,
                    'settings'                => $option_query['settings'],
                    'value'                   => $option_value_query['name'],
                    'prefix'                  => $option_value_query['prefix'],
                    'price'                   => (
                    $custom_price !== null
                        ? $custom_price
                        : $option_value_query['price']
                    ),
                    'sku'                     => $option_value_query['sku'],
                    'inventory_quantity'      => (
                    $option_value_query['subtract']
                        ? (int)$option_value_query['quantity']
                        : 1000000
                    ),
                    'weight'                  => $option_value_query['weight'],
                    'weight_type'             => $option_value_query['weight_type'],
                ];

                //check if need to track stock and we have it
                if ($option_value_query['subtract']
                    && $option_value_query['quantity'] < $quantity
                    && !$stock_checkout
                ) {
                    $stock = false;
                }
                $op_stock_trackable += $option_value_query['subtract'];
                unset($option_value_query);
            } else {
                if ($option_value_queries) {
                    foreach ($option_value_queries as $item) {
                        $option_data[] = [
                            'product_option_value_id' => $item['product_option_value_id'],
                            'name'                    => $option_query['name'],
                            'value'                   => $item['name'],
                            'prefix'                  => $item['prefix'],
                            'price'                   => (
                            $custom_price !== null
                                ? $custom_price
                                : $item['price']
                            ),
                            'sku'                     => $item['sku'],
                            'inventory_quantity'      => ($item['subtract'] ? (int)$item['quantity'] : 1000000),
                            'weight'                  => $item['weight'],
                            'weight_type'             => $item['weight_type'],
                        ];
                        //check if need to track stock and we have it
                        if ($item['subtract'] && $item['quantity'] < $quantity) {
                            $stock = false;
                        }
                        $op_stock_trackable += $option_value_query['subtract'];
                    }
                    unset($option_value_queries);
                }
            }
        } // end of options build

        if($custom_price === null){
            //needed for promotion
            $discount_quantity = 0; // this is used to calculate total QTY of 1 product in the cart

            // check is product is in cart and calculate quantity to define item price with product discount
            foreach ($this->cust_data['cart'] as $k => $v) {
                $array2 = explode(':', $k);
                if ($array2[0] == $product_id) {
                    $discount_quantity += $v['qty'];
                }
            }
            if (!$discount_quantity) {
                $discount_quantity = $quantity;
            }

            //Apply group and quantity discount first and if non, reply product discount
            $price = $this->promotion->getProductQtyDiscount($product_id, $discount_quantity);
            if (!$price) {
                $price = $this->promotion->getProductSpecial($product_id);
            }
            //Still no special price, use regular price
            if (!$price) {
                $price = $product_query['price'];
            }

            //Need to round price after discounts and specials
            //round base currency price to 2 decimal place
            $decimal_place = 2;
            $price = round($price, $decimal_place);

            foreach ($option_data as $item) {
                if ($item['prefix'] == '%') {
                    $option_price += $price * $item['price'] / 100;
                } else {
                    $option_price += $item['price'];
                }
            }
            //round option price to currency decimal_place setting (most common 2, but still...)
            $option_price = round($option_price, $decimal_place);
            $final_price = $price + $option_price;
        }else{
            $final_price = $custom_price;
        }

        // product downloads
        $download_data = $this->download->getProductOrderDownloads($product_id);

        //check if we need to check main product stock. Do only if no stock trackable options selected
        if (!$op_stock_trackable
            && $product_query['subtract']
            && $product_query['quantity'] < $quantity
            && !$product_query['stock_checkout']) {
            $stock = false;
        }

        $result = [
            'product_id'         => $product_query['product_id'],
            'name'               => $product_query['name'],
            'model'              => $product_query['model'],
            'shipping'           => $product_query['shipping'],
            'option'             => $option_data,
            'download'           => $download_data,
            'quantity'           => $quantity,
            'inventory_quantity' => ($product_query['subtract'] ? (int)$product_query['quantity'] : 1000000),
            'minimum'            => $product_query['minimum'],
            'maximum'            => $product_query['maximum'],
            'stock'              => $stock,
            'price'              => $final_price,
            'total'              => $final_price * $quantity,
            'tax_class_id'       => $product_query['tax_class_id'],
            'weight'             => $product_query['weight'],
            'weight_class'       => $product_query['weight_class'],
            'length'             => $product_query['length'],
            'width'              => $product_query['width'],
            'height'             => $product_query['height'],
            'length_class'       => $product_query['length_class'],
            'ship_individually'  => $product_query['ship_individually'],
            'shipping_price'     => $product_query['shipping_price'],
            'free_shipping'      => $product_query['free_shipping'],
            'sku'                => $product_query['sku'],
        ];
        return $result;
    }

    /**
     * @param int $product_id
     * @param int $qty
     * @param array $options
     *
     * @param null $custom_price
     *
     * @throws AException
     */
    public function add($product_id, $qty = 1, $options = [], $custom_price = null)
    {
        $product_id = (int)$product_id;
        if (!$options) {
            $key = $product_id;
        } else {
            $key = $product_id.':'.md5(serialize($options));
        }

        if ((int)$qty && ((int)$qty > 0)) {
            if (!isset($this->cust_data['cart'][$key])) {
                $this->cust_data['cart'][$key]['qty'] = (int)$qty;
            } else {
                $this->cust_data['cart'][$key]['qty'] += (int)$qty;
            }
            //TODO Add validation for correct options for the product and add error return or more stable behaviour
            $this->cust_data['cart'][$key]['options'] = $options;
            //allow custom price in conciergeMode
            if($this->conciergeMode && $custom_price !== null) {
                $this->cust_data['cart'][$key]['custom_price'] = (float)$custom_price;
            }
        }

        //if logged in customer, save cart content
        if ($this->customer && ($this->customer->isLogged() || $this->customer->isUnauthCustomer())) {
            $this->customer->saveCustomerCart();
        }

        //reload data for the cart
        $this->getProducts(true);
    }

    /**
     * @param string $key
     * @param array  $data
     *
     * @return bool
     */
    public function addVirtual($key, $data)
    {

        if (!H::has_value($data)) {
            return false;
        }

        if (!isset($this->cust_data['cart']['virtual']) || !is_array($this->cust_data['cart']['virtual'])) {
            $this->cust_data['cart']['virtual'] = [];
        }

        $this->cust_data['cart']['virtual'][$key] = $data;
        return true;
    }

    /**
     * @return array
     */
    public function getVirtualProducts()
    {
        return (array)$this->cust_data['cart']['virtual'];
    }

    /**
     * @param $key
     */
    public function removeVirtual($key)
    {
        if (isset($this->cust_data['cart']['virtual'][$key])) {
            unset($this->cust_data['cart']['virtual'][$key]);
            if (!H::has_value($this->cust_data['cart']['virtual'])) {
                unset($this->cust_data['cart']['virtual']);
            }
        }
    }

    /**
     * @param string $key
     * @param int $qty
     *
     * @throws AException
     */
    public function update($key, $qty)
    {
        if ((int)$qty && ((int)$qty > 0)) {
            $this->cust_data['cart'][$key]['qty'] = (int)$qty;
        } else {
            $this->remove($key);
        }

        //save if logged in customer
        if ($this->customer && ($this->customer->isLogged() || $this->customer->isUnauthCustomer())) {
            $this->customer->saveCustomerCart();
        }

        //reload data for the cart
        $this->getProducts(true);
    }

    /**
     * @param $key
     */
    public function remove($key)
    {
        if (isset($this->cust_data['cart'][$key])) {
            unset($this->cust_data['cart'][$key]);
            // remove balance credit from session when any products removed from cart
            unset($this->cust_data['used_balance']);

            //if logged in customer, save cart content
            if ($this->customer && ($this->customer->isLogged() || $this->customer->isUnauthCustomer())) {
                $this->customer->saveCustomerCart();
            }
        }
    }

    public function clear()
    {
        $this->cust_data['cart'] = [];
    }

    /**
     * Accumulative weight for all or requested products
     *
     * @param array $product_ids
     *
     * @return int
     * @throws AException
     */
    public function getWeight($product_ids = [])
    {
        $weight = 0;
        $products = $this->getProducts();
        foreach ($products as $product) {
            if (count($product_ids) > 0 && !in_array((string)$product['product_id'], $product_ids)) {
                continue;
            }

            if ($product['shipping']) {
                $product_weight = $product['weight'];
                // if product_option has weight value
                if ($product['option']) {
                    $hard = false;
                    foreach ($product['option'] as $option) {
                        if ($option['weight'] == 0) {
                            continue;
                        } // if weight not set - skip
                        if ($option['weight_type'] != '%') {
                            //If weight was set by option hard and other option sets another weight hard - ignore it
                            //skip negative weight. Negative allowed only for % based weight
                            if ($hard || $option['weight'] < 0) {
                                continue;
                            }

                            $hard = true;
                            $product_weight = $this->weight->convert($option['weight'], $option['weight_type'],
                                $product['weight_class']);
                        } else {
                            //We need product base weight for % calculation
                            $temp = ($option['weight'] * $product['weight'] / 100) + $product['weight'];
                            $product_weight = $this->weight->convert($temp, $option['weight_type'],
                                $this->config->get('config_weight_class'));
                        }
                    }
                }
                $weight += $this->weight->convert($product_weight * $product['quantity'], $product['weight_class'],
                    $this->config->get('config_weight_class'));
            }
        }
        return $weight;
    }

    /**
     * Products with no special settings for shipping
     *
     * @return array
     * @throws AException
     */
    public function basicShippingProducts()
    {
        $basic_ship_products = [];
        $products = $this->getProducts();
        foreach ($products as $product) {
            if ($product['shipping'] && !$product['ship_individually'] && !$product['free_shipping']
                && $product['shipping_price'] == 0) {
                $basic_ship_products[] = $product;
            }
        }
        return $basic_ship_products;
    }

    /**
     * Products with special settings for shipping
     *
     * @return array
     * @throws AException
     */
    public function specialShippingProducts()
    {
        $special_ship_products = [];
        $products = $this->getProducts();
        foreach ($products as $product) {
            if ($product['shipping']
                && ($product['ship_individually'] || $product['free_shipping']
                    || $product['shipping_price'] > 0)) {
                $special_ship_products[] = $product;
            }
        }
        return $special_ship_products;
    }

    /**
     * Check if all products are free shipping
     *
     * @return bool
     * @throws AException
     */
    public function areAllFreeShipping()
    {
        $all_free_shipping = false;
        $products = $this->getProducts();
        foreach ($products as $product) {
            if (!$product['shipping'] || ($product['shipping'] && $product['free_shipping'])) {
                $all_free_shipping = true;
            } else {
                return false;
            }
        }
        return $all_free_shipping;
    }

    /**
     * Set mim quantity on whole cart
     *
     * @void
     */
    public function setMinQty()
    {
        $products = $this->getProducts();
        foreach ($products as $product) {
            if ($product['quantity'] < $product['minimum']) {
                $this->cust_data['cart'][$product['key']]['qty'] = $product['minimum'];
            }
        }
    }

    /**
     * Set max quantity on whole cart
     *
     * @void
     */
    public function setMaxQty()
    {
        // If set 0 there is no minimum
        $products = $this->getProducts();
        foreach ($products as $product) {
            if ($product['maximum'] > 0) {
                if ($product['quantity'] > $product['maximum']) {
                    $this->cust_data['error'] = $this->language->get('error_quantity_maximum');
                    $this->cust_data['cart'][$product['key']]['qty'] = $product['maximum'];
                }
            }
        }
    }

    /**
     * Get Sub Total amount for current built order without any tax or any promotion
     * To force recalculate pass argument as TRUE
     *
     * @param bool $recalculate
     *
     * @return float
     * @throws AException
     */
    public function getSubTotal($recalculate = false)
    {
        //check if value already set
        if (H::has_value($this->sub_total) && !$recalculate) {
            return $this->sub_total;
        }

        $this->sub_total = 0.0;
        $products = $this->getProducts();
        foreach ($products as $product) {
            $this->sub_total += $product['total'];
        }
        return $this->sub_total;
    }

    /**
     * candidate to be deprecated
     *
     * @return array
     * @throws AException
     */
    public function getTaxes()
    {
        return $this->getAppliedTaxes();
    }

    /**
     * Returns all applied taxes on products in the cart
     * To force recalculate pass argument as TRUE
     *
     * @param bool $recalculate
     *
     * @return array
     * @throws AException
     */
    public function getAppliedTaxes($recalculate = false)
    {
        //check if value already set
        if (H::has_value($this->taxes) && !$recalculate) {
            return $this->taxes;
        }

        //round base currency price calculation to 2 decimal place
        $decimal_place = 2;

        $this->taxes = [];
        // taxes for products
        $products = $this->getProducts();
        foreach ($products as $product) {
            if ($product['tax_class_id']) {
                //save total for each tax class to build clear tax display later
                if (!isset($this->taxes[$product['tax_class_id']])) {
                    $this->taxes[$product['tax_class_id']]['total'] = $product['total'];
                    $this->taxes[$product['tax_class_id']]['tax'] =
                        $this->tax->calcTotalTaxAmount($product['total'], $product['tax_class_id']);
                } else {
                    $this->taxes[$product['tax_class_id']]['total'] += $product['total'];
                    $this->taxes[$product['tax_class_id']]['tax'] +=
                        $this->tax->calcTotalTaxAmount($product['total'], $product['tax_class_id']);
                }
                $this->taxes[$product['tax_class_id']]['tax'] =
                    round($this->taxes[$product['tax_class_id']]['tax'], $decimal_place);
            }
        }
        //tax for shipping
        if ($this->cust_data['shipping_method']['tax_class_id']) {
            $tax_id = $this->cust_data['shipping_method']['tax_class_id'];
            $cost = $this->cust_data['shipping_method']['cost'];
            if (!isset($this->taxes[$tax_id])) {
                $this->taxes[$tax_id]['tax'] = $this->tax->calcTotalTaxAmount($cost, $tax_id);
            } else {
                $this->taxes[$tax_id]['tax'] += $this->tax->calcTotalTaxAmount($cost, $tax_id);
            }
            //round
            $this->taxes[$tax_id]['tax'] = round($this->taxes[$tax_id]['tax'], $decimal_place);
        }
        return $this->taxes;
    }

    /**
     * Get Total amount for current built order with applicable taxes ( order value )
     * Can be used for total value in shipping insurance or to calculate total savings.
     * To force recalculate pass argument as TRUE
     *
     * @param bool $recalculate
     *
     * @return float
     * @throws AException
     */
    public function getTotal($recalculate = false)
    {
        //check if value already set
        if (H::has_value($this->total_value) && !$recalculate) {
            return $this->total_value;
        }
        $this->total_value = 0.0;
        $products = $this->getProducts();
        foreach ($products as $product) {
            $this->total_value +=
                $product['total'] + $this->tax->calcTotalTaxAmount( $product['total'], $product['tax_class_id'] );
        }
        return $this->total_value;
    }

    /**
     * Get Total amount for current built cart with all totals, taxes and applied promotions
     * To force recalculate pass argument as TRUE
     *
     * @param bool $recalculate
     *
     * @return float
     * @throws AException
     */
    public function getFinalTotal($recalculate = false)
    {
        //check if value already set
        if (H::has_value($this->total_data) && H::has_value($this->final_total)
            && !$recalculate) {
            return $this->final_total;
        }
        $this->final_total = 0.0;
        $this->total_data = [];

        $total_data = [];
        $calc_order = [];
        $total = 0.0;

        //if cart is empty, nothing to do.
        if (!$this->getProducts()) {
            return $total;
        }

        $taxes = $this->getAppliedTaxes($recalculate);
        //force storefront load (if called from admin)
        /**
         * @var $sf_checkout_mdl \abc\models\storefront\ModelCheckoutExtension
         */
        $sf_checkout_mdl = $this->load->model('checkout/extension', 'storefront');
        $total_extns = $sf_checkout_mdl->getExtensions('total');
        foreach ($total_extns as $value) {
            $calc_order[$value['key']] = (int)$this->config->get($value['key'].'_calculation_order');
        }
        array_multisort($calc_order, SORT_ASC, $total_extns);

        foreach ($total_extns as $extn) {
            $sf_total_mdl = $this->load->model('total/'.$extn['key'], 'storefront');
            /**
             * parameters are references!!!
             *
             * @var \abc\models\storefront\ModelTotalTotal $sf_total_mdl
             */
            $sf_total_mdl->getTotal($total_data, $total, $taxes, $this->cust_data);
            $sf_total_mdl = null;
        }

        $this->total_data = $total_data;
        $this->final_total = $total;
        //if balance become less or 0 reapply partial
        if ($this->session->data['used_balance'] && $this->final_total) {
            $this->session->data['used_balance_full'] = false;
        }
        return $this->final_total;
    }

    /**
     * Get Total Data for current built cart with all totals, taxes and applied promotions
     * To force recalculate pass argument as TRUE
     *
     * @param bool $recalculate
     *
     * @return mixed
     * @throws AException
     */
    public function getFinalTotalData($recalculate = false)
    {
        //check if value already set
        if (H::has_value($this->total_data) && H::has_value($this->final_total)
            && !$recalculate) {
            return $this->total_data;
        }
        $this->final_total = $this->getFinalTotal($recalculate);
        return $this->total_data;
    }

    /**
     * Function to build total display based on enabled extensions/settings for total section
     * Amounts are automatically converted to currency selected in getFinalTotal().
     * Internal currency price is present in [value] fields
     * To force recalculate pass argument as TRUE
     *
     * @param bool $recalculate
     *
     * @return array
     * @throws AException
     */
    public function buildTotalDisplay($recalculate = false)
    {

        $taxes = $this->getAppliedTaxes($recalculate);
        $total = $this->getFinalTotal($recalculate);
        $total_data = $this->getFinalTotalData();
        //sort data for view
        $sort_order = [];
        foreach ($total_data as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }
        array_multisort($sort_order, SORT_ASC, $total_data);
        //return result in array
        return [
            'total' => $total,
            'total_data' => $total_data,
            'taxes' => $taxes
        ];
    }

    /**
     * @param bool $recalc
     *
     * @return float
     * @throws AException
     */
    public function getTotalAmount($recalc = false)
    {
        return $this->buildTotalDisplay($recalc)['total'];
    }

    /**
     * Check if order/cart total has minimum amount setting met if it was set
     *
     * @return bool
     * @throws AException
     */
    public function hasMinRequirement()
    {
        $cf_total_min = $this->config->get('total_order_minimum');
        if ($cf_total_min && $cf_total_min > $this->getSubTotal()) {
            return false;
        }
        return true;
    }

    /**
     * Check if order/cart total has maximum amount setting met if it was set
     *
     * @return bool
     * @throws AException
     */
    public function hasMaxRequirement()
    {
        $cf_total_max = $this->config->get('total_order_maximum');
        if ($cf_total_max && $cf_total_max < $this->getSubTotal()) {
            return false;
        }
        return true;
    }

    /**
     * Return count of products in the cart including quantity per product
     *
     * @return int
     */
    public function countProducts()
    {
        $qty = 0;
        foreach ($this->cust_data['cart'] as $product) {
            $qty += $product['qty'];
        }
        return $qty;
    }

    /**
     * Return 0/[count] for products in the cart (quantity is not counted)
     *
     * @return int
     */
    public function hasProducts()
    {
        return count($this->cust_data['cart']);
    }

    /**
     * Return TRUE if all products have stock
     *
     * @return bool
     * @throws AException
     */
    public function hasStock()
    {
        $stock = true;
        $products = $this->getProducts();
        foreach ($products as $product) {
            if (!$product['stock']) {
                $stock = false;
            }
        }
        return $stock;
    }

    /**
     * Return FALSE if all products do NOT require shipping
     *
     * @return bool
     * @throws AException
     */
    public function hasShipping()
    {
        $shipping = false;
        $products = $this->getProducts();
        foreach ($products as $product) {
            if ($product['shipping']) {
                $shipping = true;
                break;
            }
        }
        return $shipping;
    }

    /**
     * Return FALSE if all products do NOT have download type
     *
     * @return bool
     * @throws AException
     */
    public function hasDownload()
    {
        $download = false;
        $products = $this->getProducts();
        foreach ($products as $product) {
            if ($product['download']) {
                $download = true;
                break;
            }
        }
        return $download;
    }
}