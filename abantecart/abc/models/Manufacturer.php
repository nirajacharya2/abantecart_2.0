<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcManufacturer
 * 
 * @property int $manufacturer_id
 * @property string $name
 * @property int $sort_order
 * 
 * @property \Illuminate\Database\Eloquent\Collection $ac_manufacturers_to_stores
 *
 * @package App\Models
 */
class AcManufacturer extends Eloquent
{
	protected $primaryKey = 'manufacturer_id';
	public $timestamps = false;

	protected $casts = [
		'sort_order' => 'int'
	];

	protected $fillable = [
		'name',
		'sort_order'
	];

	public function ac_manufacturers_to_stores()
	{
		return $this->hasMany(\App\Models\AcManufacturersToStore::class, 'manufacturer_id');
	}
}
