<?php

namespace abc\models\layout;

use abc\models\BaseModel;
use abc\models\system\Form;

/**
 * Class PagesForm
 *
 * @property int $page_id
 * @property int $form_id
 *
 * @property Form $form
 * @property Page $page
 *
 * @package abc\models
 */
class PagesForm extends BaseModel
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
