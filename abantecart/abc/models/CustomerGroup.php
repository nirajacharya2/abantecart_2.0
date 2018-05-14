<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcCustomerGroup
 * 
 * @property int $customer_group_id
 * @property string $name
 * @property bool $tax_exempt
 *
 * @package App\Models
 */
class AcCustomerGroup extends Eloquent
{
	protected $primaryKey = 'customer_group_id';
	public $timestamps = false;

	protected $casts = [
		'tax_exempt' => 'bool'
	];

	protected $fillable = [
		'name',
		'tax_exempt'
	];
}
