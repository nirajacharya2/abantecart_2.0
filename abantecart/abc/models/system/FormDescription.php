<?php

namespace abc\models\system;

use abc\models\BaseModel;
use abc\models\locale\Language;

/**
 * Class FormDescription
 *
 * @property int $form_id
 * @property int $language_id
 * @property string $description
 *
 * @property Form $form
 * @property Language $language
 *
 * @package abc\models
 */
class FormDescription extends BaseModel
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'form_id'     => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'description',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
