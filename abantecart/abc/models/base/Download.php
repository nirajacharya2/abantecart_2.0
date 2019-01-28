<?php

namespace abc\models\base;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Download
 *
 * @property int $download_id
 * @property string $filename
 * @property string $mask
 * @property int $max_downloads
 * @property int $expire_days
 * @property int $sort_order
 * @property string $activate
 * @property int $activate_order_status_id
 * @property int $shared
 * @property int $status
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property \Illuminate\Database\Eloquent\Collection $download_attribute_values
 * @property \Illuminate\Database\Eloquent\Collection $download_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $order_downloads
 * @property \Illuminate\Database\Eloquent\Collection $order_downloads_histories
 * @property \Illuminate\Database\Eloquent\Collection $products_to_downloads
 *
 * @package abc\models
 */
class Download extends BaseModel
{
    use SoftDeletes;
    protected $primaryKey = 'download_id';
    public $timestamps = false;

    protected $casts = [
        'max_downloads'            => 'int',
        'expire_days'              => 'int',
        'sort_order'               => 'int',
        'activate_order_status_id' => 'int',
        'shared'                   => 'int',
        'status'                   => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'filename',
        'mask',
        'max_downloads',
        'expire_days',
        'sort_order',
        'activate',
        'activate_order_status_id',
        'shared',
        'status',
        'date_added',
        'date_modified',
    ];

    public function download_attribute_values()
    {
        return $this->hasMany(DownloadAttributeValue::class, 'download_id');
    }

    public function download_descriptions()
    {
        return $this->hasMany(DownloadDescription::class, 'download_id');
    }

    public function order_downloads()
    {
        return $this->hasMany(OrderDownload::class, 'download_id');
    }

    public function order_downloads_histories()
    {
        return $this->hasMany(OrderDownloadsHistory::class, 'download_id');
    }

}
