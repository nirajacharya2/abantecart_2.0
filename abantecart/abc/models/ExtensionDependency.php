<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcExtensionDependency
 * 
 * @property int $extension_id
 * @property int $extension_parent_id
 *
 * @package App\Models
 */
class AcExtensionDependency extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'extension_id' => 'int',
		'extension_parent_id' => 'int'
	];
}
