<?php

namespace abc\modules\listeners;

use abc\core\ABC;
use abc\core\engine\AResource;
use abc\core\engine\Registry;
use abc\core\lib\AMail;
use abc\core\view\AView;
use abc\models\system\Setting;
use abc\modules\events\ABaseEvent;
use H;

class StorefrontSendWelcomeEmailListener
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

        $customer_info = $event->args[0];

        // send email to customer
        if ( $customer_info && $customer_info['email'] ) {

            $this->registry->get('load')->language('mail/account_create');
            $language = $this->registry->get('language');

            /**
             * @var Setting $store_info
             */
            $store_info = Setting::getStoreSettings($customer_info['store_id']);
            $config_mail_logo = $store_info->config_mail_logo ?: $store_info->config_logo;

            if ( $config_mail_logo ) {
                if ( is_numeric( $config_mail_logo ) ) {
                    $r = new AResource( 'image' );
                    $resource_info = $r->getResource( $config_mail_logo );
                    if ( $resource_info ) {
                        $this->data['logo_html'] = html_entity_decode( $resource_info['resource_code'], ENT_QUOTES, ABC::env( 'APP_CHARSET' ) );
                    }
                } else {
                    $this->data['logo_uri'] = 'cid:'
                        .md5( pathinfo( $config_mail_logo, PATHINFO_FILENAME ) )
                        .'.'.pathinfo( $config_mail_logo, PATHINFO_EXTENSION );
                }
            }

            $this->data['login_url'] = Registry::html()->getSecureURL( 'account/login' );
            $this->data['store_url'] = $store_info->store_url;
            $this->data['store_name'] = $store_info->store_name;
            $this->data['logo'] = $store_info->config_mail_logo;

            //allow to change email data from extensions
            Registry::extensions()->hk_ProcessData( $this, 'sf_account_welcome_mail' );

            $mail = new AMail($this->registry->get('config'));
            $mail->setTo($customer_info['email']);
            $mail->setFrom($store_info->store_main_email);
            $mail->setSender($store_info->store_name);
            if ( $customer_info['activated'] ) {
                $mail->setTemplate('storefront_welcome_email_activated', $this->data, $this->registry->get('language')->getLanguageID());
            } else {
                $mail->setTemplate('storefront_welcome_email_approval', $this->data, $this->registry->get('language')->getLanguageID());
            }

            if (is_file(ABC::env('DIR_RESOURCES').$store_info->config_mail_logo)) {
                $mail->addAttachment(
                    ABC::env('DIR_RESOURCES').$store_info->config_mail_logo,
                    md5(pathinfo($store_info->config_mail_logo, PATHINFO_FILENAME))
                    .'.'.pathinfo($store_info->config_mail_logo, PATHINFO_EXTENSION));
            }
            $mail->send();
        }

    }
}