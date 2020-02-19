<?php

namespace abc\models\catalog;

use abc\core\engine\AResource;
use abc\models\BaseModel;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ProductOptionValue
 *
 * @property int $product_option_value_id
 * @property int $product_option_id
 * @property int $product_id
 * @property int $group_id
 * @property string $sku
 * @property int $quantity
 * @property int $subtract
 * @property float $price
 * @property string $prefix
 * @property float $weight
 * @property string $weight_type
 * @property int $attribute_value_id
 * @property string $grouped_attribute_data
 * @property int $sort_order
 * @property int $default
 *
 * @property ProductOption $product_option
 * @property ProductOptionValueDescription $description
 * @property Product $product
 * @property Collection $order_options
 *
 * @method static ProductOptionValue find(int $product_option_value_id) ProductOptionValue
 * @method static ProductOptionValue select(mixed $select) Builder
 *
 * @package abc\models
 */
class ProductOptionValue extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions'];

    protected $primaryKey = 'product_option_value_id';
    public $timestamps = false;

    /**
     * @var array
     */
    protected $images = [];

    protected $casts = [
        'product_option_id'  => 'int',
        'product_id'         => 'int',
        'group_id'           => 'int',
        'quantity'           => 'int',
        'subtract'           => 'int',
        'price'              => 'float',
        'weight'             => 'float',
        'attribute_value_id' => 'int',
        'sort_order'         => 'int',
        'default'            => 'int',
    ];

    protected $fillable = [
        'product_option_id',
        'product_id',
        'group_id',
        'sku',
        'quantity',
        'subtract',
        'price',
        'prefix',
        'weight',
        'weight_type',
        'attribute_value_id',
        'grouped_attribute_data',
        'sort_order',
        'default',
    ];

    public function option()
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function description()
    {
        return $this->hasOne(ProductOptionValueDescription::class, 'product_option_value_id')
                    ->where('language_id', '=', static::$current_language_id);
    }

    public function descriptions()
    {
        return $this->hasMany(ProductOptionValueDescription::class, 'product_option_value_id');
    }

    public function images()
    {
        if ($this->images) {
            return $this->images;
        }
        $resource = new AResource('image');
        $sizes = [
            'main'  => [
                'width'  => $this->config->get('config_image_popup_width'),
                'height' => $this->config->get('config_image_popup_height'),
            ],
            'thumb' => [
                'width'  => $this->config->get('config_image_thumb_width'),
                'height' => $this->config->get('config_image_thumb_height'),
            ],
        ];
        $this->images['images'] = $resource->getResourceAllObjects(
            'product_option_value',
            $this->getKey(),
            $sizes,
            0,
            false);
        return $this->images;
    }

    public function getAllData()
    {
        $this->load('descriptions');
        $data = $this->toArray();
        $data['images'] = $this->images();
        return $data;
    }

}
