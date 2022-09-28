<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2022 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */
namespace abc\models\layout;

use abc\models\BaseModel;
use abc\models\content\BannerStat;
use Carbon\Carbon;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Banner
 *
 * @property int $banner_id
 * @property int $status
 * @property int $banner_type
 * @property string $banner_group_name
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property bool $blank
 * @property string $target_url
 * @property int $sort_order
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Collection $banner_descriptions
 * @property Collection $banner_stats
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
        'status'        => 'int',
        'banner_type'   => 'int',
        'blank'         => 'bool',
        'sort_order'    => 'int',
        'start_date'    => 'datetime',
        'end_date'      => 'datetime',
        'date_added'    => 'datetime',
        'date_modified' => 'datetime'
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

    /**
     * @return HasOne
     */
    public function description()
    {
        return $this->hasOne(BannerDescription::class, 'banner_id', 'banner_id')
            ->where('language_id', '=', static::$current_language_id);
    }

    /**
     * @return HasMany
     */
    public function descriptions()
    {
        return $this->hasMany(BannerDescription::class, 'banner_id');
    }

    public function stats()
    {
        return $this->hasMany(BannerStat::class, 'banner_id');
    }
}
