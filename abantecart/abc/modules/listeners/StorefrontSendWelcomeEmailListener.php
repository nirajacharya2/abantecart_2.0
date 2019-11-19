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

            //build welcome email in text format
            $login_url = Registry::html()->getSecureURL( 'account/login' );

            $this->registry->get('load')->language('mail/account_create');
            $language = $this->registry->get('language');

            /**
             * @var Setting $store_info
             */
            $store_info = Setting::getStoreSettings($customer_info['store_id']);
            $config_mail_logo = $store_info->config_mail_logo ?: $store_info->config_logo;



            $this->data['mail_plain_text'] = sprintf( $language->get( 'text_welcome' ), $store_info->store_name )."\n\n";
            if ( $customer_info['activated'] ) {
                $this->data['mail_plain_text'] .= $language->get( 'text_login' )."\n";
                $this->data['mail_plain_text'] .= $login_url."\n\n";
            } else {
                $this->data['mail_plain_text'] .= $language->get( 'text_approval' )."\n\n";
                $this->data['mail_plain_text'] .= $login_url."\n\n";
            }
            $this->data['mail_plain_text'] .= $language->get( 'text_services' )."\n\n";
            $this->data['mail_plain_text'] .= $language->get( 'text_thanks' )."\n";
            $this->data['mail_plain_text'] .= $store_info->store_name;

            //build HTML message with the template
            $this->data['mail_template_data']['text_welcome'] = sprintf( $language->get( 'text_welcome' ), $store_info->store_name )."\n\n";
            $this->data['mail_template_data']['text_thanks'] = $language->get( 'text_thanks' );
            if ( $customer_info['activated'] ) {
                $this->data['mail_template_data']['text_login'] = $language->get( 'text_login' );
                $this->data['mail_template_data']['text_login_later'] = '<a href="'.$login_url.'">'.$login_url.'</a>';
                $this->data['mail_template_data']['text_services'] = $language->get( 'text_services' );
            } else {
                $this->data['mail_template_data']['text_approval'] = $language->get( 'text_approval' );
                $this->data['mail_template_data']['text_login_later'] = '<a href="'.$login_url.'">'.$login_url.'</a>';
            }

            if ( $config_mail_logo ) {
                if ( is_numeric( $config_mail_logo ) ) {
                    $r = new AResource( 'image' );
                    $resource_info = $r->getResource( $config_mail_logo );
                    if ( $resource_info ) {
                        $this->data['mail_template_data']['logo_html'] = html_entity_decode( $resource_info['resource_code'], ENT_QUOTES, ABC::env( 'APP_CHARSET' ) );
                    }
                } else {
                    $this->data['mail_template_data']['logo_uri'] = 'cid:'
                        .md5( pathinfo( $config_mail_logo, PATHINFO_FILENAME ) )
                        .'.'.pathinfo( $config_mail_logo, PATHINFO_EXTENSION );
                }
            }


            $this->data['mail_template_data']['logo'] = $store_info->config_mail_logo;
            $this->data['mail_template_data']['store_name'] = $store_info->store_name;
            $this->data['mail_template_data']['store_url'] = $store_info->store_url;
            $this->data['mail_template_data']['text_project_label'] = H::project_base();

            $this->data['mail_template'] = 'mail/account_create.tpl';

            $subject = sprintf( $language->get( 'text_subject' ), $store_info->store_name );

            //allow to change email data from extensions
            Registry::extensions()->hk_ProcessData( $this, 'sf_account_welcome_mail' );

            $view = new AView( $this->registry, 0 );
            $view->batchAssign( $this->data['mail_template_data'] );
            $html_body = $view->fetch( $this->data['mail_template'] );

            $mail = new AMail($this->registry->get('config'));
            $mail->setTo($customer_info['email']);
            $mail->setFrom($store_info->store_main_email);
            $mail->setSender($store_info->store_name);
            $mail->setSubject($subject);
            $mail->setText(html_entity_decode($this->data['mail_plain_text'], ENT_QUOTES, ABC::env('APP_CHARSET')));
            $mail->setHtml($html_body);
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