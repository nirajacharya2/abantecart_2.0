<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcBlock
 * 
 * @property int $block_id
 * @property string $block_txt_id
 * @property string $controller
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \Illuminate\Database\Eloquent\Collection $ac_block_templates
 * @property \Illuminate\Database\Eloquent\Collection $ac_custom_blocks
 *
 * @package App\Models
 */
class AcBlock extends Eloquent
{
	protected $primaryKey = 'block_id';
	public $timestamps = false;

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'block_txt_id',
		'controller',
		'date_added',
		'date_modified'
	];

	public function ac_block_templates()
	{
		return $this->hasMany(\App\Models\AcBlockTemplate::class, 'block_id');
	}

	public function ac_custom_blocks()
	{
		return $this->hasMany(\App\Models\AcCustomBlock::class, 'block_id');
	}
}
