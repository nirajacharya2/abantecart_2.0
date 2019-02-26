<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use abc\models\system\Store;

/**
 * Class CategoriesToStore
 *
 * @property int $category_id
 * @property int $store_id
 *
 * @property Category $category
 * @property Store $store
 *
 * @package abc\models
 */
class CategoriesToStore extends BaseModel
{

    /**
     * @var string
     */
    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'category_id',
        'store_id'
    ];

    public $timestamps = false;

    protected $casts = [
        'category_id' => 'int',
        'store_id'    => 'int',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
