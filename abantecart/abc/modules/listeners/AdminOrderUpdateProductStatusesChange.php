<?php

namespace abc\modules\listeners;

use abc\core\ABC;
use abc\core\engine\ALanguage;
use abc\core\engine\AResource;
use abc\core\engine\Registry;
use abc\core\lib\ACurrency;
use abc\core\lib\ACustomer;
use abc\core\lib\AEncryption;
use abc\core\lib\AMail;
use abc\core\lib\AMessage;
use abc\core\view\AView;
use abc\models\customer\CustomerTransaction;
use abc\models\order\Order;
use abc\models\order\OrderOption;
use abc\models\order\OrderProduct;
use abc\models\order\OrderTotal;
use abc\models\system\Setting;
use abc\modules\events\ABaseEvent;
use H;
use Illuminate\Validation\ValidationException;

class AdminOrderUpdateProductStatusesChange
{

    protected $registry, $data;
    protected $db;

    public function __construct()
    {
        $this->registry = Registry::getInstance();
    }

    /**
     * @param ABaseEvent $event
     *
     * @return bool
     * @throws \Exception
     */
    public function handle(ABaseEvent $event)
    {
        if (ABC::env('IS_ADMIN') !== true) {
            return true;
        }
        $order_id = $event->args[0];
        $data = $event->args[1];

        if (!$order_id) {
            return true;
        }

        if (isset($data['order_status_id'])) {
            $orderProducts = OrderProduct::where('order_id', '=', $order_id)->get();

            if (!$orderProducts) {
                Registry::log()->write(__CLASS__.": order #".$order_id." have no any products!");
                return true;
            }
            try {
                foreach ($orderProducts as $product) {
                    $productStatus = $this->registry::order_status()->getStatusById($product->order_status_id);
                    if (!in_array($productStatus, ABC::env('ORDER')['not_reversal_statuses'])) {
                        $product->update(['order_status_id' => (int)$data['order_status_id']]);
                    }
                }
            } catch (\Exception $e) {
                Registry::log()->write(__CLASS__.": ".$e->getMessage());
            }
        }

        //send notification email to customer
        $order_info = Order::getOrderArray($order_id);
        //if order status not reversal or complete - do not sent email
        if (in_array(
                Registry::order_status()->getStatusById($order_info['order_status_id']),
                (array)ABC::env('ORDER')['not_reversal_statuses']
            )
            || Registry::order_status()->getStatusById($order_info['order_status_id']) == 'completed'
        ) {
            return true;
        }



        // send email to customer
        if ($order_info && $order_info['email']) {
            $order_id = $order_info['order_id'];

            $language = new ALanguage($this->registry, $order_info['language_code']);

            $language->load($order_info['language_filename']);
            $language->load('mail/account_create');
            $language->load($order_info['filename']);
            $language->load('mail/order_confirm');
            $language->load('mail/order_update');

            $config = Registry::config();
            $aCustomer = new ACustomer(Registry::getInstance(), $order_info['customer_id']);

            $currency = Registry::currency() ?: new ACurrency(Registry::getInstance());

            /**
             * @var \stdClass $store_info
             */
            $store_info = Setting::getStoreSettings($order_info['store_id']);
            $config_mail_logo = $store_info->config_mail_logo ?: $store_info->config_logo;

            // HTML Mail
            $this->data['mail_template_data']['title'] = sprintf(
                $language->get('text_subject'),
                html_entity_decode(
                    $order_info['store_name'],
                    ENT_QUOTES,
                    ABC::env('APP_CHARSET')
                ),
                $order_id);
            $this->data['mail_template_data']['text_greeting'] = sprintf(
                $language->get('text_greeting'),
                html_entity_decode(
                    $order_info['store_name'],
                    ENT_QUOTES,
                    ABC::env('APP_CHARSET')
                )
            );
            $this->data['mail_template_data']['text_order_detail'] = $language->get('text_order_detail');
            $this->data['mail_template_data']['text_order_id'] = $language->get('text_order_id');
            $this->data['mail_template_data']['text_invoice'] = $language->get('text_invoice');
            $this->data['mail_template_data']['text_date_added'] = $language->get('text_date_added');
            $this->data['mail_template_data']['text_telephone'] = $language->get('text_telephone');
            $this->data['mail_template_data']['text_mobile_phone'] = $language->get('text_mobile_phone');

            $this->data['mail_template_data']['text_email'] = $language->get('text_email');
            $this->data['mail_template_data']['text_ip'] = $language->get('text_ip');
            $this->data['mail_template_data']['text_fax'] = $language->get('text_fax');
            $this->data['mail_template_data']['text_shipping_address'] = $language->get('text_shipping_address');
            $this->data['mail_template_data']['text_payment_address'] = $language->get('text_payment_address');
            $this->data['mail_template_data']['text_shipping_method'] = $language->get('text_shipping_method');
            $this->data['mail_template_data']['text_payment_method'] = $language->get('text_payment_method');
            $this->data['mail_template_data']['text_comment'] = $language->get('text_comment');
            $this->data['mail_template_data']['text_powered_by'] = $language->get('text_powered_by');
            $this->data['mail_template_data']['text_project_label'] = $language->get('text_powered_by')
                .' '.H::project_base();

            $this->data['mail_template_data']['text_total'] = $language->get('text_total');
            $this->data['mail_template_data']['text_footer'] = $language->get('text_footer');

            $this->data['mail_template_data']['column_product'] = $language->get('column_product');
            $this->data['mail_template_data']['column_model'] = $language->get('column_model');
            $this->data['mail_template_data']['column_quantity'] = $language->get('column_quantity');
            $this->data['mail_template_data']['column_price'] = $language->get('column_price');
            $this->data['mail_template_data']['column_total'] = $language->get('column_total');

            $this->data['mail_template_data']['order_id'] = $order_id;
            $this->data['mail_template_data']['customer_id'] = $order_info['customer_id'];
            $this->data['mail_template_data']['date_added'] = H::dateISO2Display(
                $order_info['date_added'],
                $language->get('date_format_short')
            );

            if ($config_mail_logo) {
                if (is_numeric($config_mail_logo)) {
                    $r = new AResource('image');
                    $resource_info = $r->getResource($config_mail_logo);
                    if ($resource_info) {
                        $this->data['mail_template_data']['logo_html'] = html_entity_decode(
                            $resource_info['resource_code'],
                            ENT_QUOTES,
                            ABC::env('APP_CHARSET')
                        );
                    }
                } else {
                    $this->data['mail_template_data']['logo_uri'] = 'cid:'
                        .md5(pathinfo($config_mail_logo, PATHINFO_FILENAME))
                        .'.'.pathinfo($config_mail_logo, PATHINFO_EXTENSION);
                }
            }

            $this->data['mail_template_data']['logo'] = $config_mail_logo;
            $this->data['mail_template_data']['store_name'] = $order_info['store_name'];
            $this->data['mail_template_data']['address'] = nl2br($config->get('config_address'));
            $this->data['mail_template_data']['telephone'] = $config->get('config_telephone');
            $this->data['mail_template_data']['fax'] = $config->get('config_fax');
            $this->data['mail_template_data']['email'] = $config->get('store_main_email');
            $this->data['mail_template_data']['store_url'] = $order_info['store_url'];

            //give link on order page for quest
            if ($config->get('config_guest_checkout') && $order_info['email']) {
                $enc = new AEncryption($config->get('encryption_key'));
                $order_token = $enc->encrypt($order_id.'::'.$order_info['email']);
                $this->data['mail_template_data']['invoice'] = $order_info['store_url']
                    .'index.php?rt=account/invoice&ot='.$order_token."\n\n";
            }//give link on order for registered customers
            elseif ($order_info['customer_id']) {
                $this->data['mail_template_data']['invoice'] = $order_info['store_url']
                    .'index.php?rt=account/invoice&order_id='.$order_id;
            }

            $this->data['mail_template_data']['firstname'] = $order_info['firstname'];
            $this->data['mail_template_data']['lastname'] = $order_info['lastname'];
            $this->data['mail_template_data']['shipping_method'] = $order_info['shipping_method'];
            $this->data['mail_template_data']['payment_method'] = $order_info['payment_method'];
            $this->data['mail_template_data']['customer_email'] = $order_info['email'];
            $this->data['mail_template_data']['customer_telephone'] = $order_info['telephone'];
            $this->data['mail_template_data']['customer_mobile_phone'] =
                Registry::im()->getCustomerURI(
                    'sms',
                    (int)$order_info['customer_id'],
                    $order_id
                );
            $this->data['mail_template_data']['customer_fax'] = $order_info['fax'];
            $this->data['mail_template_data']['customer_ip'] = $order_info['ip'];
            $this->data['mail_template_data']['comment'] = trim(nl2br($order_info['comment']));

            //override with the data from the before hooks
            if ($this->data) {
                $this->data['mail_template_data'] = array_merge($this->data['mail_template_data'], $this->data);
            }

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

            $this->data['mail_template_data']['shipping_address'] = $aCustomer->getFormattedAddress(
                $shipping_data,
                $order_info['shipping_address_format']
            );

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

            $this->data['mail_template_data']['payment_address'] = $aCustomer->getFormattedAddress(
                $payment_data,
                $order_info['payment_address_format']
            );

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
                        'price'      => $currency->format(
                            $product['price'],
                            $order_info['currency'],
                            $order_info['value']
                        ),
                        'total'      => $currency->format_total(
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
            $this->data['mail_template_data']['products'] = $this->data['products'];

            $orderTotals = OrderTotal::where('order_id', '=', $order_id)->get();
            if ($orderTotals) {
                $totals = $orderTotals->toArray();
                foreach ($totals as &$total) {
                    $total['text'] = html_entity_decode($total['text'], ENT_QUOTES, ABC::env('APP_CHARSET'));
                }
                $this->data['mail_template_data']['totals'] = $totals;
            }

            $this->data['mail_template'] = 'mail/order_confirm.tpl';

            //allow to change email data from extensions
            Registry::extensions()->hk_ProcessData($this, 'sf_order_confirm_mail');

            $view = new AView($this->registry, 0, false);
            $view->batchAssign($this->data['mail_template_data']);
            $html_body = $view->fetch($this->data['mail_template']);

            //text email
            $this->data['mail_template'] = 'mail/order_confirm_text.tpl';

            //allow to change email data from extensions
            Registry::extensions()->hk_ProcessData($this, 'sf_order_confirm_mail_text');
            $this->data['mail_plain_text'] = $view->fetch($this->data['mail_template']);
            $this->data['mail_plain_text'] = html_entity_decode(
                $this->data['mail_plain_text'],
                ENT_QUOTES,
                ABC::env('APP_CHARSET')
            );
            //remove html-tags
            $breaks = ["<br />", "<br>", "<br/>"];
            $this->data['mail_plain_text'] = str_ireplace($breaks, "\r\n", $this->data['mail_plain_text']);

            $subject = sprintf($language->get('text_subject'), $order_info['store_name'], $order_id);

            $mail = new AMail($config);
            $mail->setTo($order_info['email']);
            $mail->setFrom($config->get('store_main_email'));
            $mail->setSender($order_info['store_name']);
            $mail->setSubject($subject);
            $mail->setHtml($html_body);
            $mail->setText($this->data['mail_plain_text']);
            if (is_file(ABC::env('DIR_RESOURCES').$config_mail_logo)) {
                $mail->addAttachment(ABC::env('DIR_RESOURCES').$config_mail_logo);
            }
            $mail->send();

            //send alert email for merchant
            if ($config->get('config_alert_mail')) {

                // HTML
                $this->data['mail_template_data']['text_greeting'] = $language->get('text_received')."\n\n";
                $this->data['mail_template_data']['invoice'] = '';
                $this->data['mail_template_data']['text_invoice'] = '';
                $this->data['mail_template_data']['text_footer'] = '';

                $this->data['mail_template'] = 'mail/order_confirm.tpl';

                //allow to change email data from extensions
                Registry::extensions()->hk_ProcessData($this, 'sf_order_confirm_alert_mail');

                $view = new AView($this->registry, 0, false);
                $view->batchAssign($this->data['mail_template_data']);
                $html_body = $view->fetch($this->data['mail_template']);

                //text email
                //allow to change email data from extensions
                $this->data['mail_template'] = 'mail/order_confirm_text.tpl';
                Registry::extensions()->hk_ProcessData($this, 'sf_order_confirm_alert_mail_text');

                $this->data['mail_plain_text'] = $view->fetch($this->data['mail_template']);
                $this->data['mail_plain_text'] =
                    html_entity_decode($this->data['mail_plain_text'], ENT_QUOTES, ABC::env('APP_CHARSET'));
                //remove html-tags
                $breaks = ["<br />", "<br>", "<br/>"];
                $this->data['mail_plain_text'] = str_ireplace($breaks, "\r\n", $this->data['mail_plain_text']);

                $order_total = '';
                foreach ($orderTotals->toArray() as $row) {
                    if ($row['key'] == 'total') {
                        $order_total = $row['text'];
                        break;
                    }
                }

                $subject = sprintf($language->get('text_subject'),
                    html_entity_decode($config->get('store_name'), ENT_QUOTES, ABC::env('APP_CHARSET')),
                    $order_id.' ('.$order_total.')');

                $mail->setSubject($subject);
                $mail->setTo($config->get('store_main_email'));
                $mail->setHtml($html_body);
                $mail->setText($this->data['mail_plain_text']);
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
            foreach ($this->data['mail_template_data']['totals'] as $total) {
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
        return true;
    }
}