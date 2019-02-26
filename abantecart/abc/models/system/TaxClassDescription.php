<?php

namespace abc\models\system;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TaxClassDescription
 *
 * @property int $tax_class_id
 * @property int $language_id
 * @property string $title
 * @property string $description
 *
 * @property TaxClass $tax_class
 * @property Language $language
 *
 * @package abc\models
 */
class TaxClassDescription extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'tax_class_id',
        'language_id',
    ];

    public $timestamps = false;

    protected $casts = [
        'tax_class_id' => 'int',
        'language_id'  => 'int',
    ];

    protected $fillable = [
        'title',
        'description',
    ];

    public function tax_class()
    {
        return $this->belongsTo(TaxClass::class, 'tax_class_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
