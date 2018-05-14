<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcBannerDescription
 * 
 * @property int $banner_id
 * @property int $language_id
 * @property string $name
 * @property string $description
 * @property string $meta
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcBanner $ac_banner
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcBannerDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'banner_id' => 'int',
		'language_id' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'name',
		'description',
		'meta',
		'date_added',
		'date_modified'
	];

	public function ac_banner()
	{
		return $this->belongsTo(\App\Models\AcBanner::class, 'banner_id');
	}

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
