<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2022 Belavier Commerce LLC
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

use abc\models\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CustomerNotification
 *
 * @property int $customer_id
 * @property string $sendpoint
 * @property string $protocol
 * @property int $status
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Customer $customer
 *
 * @method static CustomerNotification find(int $id) CustomerNotification
 * @method static CustomerNotification UpdateOrCreate(array $data) CustomerNotification
 * @method static CustomerNotification create(array $data) CustomerNotification
 *
 * @package abc\models
 */
class CustomerNotification extends BaseModel
{
    use SoftDeletes;

    protected $mainClassName = Customer::class;
    protected $mainClassKey = 'customer_id';

    protected $primaryKey = 'id';

    protected $casts = [
        'customer_id' => 'int',
        'status'      => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'customer_id',
        'sendpoint',
        'protocol',
        'status',
        'date_added',
        'date_modified',
    ];

    protected $touches = ['customer'];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
