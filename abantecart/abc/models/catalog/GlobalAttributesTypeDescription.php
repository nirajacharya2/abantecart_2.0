<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class GlobalAttributesTypeDescription
 *
 * @property int $attribute_type_id
 * @property int $language_id
 * @property string $type_name
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @package abc\models
 */
class GlobalAttributesTypeDescription extends BaseModel
{
    use SoftDeletes;
    const DELETED_AT = 'date_deleted';

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'attribute_type_id',
        'language_id'
    ];
    public $timestamps = false;

    protected $casts = [
        'attribute_type_id' => 'int',
        'language_id'       => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'type_name',
        'date_added',
        'date_modified',
    ];

    public function type()
    {
        return $this->belongsTo(GlobalAttributesType::class, 'attribute_type_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
