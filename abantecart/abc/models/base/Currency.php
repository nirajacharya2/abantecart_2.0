<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class Currency
 *
 * @property int $currency_id
 * @property string $title
 * @property string $code
 * @property string $symbol_left
 * @property string $symbol_right
 * @property string $decimal_place
 * @property float $value
 * @property int $status
 * @property \Carbon\Carbon $date_modified
 *
 * @property \Illuminate\Database\Eloquent\Collection $orders
 *
 * @package abc\models
 */
class Currency extends AModelBase
{
    protected $primaryKey = 'currency_id';
    public $timestamps = false;

    protected $casts = [
        'value'  => 'float',
        'status' => 'int',
    ];

    protected $dates = [
        'date_modified',
    ];

    protected $fillable = [
        'title',
        'code',
        'symbol_left',
        'symbol_right',
        'decimal_place',
        'value',
        'status',
        'date_modified',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'currency_id');
    }
}
