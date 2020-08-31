<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Review
 *
 * @property int $review_id
 * @property int $product_id
 * @property int $customer_id
 * @property string $author
 * @property string $text
 * @property int $rating
 * @property int $status
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Product $product
 *
 * @package abc\models
 */
class Review extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'review_id';

    protected $casts = [
        'product_id'  => 'int',
        'customer_id' => 'int',
        'rating'      => 'int',
        'status'      => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'product_id',
        'customer_id',
        'author',
        'text',
        'rating',
        'status',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
