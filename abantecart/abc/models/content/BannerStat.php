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

namespace abc\models\content;

use abc\models\BaseModel;
use abc\models\layout\Banner;
use Carbon\Carbon;

/**
 * Class BannerStat
 *
 * @property int $rowid
 * @property int $banner_id
 * @property int $type
 * @property Carbon $time
 * @property int $store_id
 * @property string $user_info
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
