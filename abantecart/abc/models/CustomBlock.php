<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcCustomBlock
 * 
 * @property int $custom_block_id
 * @property int $block_id
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcBlock $ac_block
 * @property \Illuminate\Database\Eloquent\Collection $ac_block_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_custom_lists
 *
 * @package App\Models
 */
class AcCustomBlock extends Eloquent
{
	public $timestamps = false;

	protected $casts = [
		'block_id' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'date_added',
		'date_modified'
	];

	public function ac_block()
	{
		return $this->belongsTo(\App\Models\AcBlock::class, 'block_id');
	}

	public function ac_block_descriptions()
	{
		return $this->hasMany(\App\Models\AcBlockDescription::class, 'custom_block_id');
	}

	public function ac_custom_lists()
	{
		return $this->hasMany(\App\Models\AcCustomList::class, 'custom_block_id');
	}
}
