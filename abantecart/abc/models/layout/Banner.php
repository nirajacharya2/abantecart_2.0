<?php

namespace abc\models\layout;

use abc\models\BaseModel;
use abc\models\content\BannerStat;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Banner
 *
 * @property int $banner_id
 * @property int $status
 * @property int $banner_type
 * @property string $banner_group_name
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon $end_date
 * @property bool $blank
 * @property string $target_url
 * @property int $sort_order
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property \Illuminate\Database\Eloquent\Collection $banner_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $banner_stats
 *
 * @package abc\models
 */
class Banner extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;
    protected $cascadeDeletes = ['descriptions', 'stats'];
    protected $primaryKey = 'banner_id';
    public $timestamps = false;

    protected $casts = [
        'status'      => 'int',
        'banner_type' => 'int',
        'blank'       => 'bool',
        'sort_order'  => 'int',
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'status',
        'banner_type',
        'banner_group_name',
        'start_date',
        'end_date',
        'blank',
        'target_url',
        'sort_order',
        'date_added',
        'date_modified',
    ];

    public function descriptions()
    {
        return $this->hasMany(BannerDescription::class, 'banner_id');
    }

    public function stats()
    {
        return $this->hasMany(BannerStat::class, 'banner_id');
    }
}
