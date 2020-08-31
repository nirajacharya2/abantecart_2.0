<?php

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
