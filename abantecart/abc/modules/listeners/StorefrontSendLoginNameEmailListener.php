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

class StorefrontSendLoginNameEmailListener
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
     * @throws \Exception
     */
    public function handle(ABaseEvent $event)
    {

        $customer_info = $event->args[0];

        // send email to customer
        if ( $customer_info && $customer_info['email'] ) {

            $store_info = Setting::getStoreSettings($customer_info['store_id']);

            $this->data['store_name'] = $store_info->store_name;
            $this->data['login_name'] = $customer_info['loginname'];

            //allow to change email data from extensions
            Registry::extensions()->hk_ProcessData( $this, 'sf_loginname_reminder_email' );

            $mail = new AMail(Registry::config());
            $mail->setTo($customer_info['email']);
            $mail->setFrom($store_info->store_main_email);
            $mail->setSender($store_info->store_name);
            $mail->setTemplate('storefront_send_login_name', $this->data, $this->registry->get('language')->getLanguageID());
            $mail->send();
        }

    }
}