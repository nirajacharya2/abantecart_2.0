<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class PagesForm
 *
 * @property int              $page_id
 * @property int              $form_id
 *
 * @property \abc\models\Form $form
 * @property \abc\models\Page $page
 *
 * @package abc\models
 */
class PagesForm extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'page_id' => 'int',
        'form_id' => 'int',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function page()
    {
        return $this->belongsTo(Page::class, 'page_id');
    }
}
