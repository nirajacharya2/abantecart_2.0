<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcDownload
 * 
 * @property int $download_id
 * @property string $filename
 * @property string $mask
 * @property int $max_downloads
 * @property int $expire_days
 * @property int $sort_order
 * @property string $activate
 * @property int $activate_order_status_id
 * @property int $shared
 * @property int $status
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \Illuminate\Database\Eloquent\Collection $ac_download_attribute_values
 * @property \Illuminate\Database\Eloquent\Collection $ac_download_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_order_downloads
 * @property \Illuminate\Database\Eloquent\Collection $ac_order_downloads_histories
 * @property \Illuminate\Database\Eloquent\Collection $ac_products_to_downloads
 *
 * @package App\Models
 */
class AcDownload extends Eloquent
{
	protected $primaryKey = 'download_id';
	public $timestamps = false;

	protected $casts = [
		'max_downloads' => 'int',
		'expire_days' => 'int',
		'sort_order' => 'int',
		'activate_order_status_id' => 'int',
		'shared' => 'int',
		'status' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'filename',
		'mask',
		'max_downloads',
		'expire_days',
		'sort_order',
		'activate',
		'activate_order_status_id',
		'shared',
		'status',
		'date_added',
		'date_modified'
	];

	public function ac_download_attribute_values()
	{
		return $this->hasMany(\App\Models\AcDownloadAttributeValue::class, 'download_id');
	}

	public function ac_download_descriptions()
	{
		return $this->hasMany(\App\Models\AcDownloadDescription::class, 'download_id');
	}

	public function ac_order_downloads()
	{
		return $this->hasMany(\App\Models\AcOrderDownload::class, 'download_id');
	}

	public function ac_order_downloads_histories()
	{
		return $this->hasMany(\App\Models\AcOrderDownloadsHistory::class, 'download_id');
	}

	public function ac_products_to_downloads()
	{
		return $this->hasMany(\App\Models\AcProductsToDownload::class, 'download_id');
	}
}
