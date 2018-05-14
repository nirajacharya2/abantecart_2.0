<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcProductsToDownload
 * 
 * @property int $product_id
 * @property int $download_id
 * 
 * @property \App\Models\AcProduct $ac_product
 * @property \App\Models\AcDownload $ac_download
 *
 * @package App\Models
 */
class AcProductsToDownload extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'product_id' => 'int',
		'download_id' => 'int'
	];

	public function ac_product()
	{
		return $this->belongsTo(\App\Models\AcProduct::class, 'product_id');
	}

	public function ac_download()
	{
		return $this->belongsTo(\App\Models\AcDownload::class, 'download_id');
	}
}
