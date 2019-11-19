<?php

namespace abc\modules\listeners;

use abc\core\ABC;
use abc\core\engine\ALanguage;
use abc\core\engine\Registry;
use abc\core\lib\AEncryption;
use abc\core\lib\AMail;
use abc\modules\events\ABaseEvent;
use H;

class StorefrontSendOrderUpdateEmailListener
{

    protected $registry, $data;
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

        $orderData = $event->args[0];
        $orderStatus = $event->args[1];
        $comment = $event->args[2];

        $order_id = $orderData['order_id'];
        $config = Registry::config();

        $language = new ALanguage($this->registry, $orderData['code']);
        $language->load($language->language_details['directory']);
        $language->load('mail/order_update');

        // send email to customer
        if ( $orderData && $orderData['email'] ) {

            $subject = sprintf(
                $language->get('text_subject'),
                html_entity_decode($orderData['store_name'], ENT_QUOTES, ABC::env('APP_CHARSET')),
                $order_id
            );

            $message = $language->get('text_order').' '.$order_id."\n";
            $message .= $language->get('text_date_added').' '
                .H::dateISO2Display($orderData['date_added'], $language->get('date_format_short'))
                ."\n\n";

            if ($orderStatus->description->name) {
                $message .= $language->get('text_order_status')."\n\n";
                $message .= $orderStatus->description->name."\n\n";
            }

            if ($orderData['customer_id']) {
                $message .= $language->get('text_invoice')."\n";
                $message .= $orderData['store_url'].'index.php?rt=account/invoice&order_id='.$order_id."\n\n";
            } //give link on order page for quest
            elseif ($config->get('config_guest_checkout') && $orderData['email']) {
                /**
                 * @var AEncryption $enc
                 */
                $enc = ABC::getObjectByAlias('AEncryption', [$config->get('encryption_key')]);
                $order_token = $enc->encrypt($order_id.'::'.$orderData['email']);
                if ($order_token) {
                    $message .= $language->get('text_invoice')."\n";
                    $message .= $orderData['store_url'].'index.php?rt=account/invoice&ot='.$order_token."\n\n";
                }
            }

            if ($comment) {
                $message .= $language->get('text_comment')."\n\n";
                $message .= $comment."\n\n";
            }

            $message .= $language->get('text_footer');

            $mail = new AMail($config);
            $mail->setTo($orderData['email']);
            $mail->setFrom($config->get('store_main_email'));
            $mail->setSender($orderData['store_name']);
            $mail->setSubject($subject);
            $mail->setText(html_entity_decode($message, ENT_QUOTES, ABC::env('APP_CHARSET')));
            $mail->send();

        }

    }
}