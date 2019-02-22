<?php

namespace abc\models\layout;

use abc\models\BaseModel;
use abc\models\system\Form;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'page_id',
        'form_id'
    ];
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
