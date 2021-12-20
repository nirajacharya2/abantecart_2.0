<?php

namespace abc\models\order;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['order_data'];

    protected $primaryKey = 'type_id';
    protected $casts = [
        'language_id' => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'type_id',
        'language_id',
        'name',
    ];

    protected $rules = [
        /** @see validate() */
        'type_id'     => [
            'checks'   => [
                'integer',
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],
        'language_id' => [
            'checks'   => [
                'integer',
                'required',
                'exists:languages',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],
        'name'        => [
            'checks'   => [
                'string',
                'max:64',
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    public function order_data()
    {
        return $this->HasMany(OrderDatum::class, 'type_id');
    }
}
