<?php

namespace abc\models\locale;

use abc\models\BaseModel;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class LengthClass
 *
 * @property int $length_class_id
 * @property float $value
 * @property string $iso_code
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @package abc\models
 */
class LengthClass extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions'];
    protected $primaryKey = 'length_class_id';

    public $timestamps = false;

    protected $casts = [
        'value' => 'float',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'value',
        'date_added',
        'date_modified',
    ];

    public function descriptions()
    {
        return $this->HasMany(LengthClassDescription::class, 'length_class_id');
    }

    public function description()
    {
        return $this->hasOne(LengthClassDescription::class, 'length_class_id')
                    ->where('language_id', $this->registry->get('language')->getContentLanguageID());
    }
}
