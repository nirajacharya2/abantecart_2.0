<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Illuminate\Database\Eloquent\SoftDeletes;

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
 * @property Category $category
 * @property Language $language
 *
 * @package abc\models
 */
class CategoryDescription extends BaseModel
{
    use SoftDeletes;

    protected $mainClassName = Category::class;
    protected $mainClassKey = 'category_id';
    /**
     * @var string
     */
    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'category_id',
        'language_id',
    ];

    protected $casts = [
        'category_id' => 'int',
        'language_id' => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $guarded = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'language_id',
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
