<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcOrderHistory
 *
 * @property int                       $order_history_id
 * @property int                       $order_id
 * @property int                       $order_status_id
 * @property int                       $notify
 * @property string                    $comment
 * @property \Carbon\Carbon            $date_added
 * @property \Carbon\Carbon            $date_modified
 *
 * @property \abc\models\AcOrderStatus $order_status
 *
 * @package abc\models
 */
class OrderHistory extends AModelBase
{
    protected $table = 'order_history';
    protected $primaryKey = 'order_history_id';
    public $timestamps = false;

    protected $casts = [
        'order_id'        => 'int',
        'order_status_id' => 'int',
        'notify'          => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'order_id',
        'order_status_id',
        'notify',
        'comment',
        'date_added',
        'date_modified',
    ];

    public function order_status()
    {
        return $this->belongsTo(OrderStatus::class, 'order_status_id');
    }
}
