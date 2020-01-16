<?php

namespace abc\modules\listeners;

use abc\core\ABC;
use abc\core\engine\ALanguage;
use abc\core\engine\Registry;
use abc\core\lib\AMail;
use abc\models\system\Setting;
use abc\modules\events\ABaseEvent;

class StorefrontSendResetPasswordLinkListener
{

    protected $registry, $data, $language;
    protected $db;
    const DECIMAL = 2;

    public function __construct()
    {
        $this->registry = Registry::getInstance();
        $this->language = $this->registry->get('language');
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
        $data = $event->args[1];
        $rtoken = $data['rtoken'];

        // send email to customer
        if ($customer_info && $customer_info['email']) {

            $store_info = Setting::getStoreSettings($customer_info['store_id']);

            $this->data['store_name'] = $store_info->store_name;
            $this->data['reset_link'] = Registry::html()->getCatalogURL('account/forgotten/reset', '&rtoken='.$rtoken);

            //allow to change email data from extensions
            Registry::extensions()->hk_ProcessData($this, 'sf_password_reset_mail');

            $mail = new AMail(Registry::config());
            $mail->setTo($customer_info['email']);
            $mail->setFrom($store_info->store_main_email);
            $mail->setSender($store_info->store_name);
            $mail->setTemplate('storefront_reset_password_link', $this->data, $this->registry->get('language')->getLanguageID());
            $mail->send();
        }

    }
}