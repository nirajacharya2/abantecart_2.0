<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class AcOrderDownloadsHistory
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
 * @property \abc\models\AcOrderDownload $order_download
 * @property \abc\models\AcDownload $download
 * @property \abc\models\Order $order
 * @property \abc\models\AcOrderProduct $order_product
 *
 * @package abc\models
 */
class OrderDownloadsHistory extends AModelBase
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
        return $this->belongsTo(\abc\models\Order::class, 'order_id');
    }

    public function order_product()
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id');
    }
}
