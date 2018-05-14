<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcEncryptionKey
 * 
 * @property int $key_id
 * @property string $key_name
 * @property int $status
 * @property string $comment
 *
 * @package App\Models
 */
class AcEncryptionKey extends Eloquent
{
	protected $primaryKey = 'key_id';
	public $timestamps = false;

	protected $casts = [
		'status' => 'int'
	];

	protected $fillable = [
		'key_name',
		'status',
		'comment'
	];
}
