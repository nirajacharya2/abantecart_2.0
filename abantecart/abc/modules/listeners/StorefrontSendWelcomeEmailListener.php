<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2022 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/
namespace abc\modules\listeners;

use abc\core\ABC;
use abc\core\engine\AResource;
use abc\core\engine\Registry;
use abc\core\lib\AException;
use abc\core\lib\AMail;
use abc\models\system\Setting;
use abc\modules\events\ABaseEvent;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

class StorefrontSendWelcomeEmailListener
{

    protected $data;

    /**
     * @param ABaseEvent $event
     *
     * @throws InvalidArgumentException
     * @throws ReflectionException | AException
     */
    public function handle(ABaseEvent $event)
    {
        $customer_info = $event->args[0];
        // send email to customer
        if ( $customer_info && $customer_info['email'] ) {
            Registry::load()->language('mail/account_create');
            $language = Registry::language();
            $store_info = Setting::getStoreSettings($customer_info['store_id']);
            $config_mail_logo = $store_info->config_mail_logo ?: $store_info->config_logo;

            if ($config_mail_logo) {
                if (is_numeric($config_mail_logo)) {
                    $r = new AResource('image');
                    $resource_info = $r->getResource($config_mail_logo);
                    if ($resource_info) {
                        $this->data['logo_html'] = html_entity_decode(
                            $resource_info['resource_code'],
                            ENT_QUOTES,
                            ABC::env('APP_CHARSET')
                        );
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
            Registry::extensions()->hk_ProcessData($this, 'sf_account_welcome_mail');

            $mail = new AMail(Registry::config());
            $mail->setTo($customer_info['email']);
            $mail->setFrom($store_info->store_main_email);
            $mail->setSender($store_info->store_name);

            $tpl = $customer_info['activated']
                ? 'storefront_welcome_email_activated'
                : 'storefront_welcome_email_approval';

            $mail->setTemplate($tpl, $this->data, $language->getLanguageID());

            if (is_file(ABC::env('DIR_RESOURCES') . $store_info->config_mail_logo)) {
                $mail->addAttachment(
                    ABC::env('DIR_RESOURCES') . $store_info->config_mail_logo,
                    md5(pathinfo($store_info->config_mail_logo, PATHINFO_FILENAME))
                    . '.' . pathinfo($store_info->config_mail_logo, PATHINFO_EXTENSION));
            }
            $mail->send();
        }
    }
}