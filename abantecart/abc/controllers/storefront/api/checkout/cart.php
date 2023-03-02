<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2023 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\controllers\storefront;

use abc\core\engine\AControllerAPI;
use abc\core\engine\AResource;
use abc\core\lib\AWeight;

/**
 * Class ControllerApiCheckoutCart
 *
 * @property AWeight $weight
 */
class ControllerApiCheckoutCart extends AControllerAPI
{
    public $error = [];

    public function post()
    {
        $request = $this->rest->getRequestParams();
        if (!$this->customer->isLoggedWithToken($request['token'])) {
            $this->rest->setResponseData(['error' => 'Not logged in or Login attempt failed!']);
            $this->rest->sendResponse(401);
            return;
        }

        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadModel('catalog/product');

        //check if we add single or multiple products to cart
        if (isset($request['quantity']) || is_array($request['products'])) {
            if (isset($request['product_id']) && !is_array($request['quantity'])) {
                //add single product
                $this->addToCart($request);
            } else {
                if (isset($request['product_id']) && is_array($request['quantity'])) {
                    //update quantities for products
                    foreach ($request['quantity'] as $key => $value) {
                        $this->cart->update($key, $value);
                    }
                } else {
                    if (is_array($request['products'])) {
                        //add bulk products
                        foreach ($request['products'] as $product) {
                            $this->addToCart($product);
                        }
                    }
                }
            }

            unset(
                $this->session->data['shipping_methods'],
                $this->session->data['shipping_method'],
                $this->session->data['payment_methods'],
                $this->session->data['payment_method']
            );
        }

        //request to remove
        if (isset($request['remove']) && is_array($request['remove'])) {
            foreach (array_keys($request['remove']) as $key) {
                if ($key) {
                    $this->cart->remove($key);
                }
            }
        } else {
            if ($request['remove_all']) {
                $this->cart->clear();
            }
        }

        $this->prepareCartData();

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($this->data);
        $this->rest->sendResponse(200);
    }

    public function delete()
    {
        $request = $this->rest->getRequestParams();
        $count = 0;
        if (isset($request['remove']) && is_array($request['remove'])) {
            foreach (array_keys($request['remove']) as $key) {
                if ($key) {
                    $this->cart->remove($key);
                    $count++;
                }
            }
        } else {
            if ($request['remove_all']) {
                $this->cart->clear();
            }
        }
        $this->rest->setResponseData(['success' => "$count removed"]);
        $this->rest->sendResponse(200);
    }

    public function put()
    {
        return $this->post();
    }

    protected function addToCart($product)
    {
        $options = $product['option'] ?? [];
        if ($errors = $this->model_catalog_product->validateProductOptions($product['product_id'], $options)) {
            $this->rest->setResponseData(['error' => implode(' ', $errors)]);
            $this->rest->sendResponse(206);
        }
        $this->cart->add($product['product_id'], $product['quantity'], $options);
    }

    protected function prepareCartData()
    {
        if ($this->cart->hasProducts()) {
            $this->loadModel('tool/image');
            $products = [];
            $cart_products = $this->cart->getProducts();

            $product_ids = [];
            foreach ($cart_products as $result) {
                $product_ids[] = (int)$result['product_id'];
            }

            $resource = new AResource('image');
            $thumbnails = $resource->getMainThumbList(
                'products',
                $product_ids,
                $this->config->get('config_image_cart_width'),
                $this->config->get('config_image_cart_height')
            );

            if (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout')) {
                $this->data['error_warning'] = $this->language->get('error_stock');
            }

            foreach ($cart_products as $result) {
                $option_data = [];
                $thumbnail = $thumbnails[$result['product_id']];
                foreach ($result['option'] as $option) {
                    $option_data[] = [
                        'name'  => html_entity_decode($option['name'], ENT_QUOTES, 'UFS-8'),
                        'value' => $option['value'],
                    ];
                    // product image by option value
                    $mSizes = [
                        'main'  =>
                            [
                                'width'  => $this->config->get('config_image_cart_width'),
                                'height' => $this->config->get('config_image_cart_height')
                            ],
                        'thumb' => [
                            'width'  => $this->config->get('config_image_cart_width'),
                            'height' => $this->config->get('config_image_cart_height')
                        ],
                    ];

                    $main_image = $resource->getResourceAllObjects(
                        'product_option_value',
                        $option['product_option_value_id'],
                        $mSizes,
                        1,
                        false
                    );

                    if (!empty($main_image)) {
                        $thumbnail['origin'] = $main_image['origin'];
                        $thumbnail['title'] = $main_image['title'];
                        $thumbnail['description'] = $main_image['description'];
                        $thumbnail['thumb_html'] = $main_image['thumb_html'];
                        $thumbnail['thumb_url'] = $main_image['thumb_url'];
                    }
                }

                $price_with_tax = $this->tax->calculate(
                    $result['price'],
                    $result['tax_class_id'],
                    $this->config->get('config_tax')
                );

                $products[] = [
                    'key'      => $result['key'],
                    'name'     => html_entity_decode($result['name'], ENT_QUOTES, 'UFS-8'),
                    'model'    => $result['model'],
                    'thumb'    => $thumbnail['thumb_url'],
                    'option'   => $option_data,
                    'quantity' => $result['quantity'],
                    'stock'    => $result['stock'],
                    'price'    => $this->currency->format($price_with_tax),
                    'total'    => $this->currency->format_total($price_with_tax, $result['quantity']),
                ];
            }
            $this->data['products'] = $products;
            if ($this->config->get('config_cart_weight')) {
                $this->data['weight'] = $this->weight->format(
                    $this->cart->getWeight(),
                    $this->config->get('config_weight_class')
                );
            } else {
                $this->data['weight'] = false;
            }
            $display_totals = $this->cart->buildTotalDisplay();
            $this->data['totals'] = $display_totals['total_data'];
        } else {
            //empty cart content
            $this->data['products'] = [];
            $this->data['totals'] = 0;
        }
    }
}