<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Iatstuti\Database\Support\CascadeSoftDeletes;
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
    use SoftDeletes, CascadeSoftDeletes;
    const DELETED_AT = 'date_deleted';
    /**
     * @var string
     */
    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'category_id',
        'language_id'
    ];

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
