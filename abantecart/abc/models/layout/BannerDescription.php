<?php

namespace abc\models\layout;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class BannerDescription
 *
 * @property int $banner_id
 * @property int $language_id
 * @property string $name
 * @property string $description
 * @property string $meta
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property Banner $banner
 * @property Language $language
 *
 * @package abc\models
 */
class BannerDescription extends BaseModel
{
    use SoftDeletes;

    public $timestamps = false;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'banner_id',
        'language_id',
    ];

    protected $casts = [
        'banner_id'   => 'int',
        'language_id' => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'name',
        'description',
        'meta',
        'date_added',
        'date_modified',
    ];

    public function banner()
    {
        return $this->belongsTo(Banner::class, 'banner_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
