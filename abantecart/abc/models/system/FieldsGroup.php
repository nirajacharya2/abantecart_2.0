<?php

namespace abc\models\system;

use abc\models\BaseModel;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class FieldsGroup
 *
 * @property int $field_id
 * @property int $group_id
 * @property int $sort_order
 *
 * @property Field $field
 * @property FormGroup $form_group
 *
 * @package abc\models
 */
class FieldsGroup extends BaseModel
{
    use SoftDeletes;

    const DELETED_AT = 'date_deleted';

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'field_id',
        'group_id'
    ];

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
