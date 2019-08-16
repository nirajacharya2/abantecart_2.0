<?php

namespace abc\modules\listeners;

use abc\core\ABC;
use abc\core\engine\ALanguage;
use abc\core\engine\Registry;
use abc\core\lib\AEncryption;
use abc\core\lib\AMail;
use abc\models\customer\CustomerTransaction;
use abc\models\order\Order;
use abc\models\order\OrderProduct;
use abc\modules\events\ABaseEvent;
use H;
use Illuminate\Validation\ValidationException;

class AdminOrderUpdateProductStatusesChange
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
     * @return bool
     * @throws \Exception
     */
    public function handle(ABaseEvent $event)
    {
        if (ABC::env('IS_ADMIN') !== true) {
            return true;
        }
        $order_id = $event->args[0];
        $data = $event->args[1];

        if (!$order_id || !isset($data['order_status_id'])) {
            return true;
        }

        $orderProducts = OrderProduct::where('order_id', '=', $order_id)->get();

        if (!$orderProducts) {
            Registry::log()->write(__CLASS__.": order #".$order_id." have no any products!");
            return true;
        }
        try {
            foreach ($orderProducts as $product) {
                $productStatus = $this->registry::order_status()->getStatusById($product->order_status_id);
                if (!in_array($productStatus, ABC::env('ORDER')['not_reversal_statuses'])) {
                    $product->update(['order_status_id' => $data['order_status_id']]);
                }
            }
        } catch (\Exception $e) {
            Registry::log()->write(__CLASS__.": ".$e->getMessage());
        }

        return true;
    }
}