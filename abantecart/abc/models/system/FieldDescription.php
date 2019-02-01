<?php

namespace abc\models\system;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class FieldDescription
 *
 * @property int $field_id
 * @property string $name
 * @property string $description
 * @property int $language_id
 * @property string $error_text
 *
 * @property Field $field
 * @property Language $language
 *
 * @package abc\models
 */
class FieldDescription extends BaseModel
{
    use SoftDeletes;
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'field_id'    => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'name',
        'description',
        'error_text',
    ];

    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
