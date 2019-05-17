<?php

namespace abc\modules\listeners;

use abc\core\ABC;
use abc\core\engine\AResource;
use abc\core\engine\Registry;
use abc\core\lib\AMail;
use abc\core\view\AView;
use abc\models\system\Setting;
use abc\models\user\User;
use abc\modules\events\ABaseEvent;
use H;

class AdminSendApprovalEmailListener
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
        if ($customer_info && !$customer_info['approved']) {
            $this->registry->get('load')->language('mail/customer');
            $language = $this->registry->get('language');
            $config = $this->registry->get('config');
            /**
             * @var Setting $store_info
             */
            $store_info = Setting::getStoreSettings($customer_info['store_id']);

            if ($store_info) {
                $store_info->store_url = $store_info->config_url.'index.php?rt=account/login';
            } else {

                $store_info->store_name = $config->get('store_name');
                $store_info->store_url  = $config->get('config_url').'index.php?rt=account/login';
                $store_info->config_mail_logo = $config->get('config_mail_logo');
                $store_info->config_logo = $config->get('config_logo');

            }
            $store_info->config_mail_logo = $store_info->config_mail_logo ?: $store_info->config_logo;

            //build plain text email
            $this->data['mail_plain_text'] = sprintf(
                                                    $language->get('text_welcome'),
                                                    $store_info->store_name
                                                )."\n\n";
            $this->data['mail_plain_text'] .= $language->get('text_login')."\n";
            $this->data['mail_plain_text'] .= $store_info->store_url."\n\n";
            $this->data['mail_plain_text'] .= $language->get('text_services')."\n\n";
            $this->data['mail_plain_text'] .= $language->get('text_thanks')."\n";
            $this->data['mail_plain_text'] .= $store_info->store_name;

            //build HTML message with the template
            $this->data['mail_template_data']['text_welcome'] = sprintf(
                                                                $language->get('text_welcome'),
                                                                $store_info->store_name
                                                            )."\n\n";
            $this->data['mail_template_data']['text_login'] = $language->get('text_login');
            $this->data['mail_template_data']['text_login_later'] = '<a href="'.$store_info->store_url.'">'
                                                                    .$store_info->store_url.'</a>';
            $this->data['mail_template_data']['text_services'] = $language->get('text_services');
            if ($store_info->config_mail_logo) {
                if (is_numeric($store_info->config_mail_logo)) {
                    $r = new AResource('image');
                    $resource_info = $r->getResource($store_info->config_mail_logo);
                    if ($resource_info) {
                        $this->data['mail_template_data']['logo_html'] = html_entity_decode(
                                                                                $resource_info['resource_code'],
                                                                                ENT_QUOTES,
                                                                                ABC::env('APP_CHARSET')
                                                                            );
                    }
                } else {
                    $this->data['mail_template_data']['logo_uri'] = 'cid:'
                        .md5(pathinfo($store_info->config_mail_logo, PATHINFO_FILENAME))
                        .'.'.pathinfo($store_info->config_mail_logo, PATHINFO_EXTENSION);
                }
            }

            $this->data['mail_template_data']['logo'] = $store_info->config_mail_logo;
            $this->data['mail_template_data']['store_name'] = $store_info->store_name;
            $this->data['mail_template_data']['store_url'] = $store_info->store_url;
            $this->data['mail_template_data']['text_thanks'] = $language->get('text_thanks');
            $this->data['mail_template_data']['text_project_label'] = H::project_base();

            $this->data['mail_template'] = 'mail/account_create.tpl';

            //allow to change email data from extensions
            $this->registry->get('extensions')->hk_ProcessData($this, 'cp_customer_approve_mail');

            $view = new AView($this->registry, 0);
            $view->batchAssign($this->data['mail_template_data']);
            $html_body = $view->fetch($this->data['mail_template']);

            $mail = new AMail($this->registry->get('config'));
            $mail->setTo($customer_info['email']);
            $mail->setFrom($config->get('store_main_email'));
            $mail->setSender($store_info->store_name);
            $mail->setSubject(sprintf($language->get('text_subject'), $store_info->store_name));
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