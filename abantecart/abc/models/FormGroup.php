<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class FormGroup
 *
 * @property int                                      $group_id
 * @property string                                   $group_name
 * @property int                                      $form_id
 * @property int                                      $sort_order
 * @property int                                      $status
 *
 * @property \abc\models\Form                         $form
 * @property \Illuminate\Database\Eloquent\Collection $fields_group_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $fields_groups
 *
 * @package abc\models
 */
class FormGroup extends AModelBase
{
    protected $primaryKey = 'group_id';
    public $timestamps = false;

    protected $casts = [
        'form_id'    => 'int',
        'sort_order' => 'int',
        'status'     => 'int',
    ];

    protected $fillable = [
        'group_name',
        'form_id',
        'sort_order',
        'status',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function fields_group_descriptions()
    {
        return $this->hasMany(FieldsGroupDescription::class, 'group_id');
    }

    public function fields_groups()
    {
        return $this->hasMany(FieldsGroup::class, 'group_id');
    }
}
