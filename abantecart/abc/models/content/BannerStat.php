<?php

namespace abc\models\content;

use abc\models\BaseModel;
use abc\models\layout\Banner;

/**
 * Class BannerStat
 *
 * @property int                  $rowid
 * @property int                  $banner_id
 * @property int                  $type
 * @property \Carbon\Carbon       $time
 * @property int                  $store_id
 * @property string               $user_info
 *
 * @property Banner $banner
 *
 * @package abc\models
 */
class BannerStat extends BaseModel
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
