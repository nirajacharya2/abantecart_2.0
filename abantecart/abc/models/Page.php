<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcPage
 * 
 * @property int $page_id
 * @property int $parent_page_id
 * @property string $controller
 * @property string $key_param
 * @property string $key_value
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \Illuminate\Database\Eloquent\Collection $ac_page_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_pages_forms
 * @property \Illuminate\Database\Eloquent\Collection $ac_pages_layouts
 *
 * @package App\Models
 */
class AcPage extends Eloquent
{
	protected $primaryKey = 'page_id';
	public $timestamps = false;

	protected $casts = [
		'parent_page_id' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'parent_page_id',
		'controller',
		'key_param',
		'key_value',
		'date_added',
		'date_modified'
	];

	public function ac_page_descriptions()
	{
		return $this->hasMany(\App\Models\AcPageDescription::class, 'page_id');
	}

	public function ac_pages_forms()
	{
		return $this->hasMany(\App\Models\AcPagesForm::class, 'page_id');
	}

	public function ac_pages_layouts()
	{
		return $this->hasMany(\App\Models\AcPagesLayout::class, 'page_id');
	}
}
