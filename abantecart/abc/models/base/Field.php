<?php

namespace abc\models\base;

use abc\models\BaseModel;

/**
 * Class Field
 *
 * @property int $field_id
 * @property int $form_id
 * @property string $field_name
 * @property string $element_type
 * @property int $sort_order
 * @property string $attributes
 * @property string $settings
 * @property string $required
 * @property int $status
 * @property string $regexp_pattern
 *
 * @property Form $form
 * @property \Illuminate\Database\Eloquent\Collection $field_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $field_values
 * @property FieldsGroup $fields_group
 *
 * @package abc\models
 */
class Field extends BaseModel
{
    protected $primaryKey = 'field_id';
    public $timestamps = false;

    protected $casts = [
        'form_id'    => 'int',
        'sort_order' => 'int',
        'status'     => 'int',
    ];

    protected $fillable = [
        'form_id',
        'field_name',
        'element_type',
        'sort_order',
        'attributes',
        'settings',
        'required',
        'status',
        'regexp_pattern',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function field_descriptions()
    {
        return $this->hasMany(FieldDescription::class, 'field_id');
    }

    public function field_values()
    {
        return $this->hasMany(FieldValue::class, 'field_id');
    }

    public function fields_group()
    {
        return $this->hasOne(FieldsGroup::class, 'field_id');
    }
}
