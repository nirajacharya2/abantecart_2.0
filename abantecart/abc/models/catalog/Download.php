<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use abc\models\order\OrderDownload;
use abc\models\order\OrderDownloadsHistory;
use Carbon\Carbon;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Collection;
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
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Collection $download_attribute_values
 * @property DownloadDescription $description
 * @property DownloadDescription $descriptions
 * @property Collection $order_downloads
 * @property Collection $order_downloads_histories
 * @property Collection $products_to_downloads
 *
 * @package abc\models
 */
class Download extends BaseModel
{
    protected $cascadeDeletes = ['attribute_values', 'descriptions'];

    protected $primaryKey = 'download_id';

    protected $casts = [
        'max_downloads'            => 'int',
        'expire_days'              => 'int',
        'sort_order'               => 'int',
        'activate_order_status_id' => 'int',
        'shared'                   => 'int',
        'status' => 'int'
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
        'status'
    ];

    public function attribute_values()
    {
        return $this->hasMany(DownloadAttributeValue::class, 'download_id');
    }

    public function description()
    {
        return $this->hasOne(DownloadDescription::class, 'download_id', 'download_id')
            ->where('language_id', '=', static::$current_language_id);
    }

    public function descriptions()
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
