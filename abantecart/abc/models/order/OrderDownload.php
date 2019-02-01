<?php

namespace abc\models\order;

use abc\models\BaseModel;
use abc\models\catalog\Download;

/**
 * Class OrderDownload
 *
 * @property int $order_download_id
 * @property int $order_id
 * @property int $order_product_id
 * @property string $name
 * @property string $filename
 * @property string $mask
 * @property int $download_id
 * @property int $status
 * @property int $remaining_count
 * @property int $percentage
 * @property \Carbon\Carbon $expire_date
 * @property int $sort_order
 * @property string $activate
 * @property int $activate_order_status_id
 * @property string $attributes_data
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property Download $download
 * @property Order $order
 * @property OrderProduct $order_product
 * @property \Illuminate\Database\Eloquent\Collection $order_downloads_histories
 *
 * @package abc\models
 */
class OrderDownload extends BaseModel
{
    protected $primaryKey = 'order_download_id';
    public $timestamps = false;

    protected $casts = [
        'order_id'                 => 'int',
        'order_product_id'         => 'int',
        'download_id'              => 'int',
        'status'                   => 'int',
        'remaining_count'          => 'int',
        'percentage'               => 'int',
        'sort_order'               => 'int',
        'activate_order_status_id' => 'int',
    ];

    protected $dates = [
        'expire_date',
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'order_id',
        'order_product_id',
        'name',
        'filename',
        'mask',
        'download_id',
        'status',
        'remaining_count',
        'percentage',
        'expire_date',
        'sort_order',
        'activate',
        'activate_order_status_id',
        'attributes_data',
        'date_added',
        'date_modified',
    ];

    public function download()
    {
        return $this->belongsTo(Download::class, 'download_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function order_product()
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id');
    }

    public function order_downloads_histories()
    {
        return $this->hasMany(OrderDownloadsHistory::class, 'order_download_id');
    }
}
