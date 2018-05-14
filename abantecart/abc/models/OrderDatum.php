<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcOrderDatum
 *
 * @property int                         $order_id
 * @property int                         $type_id
 * @property string                      $data
 * @property \Carbon\Carbon              $date_added
 * @property \Carbon\Carbon              $date_modified
 *
 * @property \abc\models\Order           $order
 * @property \abc\models\AcOrderDataType $order_data_type
 *
 * @package abc\models
 */
class OrderDatum extends AModelBase
{
    public $incrementing = false;
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
        return $this->belongsTo(\abc\models\Order::class, 'order_id');
    }

    public function order_data_type()
    {
        return $this->belongsTo(OrderDataType::class, 'type_id');
    }
}
