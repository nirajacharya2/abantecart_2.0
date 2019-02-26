<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class GlobalAttributesGroupsDescription
 *
 * @property int $attribute_group_id
 * @property int $language_id
 * @property string $name
 *
 * @package abc\models
 */
class GlobalAttributesGroupsDescription extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'attribute_group_id',
        'language_id',
    ];

    public $timestamps = false;

    protected $casts = [
        'attribute_group_id' => 'int',
        'language_id'        => 'int',
    ];

    protected $fillable = [
        'name',
    ];

    public function group()
    {
        return $this->belongsTo(GlobalAttributesGroup::class, 'attribute_type_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
