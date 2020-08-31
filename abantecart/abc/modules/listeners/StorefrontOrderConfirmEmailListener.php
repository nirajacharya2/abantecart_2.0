<?php

namespace abc\modules\listeners;

use abc\core\ABC;
use abc\core\engine\ALanguage;
use abc\core\engine\AResource;
use abc\core\engine\Registry;
use abc\core\lib\AEncryption;
use abc\core\lib\AMail;
use abc\core\lib\AMessage;
use abc\core\view\AView;
use abc\models\order\OrderOption;
use abc\models\order\OrderProduct;
use abc\models\order\OrderTotal;
use abc\models\system\Setting;
use abc\modules\events\ABaseEvent;
use H;

class StorefrontOrderConfirmEmailListener
{

    public $registry, $data;
    protected $db;
    const DECIMAL = 2;

    public function __construct()
    {
        $this->registry = Registry::getInstance();
    }

    /**
     * @param ABaseEvent $event
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public function handle(ABaseEvent $event)
    {

        $order_info = $event->args[0];

        // send email to customer
        if ($order_info && $order_info['email']) {

            $order_id = $order_info['order_id'];

            $language = new ALanguage($this->registry, $order_info['language_code']);

            $language->load($order_info['language_filename']);
            $language->load('mail/account_create');
            $language->load($order_info['filename']);
            $language->load('mail/order_confirm');

            $config = Registry::config();

            /**
             * @var \stdClass $store_info
             */
            $store_info = Setting::getStoreSettings($order_info['store_id']);
            $config_mail_logo = $store_info->config_mail_logo ?: $store_info->config_logo;

            $this->data['order_number'] = $order_id;
            $this->data['order_id'] = $order_id;
            $this->data['customer_id'] = $order_info['customer_id'];
            $this->data['date_added'] = H::dateISO2Display(
                $order_info['date_added'],
                $language->get('date_format_short')
            );

            if ($config_mail_logo) {
                if (is_numeric($config_mail_logo)) {
                    $r = new AResource('image');
                    $resource_info = $r->getResource($config_mail_logo);
                    if ($resource_info) {
                        $this->data['logo_html'] = html_entity_decode(
                            $resource_info['resource_code'],
                            ENT_QUOTES,
                            ABC::env('APP_CHARSET')
                        );
                    }
                } else {
                    $this->data['logo_uri'] = 'cid:'
                        .md5(pathinfo($config_mail_logo, PATHINFO_FILENAME))
                        .'.'.pathinfo($config_mail_logo, PATHINFO_EXTENSION);
                }
            }

            $this->data['logo'] = $config_mail_logo;
            $this->data['store_name'] = $order_info['store_name'];
            $this->data['address'] = nl2br($config->get('config_address'));
            $this->data['telephone'] = $config->get('config_telephone');
            $this->data['fax'] = $config->get('config_fax');
            $this->data['email'] = $config->get('store_main_email');
            $this->data['store_url'] = $order_info['store_url'];

            //give link on order page for quest
            if ($config->get('config_guest_checkout') && $order_info['email']) {
                /**
                 * @var AEncryption $enc
                 */
                $enc = ABC::getObjectByAlias('AEncryption', [$config->get('encryption_key')]);
                $order_token = $enc->encrypt($order_id.'::'.$order_info['email']);
                $this->data['invoice'] = $order_info['store_url']
                    .'index.php?rt=account/invoice&ot='.$order_token."\n\n";
            }//give link on order for registered customers
            elseif ($order_info['customer_id']) {
                $this->data['invoice'] = $order_info['store_url']
                    .'index.php?rt=account/invoice&order_id='.$order_id;
            }

            $this->data['firstname'] = $order_info['firstname'];
            $this->data['lastname'] = $order_info['lastname'];
            $this->data['shipping_method'] = $order_info['shipping_method'];
            $this->data['payment_method'] = $order_info['payment_method'];
            $this->data['customer_email'] = $order_info['email'];
            $this->data['customer_telephone'] = $order_info['telephone'];
            $this->data['customer_mobile_phone'] =
                Registry::im()->getCustomerURI(
                    'sms',
                    (int)$order_info['customer_id'],
                    $order_id
                );
            $this->data['customer_fax'] = $order_info['fax'];
            $this->data['customer_ip'] = $order_info['ip'];
            if (strlen(trim(nl2br($order_info['comment']))) > 0) {
                $this->data['comment'] = trim(nl2br($order_info['comment']));
            }


            $this->data['shipping_data'] = [
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

            $this->data['payment_data'] = [
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


            if (!H::has_value($this->data['products'])) {
                $this->data['products'] = [];
            }
            try {
                $products = OrderProduct::where('order_id', '=', $order_id)->get();

                foreach ($products as $product) {
                    $option_data = [];

                    $query = OrderOption::where(
                        [
                            'order_options.order_id'         => $order_id,
                            'order_options.order_product_id' => $product->order_product_id,
                        ]
                    );

                    $query->leftJoin(
                        'product_option_values',
                        'order_options.product_option_value_id',
                        '=',
                        'product_option_values.product_option_value_id'
                    );
                    $query->leftJoin(
                        'product_options',
                        'product_options.product_option_id',
                        '=',
                        'product_option_values.product_option_id'
                    );
                    $query->leftJoin(
                        'products',
                        'product_options.product_id',
                        '=',
                        'products.product_id'
                    );
                    $order_options = $query->get();

                    if ($order_options) {
                        foreach ($order_options->toArray() as $option) {
                            if ($option['element_type'] == 'H') {
                                continue;
                            } //skip hidden options
                            elseif ($option['element_type'] == 'C' && in_array($option['value'], [0, 1, ''])) {
                                $option['value'] = '';
                            }
                            $option_data[] = [
                                'name'  => $option['name'],
                                'value' => $option['value'],
                            ];
                        }
                    }
                    $this->data['products'][] = [
                        'name'       => $product['name'],
                        'product_id' => $product['product_id'],
                        'sku'        => $product['sku'],
                        'model'      => $product['model'],
                        'option'     => $option_data,
                        'quantity'   => $product['quantity'],
                        'price'      => Registry::currency()->format(
                            $product['price'],
                            $order_info['currency'],
                            $order_info['value']
                        ),
                        'total'      => Registry::currency()->format_total(
                            $product['price'],
                            $product['quantity'],
                            $order_info['currency'],
                            $order_info['value']
                        ),
                    ];
                }
            } catch (\Exception $e) {
                Registry::log()->write(__CLASS__.': '.$e->getMessage()."\n".$e->getTraceAsString());
                return null;
            }

            $orderTotals = OrderTotal::where('order_id', '=', $order_id)->get();
            if ($orderTotals) {
                $this->data['totals'] = $orderTotals->toArray();
            }


            //allow to change email data from extensions
            Registry::extensions()->hk_ProcessData($this, 'sf_order_confirm_mail');

            $mail = new AMail($config);
            $mail->setTo($order_info['email']);
            $mail->setFrom($config->get('store_main_email'));
            $mail->setSender($order_info['store_name']);
            $mail->setTemplate('storefront_order_confirm', $this->data, $order_info['language_id']);
            $mail->send();

            //send alert email for merchant
            if ($config->get('config_alert_mail')) {

                //allow to change email data from extensions
                Registry::extensions()->hk_ProcessData($this, 'sf_order_confirm_alert_mail');

                $order_total = '';
                foreach ($orderTotals->toArray() as $row) {
                    if ($row['key'] == 'total') {
                        $order_total = $row['text'];
                        break;
                    }
                }

                $this->data['order_total'] = $order_total;

                $mail->setTo($config->get('store_main_email'));
                $mail->setTemplate('storefront_order_confirm_alert', $this->data);
                $mail->send();

                // Send to additional alert emails
                $emails = explode(',', $config->get('config_alert_emails'));
                foreach ($emails as $email) {
                    if (trim($email)) {
                        $mail->setTo($email);
                        $mail->send();
                    }
                }
            }

            $msg_text =
                sprintf($language->get('text_new_order_text'), $order_info['firstname'].' '.$order_info['lastname']);
            $msg_text .= "<br/><br/>";
            foreach ((array)$this->data['mail_template_data']['totals'] as $total) {
                $msg_text .= $total['title'].' - '.$total['text']."<br/>";
            }
            $msg = new AMessage();
            $msg->saveNotice($language->get('text_new_order').$order_id, $msg_text);

            $language = new ALanguage($this->registry);
            $language->load($language->language_details['directory']);
            $language->load('common/im');
            $message_arr = [
                1 => ['message' => sprintf($language->get('im_new_order_text_to_admin'), $order_id)],
            ];
            Registry::im()->send('new_order', $message_arr);
        }
    }
}