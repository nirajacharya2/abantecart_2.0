<?php

namespace abc\models\catalog;

use abc\core\engine\AResource;
use abc\models\BaseModel;
use Carbon\Carbon;
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
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property ProductOption $product_option
 * @property Product $product
 * @property Collection $order_options
 * @property ProductOptionDescription $descriptions
 * @property ProductOptionDescription $description
 *
 * @method static ProductOptionValue find(int $product_option_value_id) ProductOptionValue
 * @method static ProductOptionValue create(array $attributes) ProductOptionValue
 *
 * @package abc\models
 */
class ProductOptionValue extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions'];
    protected $mainClassName = Product::class;
    protected $mainClassKey = 'product_id';

    protected $primaryKey = 'product_option_value_id';

    protected $touches = ['option'];

    /**
     * @var array
     */
    protected $images = [];

    protected $casts = [
        'product_option_id'      => 'int',
        'product_id'             => 'int',
        'group_id'               => 'int',
        'quantity'               => 'int',
        'subtract'               => 'int',
        'price'                  => 'float',
        'weight'                 => 'float',
        'attribute_value_id'     => 'int',
        'grouped_attribute_data' => 'serialized',
        'sort_order'             => 'int',
        'default'                => 'int',
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

    public function setGroupedAttributeDataAttribute($value)
    {
        if ($value !== null && !is_string($value)) {
            $this->attributes['grouped_attribute_data'] = serialize($value);
        }
    }

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

    /**
     * @param int $product_option_id
     *
     * @return array - array with options
     */
    public static function getProductOptionValues($product_option_id)
    {

        $values = ProductOptionValue::where(
            [
                'product_option_id' => $product_option_id,
            ]
        )->orderBy('sort_order')
                                    ->get();

        $result = [];
        if ($values) {
            foreach ($values as $option_value) {
                $result[] = static::getProductOptionValue($option_value->product_option_value_id);
            }
        }

        return $result;
    }

    /**
     * @param int $option_value_id
     *
     * @return array
     */
    public static function getProductOptionValue($option_value_id)
    {
        $option_value_id = (int)$option_value_id;
        if (!$option_value_id) {
            return [];
        }

        $option_value = ProductOptionValue::with('descriptions')
                                          ->where(
                                              [
                                                  'product_option_value_id' => $option_value_id,
                                                  'group_id'                => 0,
                                              ]
                                          )
                                          ->first();

        if (!$option_value) {
            return [];
        }

        $value_description_data = [];
        foreach ($option_value->descriptions->toArray() as $description) {
            /** @var ProductOptionValueDescription $description */
            $language_id = $description['language_id'];
            //regular option value name
            $value_description_data[$language_id] = $description;
            //get children (grouped options) individual names array
            if ($description['grouped_attribute_names']) {
                $value_description_data[$language_id]['children_options_names'] =
                    $description['grouped_attribute_names'];
            }
        }

        $result = $option_value->toArray();
        $result['language'] = $value_description_data;

        //get children (grouped options) data
        $child_option_values = $result['grouped_attribute_data'];
        if (is_array($child_option_values) && sizeof($child_option_values)) {
            $result['children_options'] = [];
            foreach ($child_option_values as $child_value) {
                $result['children_options'][$child_value['attr_id']] = (int)$child_value['attr_v_id'];
            }
        }
        return $result;
    }

}
