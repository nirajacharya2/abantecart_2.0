<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcBlockTemplate
 * 
 * @property int $block_id
 * @property int $parent_block_id
 * @property string $template
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcBlock $ac_block
 *
 * @package App\Models
 */
class AcBlockTemplate extends Eloquent
{
	public $timestamps = false;

	protected $casts = [
		'parent_block_id' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'template',
		'date_added',
		'date_modified'
	];

	public function ac_block()
	{
		return $this->belongsTo(\App\Models\AcBlock::class, 'block_id');
	}
}
