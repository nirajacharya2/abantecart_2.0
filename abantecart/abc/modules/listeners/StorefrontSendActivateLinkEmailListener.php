<?php

namespace abc\modules\listeners;

use abc\core\ABC;
use abc\core\engine\AResource;
use abc\core\engine\Registry;
use abc\core\lib\AEncryption;
use abc\core\lib\AMail;
use abc\core\view\AView;
use abc\models\customer\Customer;
use abc\models\system\Setting;
use abc\modules\events\ABaseEvent;
use H;

class StorefrontSendActivateLinkEmailListener
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
            $language->load( 'mail/account_create' );

            /**
             * @var Setting $store_info
             */
            $store_info = Setting::getStoreSettings($customer_info['store_id']);
            $config_mail_logo = $store_info->config_mail_logo ?: $store_info->config_logo;


            //encrypt token and data
            /**
             * @var AEncryption $enc
             */
            $enc = ABC::getObjectByAlias('AEncryption', [ $store_info->encryption_key ]);
            $code = H::genToken();
            //store activation code
            $data = ['email_activation'=> $code];

            Customer::find($customer_info['customer_id'])->update(['data' => $data ]);

            $ac = $enc->encrypt( $customer_info['customer_id'].'::'.$code );

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


            $this->data['logo'] = $store_info->config_mail_logo;
            $this->data['store_name'] = $store_info->store_name;
            $this->data['store_url'] = $store_info->store_url;
            $this->data['activate_url'] = Registry::html()->getSecureURL( 'account/login', '&ac='.$ac );
            $this->data['text_project_label'] = H::project_base();

            //allow to change email data from extensions
            Registry::extensions()->hk_ProcessData( $this, 'sf_account_welcome_mail' );

            $view = new AView( $this->registry, 0 );
            $view->batchAssign( $this->data['mail_template_data'] );
            $html_body = $view->fetch( $this->data['mail_template'] );

            $mail = new AMail($this->registry->get('config'));
            $mail->setTo($customer_info['email']);
            $mail->setFrom($store_info->store_main_email);
            $mail->setSender($store_info->store_name);

            $mail->setTemplate('storefront_send_activate_link', $this->data, $this->registry->get('language')->getLanguageID());

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