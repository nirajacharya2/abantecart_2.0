<?php

namespace abc\models\order;

use abc\models\BaseModel;
use abc\models\locale\Language;

/**
 * Class OrderDataType
 *
 * @property int $type_id
 * @property int $language_id
 * @property string $name
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property Language $language
 * @property \Illuminate\Database\Eloquent\Collection $order_data
 *
 * @package abc\models
 */
class OrderDataType extends BaseModel
{
    protected $primaryKey = 'type_id';
    public $timestamps = false;

    protected $casts = [
        'language_id' => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'language_id',
        'name',
        'date_added',
        'date_modified',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    public function order_data()
    {
        return $this->hasMany(OrderDatum::class, 'type_id');
    }
}
