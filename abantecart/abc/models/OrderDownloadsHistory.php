<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcOrderDownloadsHistory
 * 
 * @property int $order_download_history_id
 * @property int $order_download_id
 * @property int $order_id
 * @property int $order_product_id
 * @property string $filename
 * @property string $mask
 * @property int $download_id
 * @property int $download_percent
 * @property \Carbon\Carbon $time
 * 
 * @property \App\Models\AcOrderDownload $ac_order_download
 * @property \App\Models\AcDownload $ac_download
 * @property \App\Models\AcOrder $ac_order
 * @property \App\Models\AcOrderProduct $ac_order_product
 *
 * @package App\Models
 */
class AcOrderDownloadsHistory extends Eloquent
{
	protected $table = 'ac_order_downloads_history';
	public $timestamps = false;

	protected $casts = [
		'order_download_id' => 'int',
		'order_id' => 'int',
		'order_product_id' => 'int',
		'download_id' => 'int',
		'download_percent' => 'int'
	];

	protected $dates = [
		'time'
	];

	protected $fillable = [
		'filename',
		'mask',
		'download_id',
		'download_percent',
		'time'
	];

	public function ac_order_download()
	{
		return $this->belongsTo(\App\Models\AcOrderDownload::class, 'order_download_id');
	}

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
}
