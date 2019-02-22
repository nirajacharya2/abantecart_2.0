<?php

namespace abc\models\order;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class OrderDatum
 *
 * @property int $order_id
 * @property int $type_id
 * @property string $data
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property Order $order
 * @property OrderDataType $order_data_type
 *
 * @package abc\models
 */
class OrderDatum extends BaseModel
{
    use SoftDeletes;
    const DELETED_AT = 'date_deleted';

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'order_id',
        'type_id'
    ];

    public $timestamps = false;

    protected $casts = [
        'order_id' => 'int',
        'type_id'  => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'data',
        'date_added',
        'date_modified',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function order_data_type()
    {
        return $this->belongsTo(OrderDataType::class, 'type_id');
    }
}
