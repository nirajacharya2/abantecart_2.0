<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcPageDescription
 * 
 * @property int $page_id
 * @property int $language_id
 * @property string $name
 * @property string $title
 * @property string $seo_url
 * @property string $keywords
 * @property string $description
 * @property string $content
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcPage $ac_page
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcPageDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'page_id' => 'int',
		'language_id' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'name',
		'title',
		'seo_url',
		'keywords',
		'description',
		'content',
		'date_added',
		'date_modified'
	];

	public function ac_page()
	{
		return $this->belongsTo(\App\Models\AcPage::class, 'page_id');
	}

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
