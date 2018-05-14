<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcOrderTotal
 * 
 * @property int $order_total_id
 * @property int $order_id
 * @property string $title
 * @property string $text
 * @property float $value
 * @property int $sort_order
 * @property string $type
 * @property string $key
 * 
 * @property \App\Models\AcOrder $ac_order
 *
 * @package App\Models
 */
class AcOrderTotal extends Eloquent
{
	protected $primaryKey = 'order_total_id';
	public $timestamps = false;

	protected $casts = [
		'order_id' => 'int',
		'value' => 'float',
		'sort_order' => 'int'
	];

	protected $fillable = [
		'order_id',
		'title',
		'text',
		'value',
		'sort_order',
		'type',
		'key'
	];

	public function ac_order()
	{
		return $this->belongsTo(\App\Models\AcOrder::class, 'order_id');
	}
}
