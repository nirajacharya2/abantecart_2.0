<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcLayout
 * 
 * @property int $layout_id
 * @property string $template_id
 * @property string $layout_name
 * @property int $layout_type
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \Illuminate\Database\Eloquent\Collection $ac_pages_layouts
 *
 * @package App\Models
 */
class AcLayout extends Eloquent
{
	protected $primaryKey = 'layout_id';
	public $timestamps = false;

	protected $casts = [
		'layout_type' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'template_id',
		'layout_name',
		'layout_type',
		'date_added',
		'date_modified'
	];

	public function ac_pages_layouts()
	{
		return $this->hasMany(\App\Models\AcPagesLayout::class, 'layout_id');
	}
}
