<?php

namespace abc\models\locale;

use abc\models\BaseModel;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CountryDescription
 *
 * @property int $country_id
 * @property int $language_id
 * @property string $name
 *
 * @property Country $country
 * @property Language $language
 *
 * @package abc\models
 */
class CountryDescription extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;
    const DELETED_AT = 'date_deleted';

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'country_id',
        'language_id'
    ];

    public $timestamps = false;

    protected $casts = [
        'country_id'  => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'name',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
