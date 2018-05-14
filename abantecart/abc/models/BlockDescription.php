<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcBlockDescription
 * 
 * @property int $block_description_id
 * @property int $custom_block_id
 * @property int $language_id
 * @property string $block_wrapper
 * @property bool $block_framed
 * @property string $name
 * @property string $title
 * @property string $description
 * @property string $content
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcCustomBlock $ac_custom_block
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcBlockDescription extends Eloquent
{
	public $timestamps = false;

	protected $casts = [
		'custom_block_id' => 'int',
		'language_id' => 'int',
		'block_framed' => 'bool'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'block_wrapper',
		'block_framed',
		'name',
		'title',
		'description',
		'content',
		'date_added',
		'date_modified'
	];

	public function ac_custom_block()
	{
		return $this->belongsTo(\App\Models\AcCustomBlock::class, 'custom_block_id');
	}

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
