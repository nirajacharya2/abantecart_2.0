<?php

namespace abc\models\base;

use abc\models\BaseModel;

/**
 * Class OrderDownloadsHistory
 *
 * @property int $order_download_history_id
 * @property int $order_download_id
 * @property int $order_id
 * @property int $order_product_id
 * @property string $filename
 * @property string $mask
 * @property int $download_id
 * @property int $download_percent
 * @property \Carbon\Carbon $time
 *
 * @property OrderDownload $order_download
 * @property Download $download
 * @property Order $order
 * @property OrderProduct $order_product
 *
 * @package abc\models
 */
class OrderDownloadsHistory extends BaseModel
{
    protected $table = 'order_downloads_history';
    public $timestamps = false;

    protected $casts = [
        'order_download_id' => 'int',
        'order_id'          => 'int',
        'order_product_id'  => 'int',
        'download_id'       => 'int',
        'download_percent'  => 'int',
    ];

    protected $dates = [
        'time',
    ];

    protected $fillable = [
        'filename',
        'mask',
        'download_id',
        'download_percent',
        'time',
    ];

    public function order_download()
    {
        return $this->belongsTo(OrderDownload::class, 'order_download_id');
    }

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
}
