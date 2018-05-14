<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcDownloadAttributeValue
 * 
 * @property int $download_attribute_id
 * @property int $attribute_id
 * @property int $download_id
 * @property string $attribute_value_ids
 * 
 * @property \App\Models\AcDownload $ac_download
 *
 * @package App\Models
 */
class AcDownloadAttributeValue extends Eloquent
{
	protected $primaryKey = 'download_attribute_id';
	public $timestamps = false;

	protected $casts = [
		'attribute_id' => 'int',
		'download_id' => 'int'
	];

	protected $fillable = [
		'attribute_id',
		'download_id',
		'attribute_value_ids'
	];

	public function ac_download()
	{
		return $this->belongsTo(\App\Models\AcDownload::class, 'download_id');
	}
}
