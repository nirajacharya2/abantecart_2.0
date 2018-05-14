<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcReview
 * 
 * @property int $review_id
 * @property int $product_id
 * @property int $customer_id
 * @property string $author
 * @property string $text
 * @property int $rating
 * @property int $status
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcProduct $ac_product
 *
 * @package App\Models
 */
class AcReview extends Eloquent
{
	protected $primaryKey = 'review_id';
	public $timestamps = false;

	protected $casts = [
		'product_id' => 'int',
		'customer_id' => 'int',
		'rating' => 'int',
		'status' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'product_id',
		'customer_id',
		'author',
		'text',
		'rating',
		'status',
		'date_added',
		'date_modified'
	];

	public function ac_product()
	{
		return $this->belongsTo(\App\Models\AcProduct::class, 'product_id');
	}
}
