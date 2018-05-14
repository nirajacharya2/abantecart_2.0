<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcCurrency
 * 
 * @property int $currency_id
 * @property string $title
 * @property string $code
 * @property string $symbol_left
 * @property string $symbol_right
 * @property string $decimal_place
 * @property float $value
 * @property int $status
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \Illuminate\Database\Eloquent\Collection $ac_orders
 *
 * @package App\Models
 */
class AcCurrency extends Eloquent
{
	protected $primaryKey = 'currency_id';
	public $timestamps = false;

	protected $casts = [
		'value' => 'float',
		'status' => 'int'
	];

	protected $dates = [
		'date_modified'
	];

	protected $fillable = [
		'title',
		'code',
		'symbol_left',
		'symbol_right',
		'decimal_place',
		'value',
		'status',
		'date_modified'
	];

	public function ac_orders()
	{
		return $this->hasMany(\App\Models\AcOrder::class, 'currency_id');
	}
}
