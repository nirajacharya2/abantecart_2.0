<?php

namespace abc\models\locale;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class WeightClassDescription
 *
 * @property int $weight_class_id
 * @property int $language_id
 * @property string $title
 * @property string $unit
 *
 * @property WeightClass $weight_class
 * @property Language $language
 *
 * @package abc\models
 */
class WeightClassDescription extends BaseModel
{
    use SoftDeletes;
    const DELETED_AT = 'date_deleted';

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'weight_class_id',
        'language_id'
    ];

    public $timestamps = false;

    protected $casts = [
        'weight_class_id' => 'int',
        'language_id'     => 'int',
    ];

    protected $fillable = [
        'title',
        'unit',
    ];

    public function weight_class()
    {
        return $this->belongsTo(WeightClass::class, 'weight_class_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
