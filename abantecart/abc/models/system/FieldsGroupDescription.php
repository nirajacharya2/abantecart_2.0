<?php

namespace abc\models\system;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class FieldsGroupDescription
 *
 * @property int $group_id
 * @property string $name
 * @property string $description
 * @property int $language_id
 *
 * @property FormGroup $form_group
 * @property Language $language
 *
 * @package abc\models
 */
class FieldsGroupDescription extends BaseModel
{
    use SoftDeletes;
    const DELETED_AT = 'date_deleted';

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'group_id',
        'language_id'
    ];

    public $timestamps = false;

    protected $casts = [
        'group_id'    => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'name',
        'description',
    ];

    public function form_group()
    {
        return $this->belongsTo(FormGroup::class, 'group_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
