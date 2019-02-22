<?php

namespace abc\models\system;

use abc\models\BaseModel;
use abc\models\layout\PagesForm;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Form
 *
 * @property int $form_id
 * @property string $form_name
 * @property string $controller
 * @property string $success_page
 * @property int $status
 *
 * @property \Illuminate\Database\Eloquent\Collection $fields
 * @property \Illuminate\Database\Eloquent\Collection $form_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $form_groups
 * @property \Illuminate\Database\Eloquent\Collection $pages_forms
 *
 * @package abc\models
 */
class Form extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;
    const DELETED_AT = 'date_deleted';
    protected $cascadeDeletes = ['fields','descriptions', 'groups', 'pages'];

    protected $primaryKey = 'form_id';
    public $timestamps = false;

    protected $casts = [
        'status' => 'int',
    ];

    protected $fillable = [
        'form_name',
        'controller',
        'success_page',
        'status',
    ];

    public function fields()
    {
        return $this->hasMany(Field::class, 'form_id');
    }

    public function descriptions()
    {
        return $this->hasMany(FormDescription::class, 'form_id');
    }

    public function groups()
    {
        return $this->hasMany(FormGroup::class, 'form_id');
    }

    public function pages()
    {
        return $this->hasMany(PagesForm::class, 'form_id');
    }
}
