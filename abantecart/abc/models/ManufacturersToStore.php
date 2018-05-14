<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcManufacturersToStore
 * 
 * @property int $manufacturer_id
 * @property int $store_id
 * 
 * @property \App\Models\AcManufacturer $ac_manufacturer
 * @property \App\Models\AcStore $ac_store
 *
 * @package App\Models
 */
class AcManufacturersToStore extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'manufacturer_id' => 'int',
		'store_id' => 'int'
	];

	public function ac_manufacturer()
	{
		return $this->belongsTo(\App\Models\AcManufacturer::class, 'manufacturer_id');
	}

	public function ac_store()
	{
		return $this->belongsTo(\App\Models\AcStore::class, 'store_id');
	}
}
