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

namespace abc\models\customer;

use abc\core\engine\Registry;
use abc\core\lib\AMail;
use abc\models\BaseModel;
use abc\models\user\User;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CustomerCommunication
 *
 * @property  int customer_id
 * @property  int user_id
 * @property  string $type
 * @property  string $subject
 * @property  string $body
 * @property  string $sent_to_address
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @method static CustomerCommunication find(int $communication_id) CustomerCommunication
 *
 * @package abc\models\customer
 */
class CustomerCommunication extends BaseModel
{
    use SoftDeletes;
    protected $table = 'customer_communications';

    protected $primaryKey = 'communication_id';

    protected $mainClassName = Customer::class;
    protected $mainClassKey = 'customer_id';

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'customer_id',
        'user_id',
        'type',
        'subject',
        'body',
        'sent_to_address',
        'date_added',
        'date_modified',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'user_id', 'user_id');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'customer_id', 'customer_id');
    }

    public static function getCustomerCommunications(int $customer_id, $data = [])
    {
        if (is_array($data) && !empty($data)) {
            return CustomerCommunication::where('customer_id', '=', $customer_id)
                                        ->offset($data['start'])->take($data['limit'])
                                        ->orderBy($data['sort'], $data['order'])
                                        ->get();
        } else {
            return CustomerCommunication::where('customer_id', '=', $customer_id)->get();
        }
    }

    public static function getCustomerCommunicationById(int $communication_id)
    {
        if ($communication_id > 0) {
            $communication = CustomerCommunication::find($communication_id);
            if ($communication) {
                return $communication->toArray();
            } else {
                return [];
            }
        } else {
            return [];
        }
    }

    public static function createCustomerCommunication(AMail $mail)
    {
        /**
         * @var CustomerCommunication $communication
         */
        $communication = new CustomerCommunication();
        $communication->subject = $mail->getSubject();
        $communication->body = $mail->getHtml() ? $mail->getHtml() : nl2br($mail->getText());
        $customer_id = null;
        if (Registry::customer()) {
            $customer_id = Registry::customer()->getId();
        }
        if (!$customer_id) {
            $customer = Customer::where('email', '=', $mail->getTo())->limit(1)->get()->first();
            $customer_id = $customer->customer_id;
        }
        if (!$customer_id) {
            return;
        }
        $communication->customer_id = $customer_id;


        $communication->user_id = null;
        $user = Registry::user();
        if($user && $user->getId()){
           $communication->user_id = $user->getId();
        }
        $communication->type = 'email';
        $communication->sent_to_address = $mail->getTo();
        Registry::extensions()->hk_extendQuery(new static,__FUNCTION__, $communication, $mail);
        $communication->save();
    }

    /**
     * @param $customer_id
     * @param $to
     * @param $message
     * @param int $user_id
     * @param string $protocol
     *
     * @throws \Exception
     */
    public static function createCustomerCommunicationIm($customer_id, $to, $message, $user_id = 0, $protocol = 'sms')
    {
        $communication = new CustomerCommunication();
        $communication->subject = 'IM message';
        $communication->body = $message;
        if (!$customer_id) {
            return;
        }
        $communication->customer_id = $customer_id;
        $communication->user_id = (int)$user_id ?: null;
        $communication->sent_to_address = $to;
        $communication->type = $protocol;
        $communication->save();
    }

}
