<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcOrderDownload
 * 
 * @property int $order_download_id
 * @property int $order_id
 * @property int $order_product_id
 * @property string $name
 * @property string $filename
 * @property string $mask
 * @property int $download_id
 * @property int $status
 * @property int $remaining_count
 * @property int $percentage
 * @property \Carbon\Carbon $expire_date
 * @property int $sort_order
 * @property string $activate
 * @property int $activate_order_status_id
 * @property string $attributes_data
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcDownload $ac_download
 * @property \App\Models\AcOrder $ac_order
 * @property \App\Models\AcOrderProduct $ac_order_product
 * @property \Illuminate\Database\Eloquent\Collection $ac_order_downloads_histories
 *
 * @package App\Models
 */
class AcOrderDownload extends Eloquent
{
	protected $primaryKey = 'order_download_id';
	public $timestamps = false;

	protected $casts = [
		'order_id' => 'int',
		'order_product_id' => 'int',
		'download_id' => 'int',
		'status' => 'int',
		'remaining_count' => 'int',
		'percentage' => 'int',
		'sort_order' => 'int',
		'activate_order_status_id' => 'int'
	];

	protected $dates = [
		'expire_date',
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'order_id',
		'order_product_id',
		'name',
		'filename',
		'mask',
		'download_id',
		'status',
		'remaining_count',
		'percentage',
		'expire_date',
		'sort_order',
		'activate',
		'activate_order_status_id',
		'attributes_data',
		'date_added',
		'date_modified'
	];

	public function ac_download()
	{
		return $this->belongsTo(\App\Models\AcDownload::class, 'download_id');
	}

	public function ac_order()
	{
		return $this->belongsTo(\App\Models\AcOrder::class, 'order_id');
	}

	public function ac_order_product()
	{
		return $this->belongsTo(\App\Models\AcOrderProduct::class, 'order_product_id');
	}

	public function ac_order_downloads_histories()
	{
		return $this->hasMany(\App\Models\AcOrderDownloadsHistory::class, 'order_download_id');
	}
}
