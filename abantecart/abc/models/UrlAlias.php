<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcUrlAlias
 * 
 * @property int $url_alias_id
 * @property string $query
 * @property string $keyword
 * @property int $language_id
 * 
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcUrlAlias extends Eloquent
{
	protected $primaryKey = 'url_alias_id';
	public $timestamps = false;

	protected $casts = [
		'language_id' => 'int'
	];

	protected $fillable = [
		'query',
		'keyword',
		'language_id'
	];

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
