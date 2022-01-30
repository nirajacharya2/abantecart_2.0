<?php

namespace abc\modules\listeners;

use abc\core\ABC;
use abc\core\engine\Registry;
use abc\core\lib\AMail;
use abc\models\customer\Customer;
use abc\models\system\Setting;
use abc\modules\events\ABaseEvent;

class AdminSendNewTransactionNotifyEmailListener
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
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public function handle(ABaseEvent $event)
    {

        $transaction_info = $event->args[0];
        $data = $event->args[1];

        // send email to customer
        if ($transaction_info && !$transaction_info['approved']) {
            $this->registry->get('load')->language('mail/customer');
            $language = $this->registry->get('language');
            $config = $this->registry->get('config');

            if ($data['notify']) {

                $customer_info = Customer::find($transaction_info['customer_id']);

                if ($customer_info) {


                    /**
                     * @var Setting $store_info
                     */
                    $store_info = Setting::getStoreSettings($customer_info['store_id']);

                    if ($store_info) {
                        $store_info->store_url = $store_info->config_url.'index.php?rt=account/login';
                    } else {

                        $store_info->store_name = $config->get('store_name');
                        $store_info->store_url = $config->get('config_url').'index.php?rt=account/transactions';
                        $store_info->config_mail_logo = $config->get('config_mail_logo');
                        $store_info->config_logo = $config->get('config_logo');

                    }

                    //build plain text email
                    $amount = $this->registry->get('currency')->format($data['credit'] - $data['debit']);
                    $this->data['store_name'] = $store_info->store_name;
                    $this->data['amount'] = $amount;


                    //allow to change email data from extensions
                    $this->registry->get('extensions')->hk_ProcessData($this, 'cp_customer_transaction_notify_mail');

                    $mail = new AMail($config);
                    $mail->setTo($customer_info->email);
                    $mail->setFrom($config->get('store_main_email'));
                    $mail->setSender($store_info->store_name);
                    $mail->setTemplate('admin_new_transaction_notify', $this->data, $this->registry->get('language')->getContentLanguageID());
                    $mail->send();

                    //notify customer
                    $language->load('common/im');
                    $message_arr = [
                        0 => [
                            'message' => sprintf($language->get('im_customer_account_update_text_to_customer'),
                                $store_info->store_name, $amount, $store_info->store_name),
                        ],
                    ];
                    $this->registry->get('im')
                                   ->sendToCustomer($data['customer_id'], 'customer_account_update', $message_arr);
                }

            }
        }
    }
}