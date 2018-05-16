<?php

namespace abc\models;

use abc\core\ABC;

/**
 * Class Product
 *
 * @property int                                      $product_id
 * @property string                                   $model
 * @property string                                   $sku
 * @property string                                   $location
 * @property int                                      $quantity
 * @property string                                   $stock_checkout
 * @property int                                      $stock_status_id
 * @property int                                      $manufacturer_id
 * @property int                                      $shipping
 * @property int                                      $ship_individually
 * @property int                                      $free_shipping
 * @property float                                    $shipping_price
 * @property float                                    $price
 * @property int                                      $tax_class_id
 * @property \Carbon\Carbon                           $date_available
 * @property float                                    $weight
 * @property int                                      $weight_class_id
 * @property float                                    $length
 * @property float                                    $width
 * @property float                                    $height
 * @property int                                      $length_class_id
 * @property int                                      $status
 * @property int                                      $viewed
 * @property int                                      $sort_order
 * @property int                                      $subtract
 * @property int                                      $minimum
 * @property int                                      $maximum
 * @property float                                    $cost
 * @property int                                      $call_to_order
 * @property string                                   $settings
 * @property \Carbon\Carbon                           $date_added
 * @property \Carbon\Carbon                           $date_modified
 *
 * @property \Illuminate\Database\Eloquent\Collection $coupons_products
 * @property \Illuminate\Database\Eloquent\Collection $order_products
 * @property \Illuminate\Database\Eloquent\Collection $product_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $product_discounts
 * @property \Illuminate\Database\Eloquent\Collection $product_option_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $product_option_value_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $product_option_values
 * @property \Illuminate\Database\Eloquent\Collection $product_options
 * @property \Illuminate\Database\Eloquent\Collection $product_specials
 * @property \Illuminate\Database\Eloquent\Collection $product_tags
 * @property \abc\models\ProductsFeatured             $products_featured
 * @property \Illuminate\Database\Eloquent\Collection $products_related
 * @property \Illuminate\Database\Eloquent\Collection $products_to_categories
 * @property \Illuminate\Database\Eloquent\Collection $products_to_downloads
 * @property \Illuminate\Database\Eloquent\Collection $products_to_stores
 * @property \Illuminate\Database\Eloquent\Collection $reviews
 *
 * @package abc\models
 */
class Product extends AModelBase
{
    protected $primaryKey = 'product_id';
    public $timestamps = false;
    protected $casts = [
        'quantity'          => 'int',
        'stock_status_id'   => 'int',
        'manufacturer_id'   => 'int',
        'shipping'          => 'int',
        'ship_individually' => 'int',
        'free_shipping'     => 'int',
        'shipping_price'    => 'float',
        'price'             => 'float',
        'tax_class_id'      => 'int',
        'weight'            => 'float',
        'weight_class_id'   => 'int',
        'length'            => 'float',
        'width'             => 'float',
        'height'            => 'float',
        'length_class_id'   => 'int',
        'status'            => 'int',
        'viewed'            => 'int',
        'sort_order'        => 'int',
        'subtract'          => 'int',
        'minimum'           => 'int',
        'maximum'           => 'int',
        'cost'              => 'float',
        'call_to_order'     => 'int',
    ];

    protected $dates = [
        'date_available',
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'model',
        'sku',
        'location',
        'quantity',
        'stock_checkout',
        'stock_status_id',
        'manufacturer_id',
        'shipping',
        'ship_individually',
        'free_shipping',
        'shipping_price',
        'price',
        'tax_class_id',
        'date_available',
        'weight',
        'weight_class_id',
        'length',
        'width',
        'height',
        'length_class_id',
        'status',
        'viewed',
        'sort_order',
        'subtract',
        'minimum',
        'maximum',
        'cost',
        'call_to_order',
        'settings',
        'date_added',
        'date_modified',
    ];

    public function coupons_products()
    {
        require_once ABC::env('DIR_MODELS').'CouponsProduct.php';
        return $this->hasMany(CouponsProduct::class, 'product_id');
    }

    public function order_products()
    {
        require_once ABC::env('DIR_MODELS').'OrderProduct.php';
        return $this->hasMany(OrderProduct::class, 'product_id');
    }

    public function product_descriptions()
    {
        require_once ABC::env('DIR_MODELS').'ProductDescription.php';
        return $this->hasMany(ProductDescription::class, 'product_id');
    }

    public function product_discounts()
    {
        require_once ABC::env('DIR_MODELS').'ProductDiscount.php';
        return $this->hasMany(ProductDiscount::class, 'product_id');
    }

    public function product_option_descriptions()
    {
        require_once ABC::env('DIR_MODELS').'ProductOptionDescription.php';
        return $this->hasMany(ProductOptionDescription::class, 'product_id');
    }

    public function product_option_value_descriptions()
    {
        require_once ABC::env('DIR_MODELS').'ProductOptionValueDescription.php';
        return $this->hasMany(ProductOptionValueDescription::class, 'product_id');
    }

    public function product_option_values()
    {
        require_once ABC::env('DIR_MODELS').'ProductOptionValue.php';
        return $this->hasMany(ProductOptionValue::class, 'product_id');
    }

    public function product_options()
    {
        require_once ABC::env('DIR_MODELS').'ProductOption.php';
        return $this->hasMany(ProductOption::class, 'product_id');
    }

    public function product_specials()
    {
        require_once ABC::env('DIR_MODELS').'ProductSpecial.php';
        return $this->hasMany(ProductSpecial::class, 'product_id');
    }

    public function product_tags()
    {
        require_once ABC::env('DIR_MODELS').'ProductTag.php';
        return $this->hasMany(ProductTag::class, 'product_id');
    }

    public function products_featured()
    {
        require_once ABC::env('DIR_MODELS').'ProductsFeatured.php';
        return $this->hasOne(ProductsFeatured::class, 'product_id');
    }

    public function products_related()
    {
        require_once ABC::env('DIR_MODELS').'ProductsRelated.php';
        return $this->hasMany(ProductsRelated::class, 'product_id');
    }

    public function products_to_categories()
    {
        require_once ABC::env('DIR_MODELS').'ProductsToCategory.php';
        return $this->hasMany(ProductsToCategory::class, 'product_id');
    }

    public function products_to_downloads()
    {
        require_once ABC::env('DIR_MODELS').'ProductsToDownload.php';
        return $this->hasMany(ProductsToDownload::class, 'product_id');
    }

    public function products_to_stores()
    {
        require_once ABC::env('DIR_MODELS').'ProductsToStore.php';
        return $this->hasMany(ProductsToStore::class, 'product_id');
    }

    public function reviews()
    {
        require_once ABC::env('DIR_MODELS').'Review.php';
        return $this->hasMany(Review::class, 'product_id');
    }

    public function isStockTrackable()
    {
        $track_status = 0;
        //check product option values
        $option_values = $this->query()->from('product_options')
            ->where('product_options.product_id', $this->product_id)
            ->where('status', 1)
            ->join(
                'product_option_values',
                'product_option_values.product_option_id',
                '=',
                'product_options.product_option_id'
            )->select('product_option_values.product_option_value_id', 'product_option_values.subtract')
            ->get()->toArray();

        foreach ($option_values as $row) {
            $track_status += (int)$row['subtract'];
        }
        //if no options - check whole product subtract
        if (!$track_status && !$option_values) {
            //check main product
            $track_status = (int)$this::find($this->product_id)->first()->subtract;
        }

        return $track_status;
    }
}