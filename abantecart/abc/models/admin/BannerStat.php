<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcBannerStat
 * 
 * @property int $rowid
 * @property int $banner_id
 * @property int $type
 * @property \Carbon\Carbon $time
 * @property int $store_id
 * @property string $user_info
 * 
 * @property \App\Models\AcBanner $ac_banner
 *
 * @package App\Models
 */
class AcBannerStat extends Eloquent
{
	protected $table = 'ac_banner_stat';
	protected $primaryKey = 'rowid';
	public $timestamps = false;

	protected $casts = [
		'banner_id' => 'int',
		'type' => 'int',
		'store_id' => 'int'
	];

	protected $dates = [
		'time'
	];

	protected $fillable = [
		'banner_id',
		'type',
		'time',
		'store_id',
		'user_info'
	];

	public function ac_banner()
	{
		return $this->belongsTo(\App\Models\AcBanner::class, 'banner_id');
	}
}
