<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use abc\models\locale\Language;

/**
 * Class StockStatus
 *
 * @property int $stock_status_id
 * @property int $language_id
 * @property string $name
 *
 * @property Language $language
 *
 * @package abc\models
 */
class StockStatus extends BaseModel
{
    //TODO: needs to rebuild this table!!!
    public $incrementing = false;

    protected $casts = [
        'stock_status_id' => 'int',
        'language_id'     => 'int',
    ];

    protected $fillable = [
        'name',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'stock_status_id');
    }
}
