<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class GlobalAttributesDescription
 *
 * @property int $attribute_id
 * @property int $language_id
 * @property string $name
 * @property string $placeholder
 * @property string $error_text
 *
 * @property GlobalAttribute $global_attribute
 * @property Language $language
 *
 * @package abc\models
 */
class GlobalAttributesDescription extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'attribute_id',
        'language_id',
    ];
    public $timestamps = false;

    protected $casts = [
        'attribute_id' => 'int',
        'language_id'  => 'int',
    ];

    protected $fillable = [
        'name',
        'placeholder',
        'error_text',
    ];

    public function attribute()
    {
        return $this->belongsTo(GlobalAttribute::class, 'attribute_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
