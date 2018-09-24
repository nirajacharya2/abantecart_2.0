<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 19.09.2018
 * Time: 17:55
 */

namespace abc\models\base;

use abc\core\helper\AHelperUtils;
use abc\core\lib\AMail;
use abc\core\lib\AMailIM;
use abc\models\AModelBase;
use abc\models\admin\User;

class CustomerCommunication extends AModelBase
{
    protected $table = 'customer_communications';

    protected $primaryKey = 'communication_id';
    public $timestamps = false;

    protected $permissions = [
        self::CLI => ['update', 'delete'],
        self::ADMIN => ['update', 'delete'],
        self::CUSTOMER => ['update', 'save']
    ];


    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'type',
        'subject',
        'body',
        'date_added',
        'date_modified',
    ];

    public function user() {

        return $this->hasOne(User::class, 'user_id', 'user_id');
    }

    public function customer() {
        return $this->hasOne(Customer::class, 'customer_id', 'customer_id');
    }

    public function getCustomerCommunications(int $customer_id, $data = []) {
        if (is_array($data) && !empty($data)) {
            return CustomerCommunication::where('customer_id', '=', $customer_id)
                ->offset($data['start'])->take($data['limit'])
                ->orderBy($data['sort'], $data['order'])
                ->get();
        } else {
            return CustomerCommunication::where('customer_id', '=', $customer_id)->get();
        }
    }

    public function getCustomerCommunicationById(int $communication_id) {
        $communication = CustomerCommunication::find($communication_id);
        return $communication->toArray();
    }

    public function createCustomerCommunication(AMail $mail) {
            $communication = new CustomerCommunication();
            $communication->subject = $mail->getSubject();
            $communication->body = $mail->getText() ? $mail->getText() : $mail->getHtml();
            $customers = Customer::where('email', '=', $mail->getTo())->limit(1)->get();
            foreach ($customers as $customer) {
                $customer_id = $customer->customer_id;
            }
            $communication->customer_id = $customer_id;
            $communication->user_id = $mail->getUser()->user_id ? $mail->getUser()->user_id : 0;
            $communication->type = 'email';
            $communication->save();
    }

    public function createCustomerCommunicationIm($customer_id, $message, $user_id=0){
        if ($this->config->get('config_save_customer_communication')) {
            $communication = new CustomerCommunication();
            $communication->subject = 'IM message';
            $communication->body = $message;
            $communication->customer_id = $customer_id;
            $communication->user_id = $user_id;
            $communication->type = 'sms';
            $communication->save();
        }
    }

}