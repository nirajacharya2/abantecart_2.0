<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class FormDescription
 *
 * @property int $form_id
 * @property int $language_id
 * @property string $description
 *
 * @property \abc\models\Form $form
 * @property \abc\models\Language $language
 *
 * @package abc\models
 */
class FormDescription extends AModelBase
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
