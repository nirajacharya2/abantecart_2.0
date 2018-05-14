<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcFieldsGroup
 *
 * @property int                     $field_id
 * @property int                     $group_id
 * @property int                     $sort_order
 *
 * @property \abc\models\AcField     $field
 * @property \abc\models\AcFormGroup $form_group
 *
 * @package abc\models
 */
class FieldsGroup extends AModelBase
{
    protected $primaryKey = 'field_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'field_id'   => 'int',
        'group_id'   => 'int',
        'sort_order' => 'int',
    ];

    protected $fillable = [
        'group_id',
        'sort_order',
    ];

    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }

    public function form_group()
    {
        return $this->belongsTo(FormGroup::class, 'group_id');
    }
}
