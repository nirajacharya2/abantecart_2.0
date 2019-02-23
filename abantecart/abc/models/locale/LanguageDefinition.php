<?php

namespace abc\models\locale;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class LanguageDefinition
 *
 * @property int $language_definition_id
 * @property int $language_id
 * @property bool $section
 * @property string $block
 * @property string $language_key
 * @property string $language_value
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property Language $language
 *
 * @package abc\models
 */
class LanguageDefinition extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'language_definition_id';
    public $timestamps = false;

    protected $casts = [
        'language_id' => 'int',
        'section'     => 'bool',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'language_value',
        'date_added',
        'date_modified',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
