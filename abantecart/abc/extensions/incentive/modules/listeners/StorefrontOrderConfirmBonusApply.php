<?php

namespace abc\extensions\incentive\modules\listeners;

use abc\core\engine\Registry;
use abc\extensions\incentive\modules\traits\IncentiveTrait;
use abc\models\order\Order;
use abc\models\order\OrderDataType;
use abc\models\order\OrderDatum;
use abc\modules\events\ABaseEvent;
use abc\core\ABC;
use Exception;

class StorefrontOrderConfirmBonusApply
{
    use IncentiveTrait;

    /**
     * @param ABaseEvent $event
     *
     * @return array
     * @throws Exception
     */
    public function handle(ABaseEvent $event)
    {
        $order_id = $event->args[0];
        //get product details
        $order = Order::find($order_id);
        if (!$order->order_id) {
            return [
                'result'  => false,
                'message' => 'Unknown order ID'
            ];
        }

        $orderDataTypeId = (int)OrderDataType::where('name', '=', 'incentive_data')->first()?->type_id;

        if (!$orderDataTypeId) {
            return [
                'result'  => false,
                'message' => 'no incentives_data found in order_data_types table'
            ];
        }

        /** @var OrderDatum $orderData */
        $orderData = OrderDatum::where('type_id', '=', $orderDataTypeId)
            ->where('order_id', '=', $order_id)
            ->first();
        $data = $orderData->data;

        if (!$data) {
            return [
                'result'  => false,
                'message' => 'no incentives found for order ID ' . $order_id
            ];
        }

        foreach ($data['incentives'] as $incentive_id => $bonus_amount) {
            $this->saveResult(
                (int)$incentive_id,
                (int)$order->customer_id,
                0,
                $bonus_amount,
                $data['applied_bonuses'] + ['matched_conditions' => $data['matched_conditions']]
            );
        }
    }
}