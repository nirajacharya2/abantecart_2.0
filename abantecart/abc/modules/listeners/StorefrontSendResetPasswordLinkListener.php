<?php

namespace abc\modules\listeners;

use abc\core\ABC;
use abc\core\engine\ALanguage;
use abc\core\engine\AResource;
use abc\core\engine\Registry;
use abc\core\lib\AMail;
use abc\core\view\AView;
use abc\models\system\Setting;
use abc\modules\events\ABaseEvent;
use H;

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
     * @throws \Exception
     */
    public function handle(ABaseEvent $event)
    {

        $customer_info = $event->args[0];
        $data = $event->args[1];
        $rtoken = $data['rtoken'];

        // send email to customer
        if ($customer_info && $customer_info['email']) {

            if (ABC::env('IS_ADMIN')) {
                //create new instance of language for case when model called from admin-side
                $language = new ALanguage($this->registry, $this->language->getLanguageCode(), 0);
                $language->load($language->language_details['filename']);
                $language->load('mail/account_forgotten');
            } else {
                $this->registry->get('load')->language('mail/account_forgotten');
                $language = $this->registry->get('language');
           }
            $store_info = Setting::getStoreSettings($customer_info['store_id']);
            $link = Registry::html()->getCatalogURL('account/forgotten/reset', '&rtoken='.$rtoken);

            $this->data['mail_plain_text'] = sprintf($language->get('text_greeting'), $store_info->store_name)
                ."\n\n"
                .$language->get('text_password')
                ."\n\n"
                .$link;

            $subject = sprintf($language->get('text_subject'), $store_info->store_name);

            //allow to change email data from extensions
            Registry::extensions()->hk_ProcessData($this, 'sf_password_reset_mail');

            $mail = new AMail(Registry::config());
            $mail->setTo($customer_info['email']);
            $mail->setFrom($store_info->store_main_email);
            $mail->setSender($store_info->store_name);
            $mail->setSubject($subject);
            $mail->setText(html_entity_decode($this->data['mail_plain_text'], ENT_QUOTES, ABC::env('APP_CHARSET')));
            $mail->send();
        }

    }
}