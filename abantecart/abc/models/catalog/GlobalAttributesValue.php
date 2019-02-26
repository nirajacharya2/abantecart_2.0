<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class GlobalAttributesValue
 *
 * @property int $attribute_value_id
 * @property int $attribute_id
 * @property int $sort_order
 *
 * @property GlobalAttribute $global_attribute
 *
 * @package abc\models
 */
class GlobalAttributesValue extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions'];
    protected $primaryKey = 'attribute_value_id';
    public $timestamps = false;

    protected $casts = [
        'attribute_id' => 'int',
        'sort_order'   => 'int',
    ];

    protected $fillable = [
        'attribute_id',
        'sort_order',
    ];

    public function attribute()
    {
        return $this->belongsTo(GlobalAttribute::class, 'attribute_value_id');
    }

    public function description()
    {
        return $this->hasMany(GlobalAttributesValueDescription::class, 'attribute_value_id')
                    ->where('language_id', $this->registry->get('language')->getContentLanguageID())->first();

    }

    public function descriptions()
    {
        return $this->hasMany(GlobalAttributesValueDescription::class, 'attribute_value_id');
    }
}
