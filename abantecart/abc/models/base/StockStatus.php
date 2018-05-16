<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class AcStockStatus
 *
 * @property int $stock_status_id
 * @property int $language_id
 * @property string $name
 *
 * @property \abc\models\base\Language $language
 *
 * @package abc\models
 */
class StockStatus extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

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
}
