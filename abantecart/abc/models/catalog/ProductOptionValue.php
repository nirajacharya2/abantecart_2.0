<?php

namespace abc\models\catalog;

use abc\core\engine\AResource;
use abc\models\BaseModel;
use Iatstuti\Database\Support\CascadeSoftDeletes;
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
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property ProductOption $product_option
 * @property Product $product
 * @property \Illuminate\Database\Eloquent\Collection $order_options
 *
 * @method static ProductOptionValue find(int $product_option_value_id) ProductOptionValue
 *
 * @package abc\models
 */
class ProductOptionValue extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions'];

    protected $primaryKey = 'product_option_value_id';

    protected $touches = ['option'];

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

    /** @var array */
    protected $dates = [
        'date_added',
        'date_modified',
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

    protected $rules = [
        /** @see validate() */
        'product_option_id' => [
            'checks'   => [
                'integer',
                'required',
                'exists:product_options',
            ],
            'messages' => [
                '*' => ['default_text' => 'Product Option ID is not Integer or absent in product_options table!'],
            ],
        ],

        'product_id' => [
            'checks'   => [
                'integer',
                'required',
                'exists:products',
            ],
            'messages' => [
                '*' => ['default_text' => 'Product ID is not Integer or absent in the products table!'],
            ],
        ],

        'group_id' => [
            'checks'   => [
                'integer',
                'nullable',
            ],
            'messages' => [
                '*' => ['default_text' => 'Group ID is not integer!'],
            ],
        ],

        'sku' => [
            'checks'   => [
                'string',
                'max:255',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Product Option SKU must be less than 255 characters!',
                ],
            ],
        ],

        'quantity' => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Product Quantity must be an integer!',
                ],
            ],
        ],

        'subtract' => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not boolean!',
                ],
            ],
        ],

        'price' => [
            'checks'   => [
                'numeric',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be numeric!',
                ],
            ],
        ],

        'prefix' => [
            'checks'   => [
                'string',
                'sometimes',
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Price prefix cannot be empty!',
                ],
            ],
        ],

        'weight' => [
            'checks'   => [
                'numeric',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be numeric!',
                ],
            ],
        ],

        'weight_type' => [
            'checks'   => [
                'string',
                'max:3',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Weight Type must be less than 3 characters length!',
                ],
            ],
        ],

        'sort_order' => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],

        'attribute_value_id' => [
            'checks'   => [
                'integer',
                'nullable',
                'exists:global_attributes_values',
            ],
            'messages' => [
                '*' => ['default_text' => ':attribute is not integer or absent in global_attribute_values table!'],
            ],
        ],

        'default' => [
            'checks'   => [
                'boolean',
                /** @see __construct() method */
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be a boolean!',
                ],
            ],
        ],

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
        $sizes = array(
            'main'  => array(
                'width'  => $this->config->get('config_image_popup_width'),
                'height' => $this->config->get('config_image_popup_height'),
            ),
            'thumb' => array(
                'width'  => $this->config->get('config_image_thumb_width'),
                'height' => $this->config->get('config_image_thumb_height'),
            ),
        );
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
