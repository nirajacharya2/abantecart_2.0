<?php

namespace abc\modules\listeners;

use abc\core\ABC;
use abc\core\engine\Registry;
use abc\models\customer\CustomerTransaction;
use abc\models\order\Order;
use abc\modules\events\ABaseEvent;
use Illuminate\Validation\ValidationException;

class AdminOrderUpdateTotalBalance
{

    public $registry, $data;
    protected $db;

    public function __construct()
    {
        $this->registry = Registry::getInstance();
    }

    /**
     * @param ABaseEvent $event
     *
     * @return bool
     * @throws \Exception
     */
    public function handle(ABaseEvent $event)
    {
        if (ABC::env('IS_ADMIN') !== true) {
            return true;
        }

        $config = $this->registry::config();
        $order_id = $event->args[0];
        $data = $event->args[1];

        //if no changes or balance disabled - skip
        if (!$data['total_difference'] || !$config->get('balance_status')) {
            return true;
        }

        $orderInfo = Order::getOrderArray($order_id, 'any');

        if (!$orderInfo) {
            Registry::log()->write(__CLASS__.": order #".$order_id." not found!");
            return true;
        }
        //skip guest checkout
        if (!$orderInfo['customer_id']) {
            return true;
        }

        $transactionData = [
            'customer_id'      => $orderInfo['customer_id'],
            'order_id'         => $order_id,
            'created_by'       => Registry::user()->getId(),
            'section'          => 1,
            'transaction_type' => 'order',
            'comment'          => '',
            'description'      => '',
        ];
        if ($data['total_difference'] < 0) {
            //debit - take diff from customer balance
            $transactionData['debit'] = abs($data['total_difference']);
            $transactionData['comment'] = 'Taken related to total amount change during order resaving';
        } else {
            //credit - return diff back to customer
            $transactionData['credit'] = abs($data['total_difference']);
            $transactionData['comment'] = 'Turned back related to total amount change during order resaving';
        }

        $transaction = new CustomerTransaction($transactionData);
        try {
            $transaction->validate();
            $transaction->save();
            Registry::cache()->flush('customer');
        } catch (ValidationException $e) {
            $errors = [];
            \H::SimplifyValidationErrors($transaction->errors()['validation'], $errors);
            Registry::log()->write(var_export($errors, true));
            throw new \Exception(__CLASS__);
        }
        return true;
    }
}