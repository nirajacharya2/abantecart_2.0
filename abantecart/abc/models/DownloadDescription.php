<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcDownloadDescription
 * 
 * @property int $download_id
 * @property int $language_id
 * @property string $name
 * 
 * @property \App\Models\AcDownload $ac_download
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcDownloadDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'download_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'name'
	];

	public function ac_download()
	{
		return $this->belongsTo(\App\Models\AcDownload::class, 'download_id');
	}

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
