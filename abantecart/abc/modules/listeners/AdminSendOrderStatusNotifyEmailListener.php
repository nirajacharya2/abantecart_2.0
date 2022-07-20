<?php

namespace abc\modules\listeners;

use abc\core\ABC;
use abc\core\engine\ALanguage;
use abc\core\engine\AResource;
use abc\core\engine\Registry;
use abc\core\lib\AEncryption;
use abc\core\lib\AMail;
use abc\models\order\Order;
use abc\models\system\Setting;
use abc\modules\events\ABaseEvent;
use H;

class AdminSendOrderStatusNotifyEmailListener
{

    public $registry, $data;
    protected $db;

    public function __construct()
    {
        $this->registry = Registry::getInstance();
    }

    /**
     * @param ABaseEvent $event
     *
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public function handle(ABaseEvent $event)
    {
        $data = $event->args[0];
        if (!$data['notify'] || !$data['order_id']) {
            return true;
        }

        $dcrypt = $this->registry::dcrypt();
        $im = $this->registry::im();
        $config = $this->registry::config();

        $orderInfo = Order::getOrderArray($data['order_id'], 'any');

        if (!$orderInfo) {
            Registry::log()->write(__CLASS__.": order #".$data['order_id']." not found!");
            return true;
        }

        /**
         * @var \stdClass $store_info
         */
        $store_info = Setting::getStoreSettings($orderInfo['store_id']);
        $logo = $store_info->config_mail_logo ?: $store_info->config_logo;
        $homepage = Registry::html()->getHomeURL();

        //see if we have a resource ID instead of path
        if (is_numeric($logo)) {
            $resource = new AResource('image');
            $image_data = $resource->getResource( $logo );
            $img_sub_path = $image_data['type_name'].'/'.$image_data['resource_path'];
            if ( is_file(ABC::env('DIR_RESOURCES') . $img_sub_path) ) {
                $logo = $img_sub_path;
            } else {
                $logo = $image_data['resource_code'];
            }
        }
        $this->data['logo_uri'] = $homepage.'resources/'.$logo;

        $order_id = $orderInfo['order_id'];
        //load language specific for the order in admin section
        $language = new ALanguage(Registry::getInstance(), $orderInfo['language_code'], 1);
        $language->load($orderInfo['language_filename']);
        $language->load('mail/order');

        $this->data['store_name'] = $orderInfo['store_name'];
        $this->data['order_id'] = $order_id;
        $this->data['date_added'] = H::dateISO2Display($orderInfo['date_added'],
                $language->get('date_format_short'));

        $this->data['order_status_name'] = $orderInfo['order_status_name'];

        //send link to order only for registered customers
        if ($orderInfo['customer_id']) {
            $this->data['invoice'] = html_entity_decode(
                    $orderInfo['store_url'].'index.php?rt=account/invoice&order_id='.$order_id,
                    ENT_QUOTES,
                    ABC::env('APP_CHARSET')
                );
        } //give link on order page for quest
        elseif ($config->get('config_guest_checkout') && $orderInfo['email']) {
            /**
             * @var AEncryption $enc
             */
            $enc = ABC::getObjectByAlias('AEncryption', [$config->get('encryption_key')]);
            $order_token = $enc->encrypt($order_id.'::'.$orderInfo['email']);
            $this->data['invoice'] = html_entity_decode(
                    $orderInfo['store_url'].'index.php?rt=account/invoice&ot='.$order_token,
                    ENT_QUOTES,
                    ABC::env('APP_CHARSET')
                );
        }

        if ($data['comment']) {
            $this->data['comment']= strip_tags(html_entity_decode($data['comment'], ENT_QUOTES, ABC::env('APP_CHARSET')));
        }


        if ($dcrypt->active) {
            $customer_email = $dcrypt->decrypt_field(
                $orderInfo['email'],
                $orderInfo['key_id']
            );
        } else {
            $customer_email = $orderInfo['email'];
        }

        $mail = new AMail($config);
        $mail->setTo($customer_email);
        $mail->setFrom($config->get('store_main_email'));
        $mail->setSender($orderInfo['store_name']);
        $mail->setTemplate('admin_order_status_notify', $this->data, $orderInfo['language_id']);
        $mail->send();

        //send IMs except emails.
        //TODO: add notifications for guest checkout
        $language->load('common/im');
        $invoice_url = $orderInfo['store_url'].'index.php?rt=account/invoice&order_id='.$order_id;
        //disable email protocol to prevent duplicates emails
        $im->removeProtocol('email');

        if ($orderInfo['customer_id']) {
            $message_arr = [
                0 => [
                    'message' => sprintf($language->get('im_order_update_text_to_customer'),
                        $invoice_url,
                        $order_id,
                        html_entity_decode($orderInfo['store_url'].'index.php?rt=account/account')),
                ],
            ];
            $im->sendToCustomer($orderInfo['customer_id'], 'order_update', $message_arr);
        } else {
            $message_arr = [
                0 => [
                    'message' => sprintf($language->get('im_order_update_text_to_guest'),
                        $invoice_url,
                        $order_id,
                        html_entity_decode($invoice_url)),
                ],
            ];
            $im->sendToGuest($order_id, $message_arr);
        }
        //turn email-protocol back
        $im->addProtocol('email');
        return true;
    }
}