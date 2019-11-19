<?php

namespace abc\modules\listeners;

use abc\core\ABC;
use abc\core\engine\ALanguage;
use abc\core\engine\Registry;
use abc\core\lib\AEncryption;
use abc\core\lib\AMail;
use abc\models\order\Order;
use abc\modules\events\ABaseEvent;
use H;

class AdminSendOrderStatusNotifyEmailListener
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

        $order_id = $orderInfo['order_id'];
        //load language specific for the order in admin section
        $language = new ALanguage(Registry::getInstance(), $orderInfo['language_code'], 1);
        $language->load($orderInfo['language_filename']);
        $language->load('mail/order');

        $subject = sprintf($language->get('text_subject'), $orderInfo['store_name'], $order_id);

        $message = $language->get('text_order').' '.$order_id."\n";
        $message .= $language->get('text_date_added').' '.H::dateISO2Display($orderInfo['date_added'],
                $language->get('date_format_short'))."\n\n";
        $message .= $language->get('text_order_status')."\n\n";
        $message .= $orderInfo['order_status_name']."\n\n";
        //send link to order only for registered customers
        if ($orderInfo['customer_id']) {
            $message .= $language->get('text_invoice')."\n";
            $message .= html_entity_decode(
                    $orderInfo['store_url'].'index.php?rt=account/invoice&order_id='.$order_id,
                    ENT_QUOTES,
                    ABC::env('APP_CHARSET')
                )."\n\n";
        } //give link on order page for quest
        elseif ($config->get('config_guest_checkout') && $orderInfo['email']) {
            /**
             * @var AEncryption $enc
             */
            $enc = ABC::getObjectByAlias('AEncryption', [$config->get('encryption_key')]);
            $order_token = $enc->encrypt($order_id.'::'.$orderInfo['email']);
            $message .= $language->get('text_invoice')."\n";
            $message .= html_entity_decode(
                    $orderInfo['store_url'].'index.php?rt=account/invoice&ot='.$order_token,
                    ENT_QUOTES,
                    ABC::env('APP_CHARSET')
                )."\n\n";
        }

        if ($data['comment']) {
            $message .= $language->get('text_comment')."\n\n";
            $message .= strip_tags(html_entity_decode($data['comment'], ENT_QUOTES, ABC::env('APP_CHARSET')))."\n\n";
        }

        $message .= $language->get('text_footer');

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
        $mail->setSubject($subject);
        $mail->setText(html_entity_decode($message, ENT_QUOTES, ABC::env('APP_CHARSET')));
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