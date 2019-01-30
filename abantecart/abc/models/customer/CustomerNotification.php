<?php

namespace abc\models\customer;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CustomerNotification
 *
 * @property int $customer_id
 * @property string $sendpoint
 * @property string $protocol
 * @property int $status
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property Customer $customer
 *
 * @package abc\models
 */
class CustomerNotification extends BaseModel
{
    use SoftDeletes;
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'customer_id' => 'int',
        'status'      => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'status',
        'date_added',
        'date_modified',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
