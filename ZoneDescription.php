<?php

namespace abc\models\locale;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ZoneDescription
 *
 * @property int $zone_id
 * @property int $language_id
 * @property string $name
 *
 * @property Zone $zone
 * @property Language $language
 *
 * @package abc\models
 */
class ZoneDescription extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'zone_id',
        'language_id',
    ];

    protected $touches = ['zone'];
    protected $casts = [
        'zone_id'     => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'name',
    ];
    protected $rules=[
        'name'=>[
            'checks'=>[
                'string',
                'between:2,128'
            ],
            'messages'=>[
                'language_key'=> 'error_name',
                'language_block'=>'localisation/zone',
                'default_text'=>'Name must be between 2 and 128 characters!',
                'section'=>'admin'
            ]
        ]
    ];
    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
