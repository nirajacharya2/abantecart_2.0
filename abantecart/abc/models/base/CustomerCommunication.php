<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */

namespace abc\models\base;

use abc\core\lib\AMail;
use abc\models\BaseModel;
use abc\models\admin\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerCommunication extends BaseModel
{
    use SoftDeletes;
    protected $table = 'customer_communications';

    protected $primaryKey = 'communication_id';
    public $timestamps = false;

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'type',
        'subject',
        'body',
        'sent_to_address',
        'date_added',
        'date_modified',
    ];

    public function user() {

        return $this->hasOne(User::class, 'user_id', 'user_id');
    }

    public function customer() {
        return $this->hasOne(Customer::class, 'customer_id', 'customer_id');
    }

    public static function getCustomerCommunications(int $customer_id, $data = []) {
        if (is_array($data) && !empty($data)) {
            return CustomerCommunication::where('customer_id', '=', $customer_id)
                ->offset($data['start'])->take($data['limit'])
                ->orderBy($data['sort'], $data['order'])
                ->get();
        } else {
            return CustomerCommunication::where('customer_id', '=', $customer_id)->get();
        }
    }

    public static function getCustomerCommunicationById(int $communication_id) {
        if ($communication_id > 0) {
            $communication = CustomerCommunication::find($communication_id);
            if ($communication)
            return $communication->toArray(); else return [];
        } else return [];
    }

    public static function createCustomerCommunication(AMail $mail) {
            $communication = new CustomerCommunication();
            $communication->subject = $mail->getSubject();
            $communication->body = $mail->getHtml() ? $mail->getHtml() : nl2br($mail->getText());
            $customers = Customer::where('email', '=', $mail->getTo())->limit(1)->get();
            $customer_id = null;
            foreach ($customers as $customer) {
                $customer_id = $customer->customer_id;
            }
            if (!$customer_id) {
                return;
            }
            $communication->customer_id = $customer_id;
            $communication->user_id = $mail->getUser()->getId() ?: 0;
            $communication->type = 'email';
            $communication->sent_to_address = $mail->getTo();
            $communication->save();
    }

    public static function createCustomerCommunicationIm($customer_id, $to, $message, $user_id=0, $protocol='sms'){
            $communication = new CustomerCommunication();
            $communication->subject = 'IM message';
            $communication->body = $message;
            if (!$customer_id) {
                return;
            }
            $communication->customer_id = $customer_id;
            $communication->user_id = $user_id;
            $communication->sent_to_address = $to;
            $communication->type = $protocol;
            $communication->save();
    }

}