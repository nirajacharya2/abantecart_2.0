<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2023 Belavier Commerce LLC
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

namespace abc\controllers\storefront;

use abc\core\ABC;
use abc\core\engine\AResource;
use abc\core\engine\ASecureControllerAPI;
use abc\models\order\Order;
use abc\models\order\OrderOption;
use abc\models\order\OrderProduct;
use abc\models\order\OrderStatusDescription;
use abc\models\order\OrderTotal;
use H;
use Illuminate\Support\Collection;


class ControllerApiOrderDetails extends ASecureControllerAPI
{

    public function get()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $request = $this->rest->getRequestParams();

        if (!H::has_value($request['order_id'])) {
            $this->rest->setResponseData([
                'error_code' => 0,
                'error_title' => 'Bad request',
                'error_text' => 'Order ID is missing'
            ]);
            $this->rest->sendResponse(400);
            return;
        }
        $orderId = $request['order_id'];

        $orderDetails = Order::getOrderArray($request['order_id'], 'any', $this->customer->getId());
        if (!count($orderDetails)) {
            $this->rest->setResponseData([
                'error_code' => 0,
                'error_title' => 'Not found',
                'error_text' => 'Order with ID is not found'
            ]);
            $this->rest->sendResponse(404);
            return null;
        }

        $this->data = $orderDetails;

        $this->data['shipping'] = [
            'address' => [
                'firstname' => $this->data['shipping_firstname'],
                'lastname' => $this->data['shipping_lastname'],
                'company' => $this->data['shipping_company'],
                'address_1' => $this->data['shipping_address_1'],
                'address_2' => $this->data['shipping_address_2'],
                'city' => $this->data['shipping_city'],
                'postcode' => $this->data['shipping_postcode'],
                'zone' => $this->data['shipping_zone'],
                'zone_id' => $this->data['shipping_zone_id'],
                'country' => $this->data['shipping_country'],
                'country_id' => $this->data['shipping_country_id'],
                'address_format' => $this->data['shipping_address_format'],
            ],
            'method' => $this->data['shipping_method'],
            'method_key' => $this->data['shipping_method_key'],
        ];


        $this->data['payment'] = [
            'address' => [
                'firstname' => $this->data['payment_firstname'],
                'lastname' => $this->data['payment_lastname'],
                'company' => $this->data['payment_company'],
                'address_1' => $this->data['payment_address_1'],
                'address_2' => $this->data['payment_address_2'],
                'city' => $this->data['payment_city'],
                'postcode' => $this->data['payment_postcode'],
                'zone' => $this->data['payment_zone'],
                'zone_id' => $this->data['payment_zone_id'],
                'country' => $this->data['payment_country'],
                'country_id' => $this->data['payment_country_id'],
                'address_format' => $this->data['payment_address_format'],
            ],
            'method' => $this->data['payment_method'],
            'method_key' => $this->data['payment_method_key'],
        ];

        foreach ($this->data as $key => $value) {
            if (str_starts_with($key, 'shipping_')) {
                unset($this->data[$key]);
            }
            if (str_starts_with($key, 'payment_')) {
                unset($this->data[$key]);
            }
        }

        $this->data['status_id'] = $this->data['order_status_id'];
        unset($this->data['order_status_id']);

        $this->data['status'] = $this->data['order_status_name'];
        unset($this->data['order_status_name']);

        $products = [];
        /** @var OrderProduct|Collection $orderProducts */
        $orderProducts = OrderProduct::where('order_id', '=', $orderId)->get();
        $orderStatuses = OrderStatusDescription::where('language_id', '=', $this->language->getLanguageID())
            ->useCache('order_status')
            ->get()
            ->toArray();
        $orderStatuses = array_column($orderStatuses, 'name', 'order_status_id');
        $productIds = $orderProducts->pluck('product_id')->toArray();

        //get thumbnails by one pass
        $resource = new AResource('image');
        $thumbnails = $resource->getMainThumbList(
            'products',
            $productIds,
            $this->config->get('config_image_cart_width'),
            $this->config->get('config_image_cart_width'),
            false
        );

        foreach ($orderProducts as $product) {
            $options = OrderOption::where(
                [
                    'order_id' => $orderId,
                    'order_product_id' => $product->order_product_id
                ]
            )->get();
            $thumbnail = $thumbnails[$product['product_id']];
            if ($thumbnail['thumb_url']) {
                $thumbnail = $thumbnail['thumb_url'];
            } else {
                $thumbnail = null;
            }
            $optionData = [];

            foreach ($options as $option) {
                if ($option->element_type == 'H') {
                    continue;
                } //hide hidden options

                $value = $option->value;
                $title = '';
                // hide binary value for checkbox
                if ($option->element_type == 'C' && in_array($value, [0, 1])) {
                    $value = '';
                }
                // strip long textarea value
                if ($option->element_type == 'T') {
                    $title = strip_tags($value);
                    $title = str_replace('\r\n', "\n", $title);

                    $value = str_replace('\r\n', "\n", $value);
                    if (mb_strlen($value) > 64) {
                        $value = mb_substr($value, 0, 64) . '...';
                    }
                }

                $optionData[] = [
                    'name' => $option->name,
                    'value' => $value,
                    'title' => $title,
                ];
                // product image by option value
                $mSizes = [
                    'main' =>
                        [
                            'width' => $this->config->get('config_image_cart_width'),
                            'height' => $this->config->get('config_image_cart_height')
                        ],
                    'thumb' => [
                        'width' => $this->config->get('config_image_cart_width'),
                        'height' => $this->config->get('config_image_cart_height')
                    ],
                ];


                $main_image = $resource->getResourceAllObjects(
                    'product_option_value',
                    $option->product_option_value_id,
                    $mSizes,
                    1,
                    false
                );

                if (!empty($main_image)) {
                    $thumbnail = $main_image['thumb_url'];
                }
            }


            $products[] = [
                'id' => $product->product_id,
                'order_product_id' => $product->order_product_id,
                'order_status_id' => $product->order_status_id,
                'order_status' => $orderStatuses[$product->order_status_id],
                'thumbnail' => $thumbnail,
                'name' => html_entity_decode($product->name, ENT_QUOTES, ABC::env('APP_CHARSET')),
                'model' => $product->model,
                'options' => $optionData,
                'quantity' => $product->quantity,
                'price' => $product->price,
                'total' => $product->price * $product->quantity
            ];
        }
        $this->data['products'] = $products;
        $this->data['totals'] = OrderTotal::where('order_id', '=', $orderId)
            ->get()
            ->toArray();

        foreach ($this->data['totals'] as &$total) {
            unset($total['text']);
        }

        $this->data['comment'] = $orderDetails['comment'];

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($this->data);
        $this->rest->sendResponse(200);
    }
}