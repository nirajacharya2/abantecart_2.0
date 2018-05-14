<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcPagesLayout
 * 
 * @property int $layout_id
 * @property int $page_id
 * 
 * @property \App\Models\AcLayout $ac_layout
 * @property \App\Models\AcPage $ac_page
 *
 * @package App\Models
 */
class AcPagesLayout extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'layout_id' => 'int',
		'page_id' => 'int'
	];

	public function ac_layout()
	{
		return $this->belongsTo(\App\Models\AcLayout::class, 'layout_id');
	}

	public function ac_page()
	{
		return $this->belongsTo(\App\Models\AcPage::class, 'page_id');
	}
}
