<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2018 Belavier Commerce LLC

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

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\engine\AForm;
use abc\core\engine\AResource;
use abc\core\lib\AEncryption;
use abc\models\order\Order;
use abc\models\order\OrderOption;
use abc\models\order\OrderProduct;
use abc\models\order\OrderStatusDescription;
use abc\models\order\OrderTotal;
use H;

/**
 * Class ControllerPagesAccountInvoice
 */
class ControllerPagesAccountInvoice extends AController
{
    public $data = [];
    public $error = [];

    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (isset($this->request->get['order_id'])) {
            $order_id = (int)$this->request->get['order_id'];
        } else {
            $order_id = 0;
        }
        $order_info = [];
        $order_token = '';

        $guest = false;
        /**
         * @var AEncryption $enc
         */
        $enc = ABC::getObjectByAlias('AEncryption', [$this->config->get('encryption_key')]);

        if (isset($this->request->get['ot']) && $this->config->get('config_guest_checkout')) {
            //try to decrypt order token
            $order_token = $this->request->get['ot'];
            if ($order_token) {
                list($order_id, $email) = H::parseOrderToken($order_token);
                if ($order_id && $email) {
                    $guest = true;
                    $order_info = Order::getOrderArray($order_id);
                }
            }
        }

        if ($this->request->is_POST() && $this->validate()) {

            $guest = true;
            $order_id = $this->request->post['order_id'];
            $email = $this->request->post['email'];
            $order_token = $enc->encrypt($order_id.'::'.$email);
            $order_info = Order::getOrderArray($order_id);

            //compare emails
            if ($order_info['email'] != $email) {
                unset($email);
                $order_id = '';
                $order_info = [];
            }
        }

        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->resetBreadcrumbs();
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getHomeURL(),
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ]);
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('account/account'),
                'text'      => $this->language->get('text_account'),
                'separator' => $this->language->get('text_separator'),
            ]);
        if (!$guest) {
            $this->document->addBreadcrumb(
                [
                    'href'      => $this->html->getSecureURL('account/history'),
                    'text'      => $this->language->get('text_history'),
                    'separator' => $this->language->get('text_separator'),
                ]);
        }

        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('account/invoice', '&order_id='.$order_id),
            'text'      => $this->language->get('text_invoice'),
            'separator' => $this->language->get('text_separator'),
        ]);

        $this->view->assign('error', $this->error);

        if (!$this->customer->isLogged() && !$guest) {
            $this->session->data['redirect'] = $this->html->getSecureURL('account/invoice', '&order_id='.$order_id);
            $this->getForm();
            return null;
        }

        if (!$order_id && $this->customer->isLogged()) {
            abc_redirect($this->html->getSecureURL('account/history'));
        }

        //get info for registered customers
        if (!$order_info) {
            $order_info = Order::getOrderArray($order_id);
        }

        $this->data['success'] = '';
        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        if ($order_info) {
            $this->data['order_id'] = $order_id;
            $this->data['invoice_id'] =
                        $order_info['invoice_id']
                        ? $order_info['invoice_prefix'].$order_info['invoice_id']
                        : '';
            $this->data['email'] = $order_info['email'];
            $this->data['telephone'] = $order_info['telephone'];
            $this->data['mobile_phone'] = $this->im->getCustomerURI('sms', (int)$order_info['customer_id'], $order_id);
            $this->data['fax'] = $order_info['fax'];
            $this->data['status'] = $order_info['order_status_name'];

            $shipping_data = [
                'firstname' => $order_info['shipping_firstname'],
                'lastname'  => $order_info['shipping_lastname'],
                'company'   => $order_info['shipping_company'],
                'address_1' => $order_info['shipping_address_1'],
                'address_2' => $order_info['shipping_address_2'],
                'city'      => $order_info['shipping_city'],
                'postcode'  => $order_info['shipping_postcode'],
                'zone'      => $order_info['shipping_zone'],
                'zone_code' => $order_info['shipping_zone_code'],
                'country'   => $order_info['shipping_country'],
            ];

            $this->data['shipping_address'] = $this->customer->getFormattedAddress(
                                                            $shipping_data,
                                                            $order_info['shipping_address_format']
                                                        );
            $this->data['shipping_method'] = $order_info['shipping_method'];

            $payment_data = [
                'firstname' => $order_info['payment_firstname'],
                'lastname'  => $order_info['payment_lastname'],
                'company'   => $order_info['payment_company'],
                'address_1' => $order_info['payment_address_1'],
                'address_2' => $order_info['payment_address_2'],
                'city'      => $order_info['payment_city'],
                'postcode'  => $order_info['payment_postcode'],
                'zone'      => $order_info['payment_zone'],
                'zone_code' => $order_info['payment_zone_code'],
                'country'   => $order_info['payment_country'],
            ];

            $this->data['payment_address'] = $this->customer->getFormattedAddress(
                                                            $payment_data,
                                                            $order_info['payment_address_format']
                                                        );
            $this->data['payment_method'] = $order_info['payment_method'];

            $products = [];
            $order_products = OrderProduct::where('order_id','=',$order_id)->get();
            $order_statuses = OrderStatusDescription::where('language_id','=',$this->language->getLanguageID())
                                                      ->get()
                                                      ->toArray();
            $orderStatuses = array_column($order_statuses,'name','order_status_id');
            $product_ids = $order_products->pluck('product_id')->toArray();

            //get thumbnails by one pass
            $resource = new AResource('image');
            $thumbnails = $resource->getMainThumbList(
                'products',
                $product_ids,
                $this->config->get('config_image_cart_width'),
                $this->config->get('config_image_cart_width'),
                false
            );

            foreach ($order_products as $product) {
                $options = OrderOption::where(
                    [
                        'order_id'         => $order_id,
                        'order_product_id' => $product->order_product_id
                    ]
                )->get();
                $thumbnail = $thumbnails[$product['product_id']];
                $option_data = [];
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
                            $value = mb_substr($value, 0, 64).'...';
                        }
                    }

                    $option_data[] = [
                        'name'  => $option->name,
                        'value' => $value,
                        'title' => $title,
                    ];
                    // product image by option value
                    $mSizes = [
                        'main'  =>
                            [
                                'width' => $this->config->get('config_image_cart_width'),
                                'height' => $this->config->get('config_image_cart_height')
                            ],
                        'thumb' => [
                            'width' =>  $this->config->get('config_image_cart_width'),
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
                        $thumbnail['origin'] = $main_image['origin'];
                        $thumbnail['title'] = $main_image['title'];
                        $thumbnail['description'] = $main_image['description'];
                        $thumbnail['thumb_html'] = $main_image['thumb_html'];
                        $thumbnail['thumb_url'] = $main_image['thumb_url'];
                    }
                }

                $products[ $product->order_product_id ] = [
                    'id'               => $product->product_id,
                    'order_product_id' => $product->order_product_id,
                    'order_status_id'  => $product->order_status_id,
                    'order_status'     => $orderStatuses[$product->order_status_id],
                    'thumbnail'        => $thumbnail,
                    'name'             => $product->name,
                    'model'            => $product->model,
                    'option'           => $option_data,
                    'quantity'         => $product->quantity,
                    'price'            => $this->currency->format(
                                                                $product->price,
                                                                $order_info['currency'],
                                                                $order_info['value']
                                          ),
                    'total'            => $this->currency->format_total(
                                                                $product->price,
                                                                $product->quantity,
                                                                $order_info['currency'],
                                                                $order_info['value']
                    )
                ];
            }
            $this->data['products'] = $products;
            $this->data['totals'] = OrderTotal::where('order_id', '=', $order_id)
                                              ->get()
                                              ->toArray();

            foreach ($this->data['totals'] as &$total) {
                $total['text'] = html_entity_decode($total['text'], ENT_QUOTES, ABC::env('APP_CHARSET'));
            }

            $this->data['comment'] = $order_info['comment'];
            $this->data['product_link'] = $this->html->getSecureURL('product/product', '&product_id=%ID%');

            $histories = [];
            $results = Order::getOrderHistories($order_id);

            foreach ($results as $result) {
                $histories[] = [
                    'date_added' => H::dateISO2Display(
                                                $result->date_added,
                                                $this->language->get('date_format_short').' '.$this->language->get('time_format')
                    ),
                    'status'     => $result->order_status_name,
                    'comment'    => nl2br($result->comment),
                ];
            }
            $this->data['histories'] = $histories;

            if ($guest) {
                $this->data['continue'] = $this->html->getHomeURL();
            } else {
                $this->data['continue'] = $this->html->getSecureURL('account/history');
            }

            $this->data['button_print'] = $this->html->buildElement(
                [
                    'type'  => 'button',
                    'name'  => 'print_button',
                    'text'  => $this->language->get('button_print'),
                    'icon'  => 'fa fa-print',
                    'style' => 'button',
                ]);

            //button for order cancellation
            if ($this->config->get('config_customer_cancellation_order_status_id')) {
                $order_cancel_ids = unserialize($this->config->get('config_customer_cancellation_order_status_id'));
                if (in_array($order_info['order_status_id'], $order_cancel_ids)) {
                    $this->data['button_order_cancel'] = $this->html->buildElement(
                        [
                            'type'  => 'button',
                            'name'  => 'button_order_cancellation',
                            'text'  => $this->language->get('text_order_cancellation'),
                            'icon'  => 'fa fa-ban',
                            'style' => 'button',
                        ]);
                    if (!$guest) {
                        $this->data['order_cancellation_url'] =
                            $this->html->getSecureURL('account/invoice/CancelOrder', '&order_id='.$order_id);
                    } else {
                        $this->data['order_cancellation_url'] =
                            $this->html->getSecureURL('account/invoice/CancelOrder', '&ot='.$order_token);
                    }
                }
            }

            if ($guest && $this->download->getTotalOrderDownloads($order_id, 0)) {

                $this->data['button_download'] = $this->html->buildElement(
                    [
                        'type'  => 'button',
                        'name'  => 'download_button',
                        'href'  => $this->html->getSecureURL('account/download', '&ot='.$order_token),
                        'text'  => $this->language->get('text_downloads', 'account/download'),
                        'icon'  => 'fa fa-download fa-fw',
                        'style' => 'button',
                    ]);
            }

            $this->view->setTemplate('pages/account/invoice.tpl');
        } else {
            if ($guest) {
                $this->data['continue'] = $this->html->getHomeURL();
            } else {
                $this->data['continue'] = $this->html->getSecureURL('account/account');
            }
            $this->view->setTemplate('pages/error/not_found.tpl');
        }

        $this->data['button_continue'] = $this->html->buildElement(
            [
                'type'  => 'button',
                'name'  => 'continue_button',
                'text'  => $this->language->get('button_continue'),
                'icon'  => 'fa fa-arrow-right',
                'style' => 'button',
            ]);

        $this->view->batchAssign($this->data);
        $this->processTemplate();

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function getForm()
    {
        $this->document->resetBreadcrumbs();

        $this->document->addBreadcrumb([
            'href'      => $this->html->getHomeURL(),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);

        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('account/account'),
            'text'      => $this->language->get('text_account'),
            'separator' => $this->language->get('text_separator'),
        ]);

        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('account/invoice'),
            'text'      => $this->language->get('heading_title'),
            'separator' => $this->language->get('text_separator'),
        ]);

        $this->data['back'] = $this->html->getHomeURL();

        $form = new AForm();
        $form->setForm(['form_name' => 'CheckOrderFrm']);

        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'CheckOrderFrm',
                'action' => $this->html->getSecureURL('account/invoice'),
                'csrf'   => true,
            ]);

        $order_id = (int)$this->request->post_or_get('order_id') ? (int)$this->request->post_or_get('order_id') : '';
        $this->data['form']['order_id'] = $form->getFieldHtml(
            [
                'type'     => 'input',
                'name'     => 'order_id',
                'value'    => $order_id,
                'required' => true,
            ]);

        $this->data['entry_order_id'] = $this->language->get('text_order_id');

        $this->data['form']['email'] = $form->getFieldHtml(
            [
                'type'     => 'input',
                'name'     => 'email',
                'value'    => $this->request->post['email'],
                'required' => true,
            ]);

        $this->data['entry_email'] = $this->language->get('text_email');

        $this->data['form']['back'] = $form->getFieldHtml(
            [
                'type'  => 'button',
                'name'  => 'back',
                'text'  => $this->language->get('button_back'),
                'icon'  => 'fa fa-arrow-left',
                'style' => 'button',
            ]);

        $this->data['form']['submit'] = $form->getFieldHtml(
            [
                'type' => 'submit',
                'icon' => 'fa fa-check',
                'name' => $this->language->get('button_continue'),
            ]);

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/account/order.tpl');
    }

    public function CancelOrder()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        //do a few checks
        //is order exists
        $order_id = (int)$this->request->get['order_id'];
        $customer_id = $this->customer->getId();
        $order_cancel_ids = [];

        $guest = false;
        if (isset($this->request->get['ot']) && $this->config->get('config_guest_checkout')) {
            //try to decrypt order token
            /**
             * @var AEncryption $enc
             */
            $enc = ABC::getObjectByAlias('AEncryption', [$this->config->get('encryption_key')]);
            $decrypted = $enc->decrypt((string)$this->request->get['ot']);
            list($order_id, $email) = explode('::', $decrypted);

            $order_id = (int)$order_id;
            if (!$decrypted || !$order_id || !$email) {
                if ($order_id) {
                    $this->session->data['redirect'] =
                        $this->html->getSecureURL('account/invoice', '&order_id='.$order_id);
                }
                abc_redirect($this->html->getSecureURL('account/login'));
            }
            $order_info = Order::getOrderArray($order_id);
            //compare emails
            if ($order_info['email'] != $email) {
                abc_redirect($this->html->getSecureURL('account/login'));
            }
            $guest = true;
        } else {
            $order_info = Order::getOrderArray($order_id);
        }

        if (!$order_id && !$guest) {
            abc_redirect($this->html->getSecureURL('account/invoice'));
        }

        if (!$customer_id && !$guest) {
            abc_redirect($this->html->getSecureURL('account/login'));
        }

        if (!$order_info) {
            abc_redirect($this->html->getSecureURL('account/invoice'));
        }
        //is cancellation enabled at all
        if ($this->config->get('config_customer_cancellation_order_status_id')) {
            $order_cancel_ids = unserialize($this->config->get('config_customer_cancellation_order_status_id'));
        }
        //is cancellation allowed for current order status
        if (!$order_cancel_ids || !in_array($order_info['order_status_id'], $order_cancel_ids)) {
            abc_redirect($this->html->getSecureURL('account/invoice'));
        }

        //now do change

        $new_order_status_id = $this->order_status->getStatusByTextId('canceled_by_customer');
        if ($new_order_status_id) {
            $this->checkout->getOrder()->update(
                $order_id,
                $new_order_status_id,
                $this->language->get('text_request_cancellation_from_customer'),
                true
            );

            $this->session->data['success'] = $this->language->get('text_order_cancellation_success');

            $this->messages->saveNotice(
                sprintf($this->language->get('text_order_cancellation_message_title'),
                    $order_id
                ),
                sprintf($this->language->get('text_order_cancellation_message_body'),
                    $order_info['firstname'].' '.$order_info['lastname'],
                    $order_id,
                    '#admin#rt=sale/order/details&order_id='.$order_id
                )
            );
        } else {
            //when new order status id is null by some unexpected reason - just redirect on the same page
            $this->log->write(
                'Error: Unknown cancellation order status id. '
                .'Probably integrity code problem. Check is file /core/lib/order_status.php exists.'
            );
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        if (!$guest) {
            $url = $this->html->getSecureURL('account/invoice', '&order_id='.$order_id);
        } else {
            $url = $this->html->getSecureURL('account/invoice', '&ot='.$this->request->get['ot']);
        }

        abc_redirect($url);
    }

    protected function validate()
    {

        if (!$this->csrftoken->isTokenValid()) {
            $this->error['warning'] = $this->language->get('error_unknown');
        } else {
            if (!(int)$this->request->post['order_id']) {
                $this->error['order_id'] = $this->language->get('error_order_id');
            }

            if (mb_strlen($this->request->post['email']) > 96
                || !preg_match(ABC::env('EMAIL_REGEX_PATTERN'), $this->request->post['email'])) {
                $this->error['email'] = $this->language->get('error_email');
            }

            $this->extensions->hk_ValidateData($this);
        }

        return !$this->error ? true : false;
    }
}
