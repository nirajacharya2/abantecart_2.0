<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcPagesForm
 * 
 * @property int $page_id
 * @property int $form_id
 * 
 * @property \App\Models\AcForm $ac_form
 * @property \App\Models\AcPage $ac_page
 *
 * @package App\Models
 */
class AcPagesForm extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'page_id' => 'int',
		'form_id' => 'int'
	];

	public function ac_form()
	{
		return $this->belongsTo(\App\Models\AcForm::class, 'form_id');
	}

	public function ac_page()
	{
		return $this->belongsTo(\App\Models\AcPage::class, 'page_id');
	}
}
