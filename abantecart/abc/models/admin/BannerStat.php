<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcBannerStat
 *
 * @property int                  $rowid
 * @property int                  $banner_id
 * @property int                  $type
 * @property \Carbon\Carbon       $time
 * @property int                  $store_id
 * @property string               $user_info
 *
 * @property \abc\models\AcBanner $banner
 *
 * @package abc\models
 */
class BannerStat extends AModelBase
{
    protected $table = 'banner_stat';
    protected $primaryKey = 'rowid';
    public $timestamps = false;

    protected $casts = [
        'banner_id' => 'int',
        'type'      => 'int',
        'store_id'  => 'int',
    ];

    protected $dates = [
        'time',
    ];

    protected $fillable = [
        'banner_id',
        'type',
        'time',
        'store_id',
        'user_info',
    ];

    public function banner()
    {
        return $this->belongsTo(Banner::class, 'banner_id');
    }
}
