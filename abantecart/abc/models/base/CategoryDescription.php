<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class CategoryDescription
 *
 * @property int $category_id
 * @property int $language_id
 * @property string $name
 * @property string $meta_keywords
 * @property string $meta_description
 * @property string $description
 *
 * @property \abc\models\Category $category
 * @property \abc\models\Language $language
 *
 * @package abc\models
 */
class CategoryDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'category_id' => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'name',
        'meta_keywords',
        'meta_description',
        'description',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
