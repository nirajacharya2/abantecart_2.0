<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class ProductsToDownload
 *
 * @property int $product_id
 * @property int $download_id
 *
 * @property Product $product
 * @property Download $download
 *
 * @package abc\models
 */
class ProductsToDownload extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'product_id'  => 'int',
        'download_id' => 'int',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function download()
    {
        return $this->belongsTo(Download::class, 'download_id');
    }
}
