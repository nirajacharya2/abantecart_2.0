<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcProductsToDownload
 *
 * @property int                    $product_id
 * @property int                    $download_id
 *
 * @property \abc\models\Product    $product
 * @property \abc\models\AcDownload $download
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
        return $this->belongsTo(\abc\models\Product::class, 'product_id');
    }

    public function download()
    {
        return $this->belongsTo(Download::class, 'download_id');
    }
}
