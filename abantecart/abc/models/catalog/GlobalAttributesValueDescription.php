<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class GlobalAttributesValueDescription
 *
 * @property int $attribute_value_id
 * @property int $attribute_id
 * @property int $language_id
 * @property string $value
 *
 * @property GlobalAttribute $global_attribute
 * @property Language $language
 *
 * @package abc\models
 */
class GlobalAttributesValueDescription extends BaseModel
{
    use SoftDeletes;
    const DELETED_AT = 'date_deleted';
    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'attribute_value_id',
        'language_id'
    ];

    public $timestamps = false;

    protected $casts = [
        'attribute_value_id' => 'int',
        'attribute_id'       => 'int',
        'language_id'        => 'int',
    ];

    protected $fillable = [
        'value',
    ];

    public function global_attribute()
    {
        return $this->belongsTo(GlobalAttribute::class, 'attribute_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
