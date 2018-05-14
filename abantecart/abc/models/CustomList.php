<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcCustomList
 * 
 * @property int $rowid
 * @property int $custom_block_id
 * @property string $data_type
 * @property int $id
 * @property int $sort_order
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcCustomBlock $ac_custom_block
 *
 * @package App\Models
 */
class AcCustomList extends Eloquent
{
	protected $primaryKey = 'rowid';
	public $timestamps = false;

	protected $casts = [
		'custom_block_id' => 'int',
		'id' => 'int',
		'sort_order' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'custom_block_id',
		'data_type',
		'id',
		'sort_order',
		'date_added',
		'date_modified'
	];

	public function ac_custom_block()
	{
		return $this->belongsTo(\App\Models\AcCustomBlock::class, 'custom_block_id');
	}
}
