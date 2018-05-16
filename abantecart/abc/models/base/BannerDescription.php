<?php

namespace abc\models\base;

use abc\models\AModelBase;

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
 * @property \abc\models\Banner $banner
 * @property \abc\models\Language $language
 *
 * @package abc\models
 */
class BannerDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

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
