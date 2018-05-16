<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class AcFieldsGroupDescription
 *
 * @property int $group_id
 * @property string $name
 * @property string $description
 * @property int $language_id
 *
 * @property \abc\models\AcFormGroup $form_group
 * @property \abc\models\AcLanguage $language
 *
 * @package abc\models
 */
class FieldsGroupDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'group_id'    => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'name',
        'description',
    ];

    public function form_group()
    {
        return $this->belongsTo(FormGroup::class, 'group_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
