<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2023 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 *
 */

namespace abc\models\catalog;

use abc\core\ABC;
use abc\core\engine\HtmlElementFactory;
use abc\core\engine\Registry;
use abc\core\lib\AException;
use abc\core\lib\AttributeManager;
use abc\models\BaseModel;
use abc\core\engine\AResource;
use abc\models\casts\Serialized;
use abc\models\locale\LengthClass;
use abc\models\locale\WeightClass;
use abc\models\order\Coupon;
use abc\models\order\OrderProduct;
use abc\models\QueryBuilder;
use abc\models\system\Audit;
use abc\models\system\Setting;
use abc\models\system\Store;
use abc\models\system\TaxClass;
use Carbon\Carbon;
use Dyrynda\Database\Support\GeneratesUuid;
use Exception;
use H;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use stdClass;

/**
 * Class Product
 *
 * @property int $product_id
 * @property string $model
 * @property string $sku
 * @property string $uuid
 * @property string $location
 * @property int $quantity
 * @property string $stock_checkout
 * @property int $stock_status_id
 * @property StockStatus $stock_status
 * @property int $manufacturer_id
 * @property Manufacturer $manufacturer
 * @property int $shipping
 * @property int $ship_individually
 * @property int $free_shipping
 * @property float $shipping_price
 * @property float $price
 * @property int $tax_class_id
 * @property Carbon $date_available
 * @property float $weight
 * @property int $weight_class_id
 * @property float $length
 * @property float $width
 * @property float $height
 * @property int $length_class_id
 * @property int $status
 * @property int $featured
 * @property int $viewed
 * @property int $sort_order
 * @property int $subtract
 * @property int $minimum
 * @property int $maximum
 * @property float $cost
 * @property int $call_to_order
 * @property array $settings
 * @property Carbon $date_added
 * @property Carbon $date_modified
 * @property ProductDescription $description
 * @property ProductDescription $descriptions
 * @property Collection $categories
 * @property ProductOption $options
 * @property ProductDescription $product_descriptions
 * @property ProductDiscount $product_discounts
 * @property ProductOption $product_options
 * @property ProductSpecial $product_specials
 * @property ProductTag $tags
 * @property ProductTag $tagsByLanguage
 * @property Product $related
 * @property Review $active_reviews
 * @property Review $reviews
 * @property int $product_type_id
 *
 * @method static Product|Collection find(int|array $product_id) Product
 * @method static Product select(mixed $select) Builder
 * @method static Collection search(array $filterParams) - see getProducts() method
 * @method static WithFinalPrice(int $customer_group_id, Carbon|string $toDate = null) - adds "final_price" column into selected fields
 * @method static WithFirstSpecialPrice(int $customer_group_id, Carbon|string $toDate = null) - adds "special_price" column into selected fields
 * @method static WithFirstDiscountPrice(int $customer_group_id, Carbon|string $toDate = null) - adds "discount_price" column into selected fields
 * @method static WithReviewCount(bool $only_enabled = true) - adds "review_count" column into selected fields
 * @method static WithOptionCount(bool $only_enabled = true) - adds "option_count" column into selected fields
 * @method static WithAvgRating(bool $only_enabled = true) - adds "rating" column into selected fields
 * @method static WithStockInfo() - adds "stock_tracking" and quantity in the stock columns into selected fields
 * @method static WithCategoryIds() - adds comma separated "category_ids" columns into selected fields
 *
 * @package abc\models
 */
class Product extends BaseModel
{
    use GeneratesUuid;

    protected $cascadeDeletes = [
        'descriptions',
        'coupons',
        'discounts',
        'options',
        'specials',
        'tags',
        'related',
        'reviews',
        'downloads',
    ];
    /**
     * Access policy properties
     * Note: names must be without dashes and whitespaces
     * policy rule will be named as {userType-userGroup}.product-product-read
     * For example: system-www-data.product-product-read
     */
    protected $policyGroup = 'product';
    protected $policyObject = 'product';

    /**
     * @var string
     */
    protected $primaryKey = 'product_id';

    protected $hidden = ['pivot'];

    /** @see Product::boot() categories touching! */
    protected $touches = ['stores'];
    /**
     * @var array
     */
    protected $casts = [
        'product_id'        => 'int',
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
        'featured'          => 'boolean',
        'viewed'            => 'int',
        'sort_order'        => 'int',
        'subtract'          => 'int',
        'minimum'           => 'int',
        'maximum'           => 'int',
        'cost'              => 'float',
        'call_to_order'     => 'int',
        'product_type_id'   => 'int',
        'settings'          => Serialized::class,
        'date_available'    => 'datetime',
        'date_added'        => 'datetime',
        'date_modified'     => 'datetime'
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'product_id',
        'uuid',
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
        'featured',
        'viewed',
        'sort_order',
        'subtract',
        'minimum',
        'maximum',
        'cost',
        'call_to_order',
        'product_type_id',
        'settings',
        'date_deleted',
    ];
    protected $rules = [
        /** @see validate() */
        'product_id' => [
            'checks' => [
                'integer',
            ],
            'messages' => [
                '*' => ['default_text' => 'Product ID is not Integer!'],
            ],
        ],

        'uuid' => [
            'checks' => [
                'uuid',
                'sometimes',
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Invalid UUID Format! Please follow pattern ' . Uuid::VALID_PATTERN,
                ],
            ],
        ],

        'model' => [
            'checks'   => [
                'string',
                'between:1,64',
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_model',
                    'language_block' => 'catalog/product',
                    'default_text'   => 'Product Model must be less than 64 characters! Recommended 5-25 characters',
                    'section'        => 'admin',
                ],
            ],
        ],

        'sku' => [
            'checks'   => [
                'string',
                'between:1,64',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Product sku must be less than 64 characters!',
                ],
            ],
        ],

        'location' => [
            'checks'   => [
                'string',
                'between:1,128',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Product Location must be less than 128 characters!',
                ],
            ],
        ],

        'quantity'       => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Product Quantity must be an integer!',
                ],
            ],
        ],
        'stock_checkout' => [
            'checks'   => [
                'string',
                'nullable',
                'in:0,1',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Stock Checkout can be 1,0 or empty!',
                ],
            ],
        ],

        'stock_status_id' => [
            'checks' => [
                'integer',
                'sometimes',
                'required',
                // 'exists:stock_statuses',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or not presents in stock_statuses table!',
                ],
            ],
        ],

        'manufacturer_id' => [
            'checks' => [
                'integer',
                'required',
                'sometimes',
                'exists:manufacturers',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or not presents in Manufacturers table!',
                ],
            ],
        ],

        'shipping' => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not boolean!',
                ],
            ],
        ],

        'ship_individually' => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not boolean!',
                ],
            ],
        ],
        'free_shipping'     => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not boolean!',
                ],
            ],
        ],
        'shipping_price'    => [
            'checks'   => [
                'numeric',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be numeric!',
                ],
            ],
        ],
        'price'             => [
            'checks'   => [
                'numeric',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be numeric!',
                ],
            ],
        ],

        'tax_class_id' => [
            'checks' => [
                'integer',
                'required',
                'sometimes',
                'exists:tax_classes',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or not presents in tax_classes table!',
                ],
            ],
        ],

        'weight' => [
            'checks' => [
                'numeric',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be numeric!',
                ],
            ],
        ],

        'weight_class_id' => [
            'checks' => [
                'integer',
                'nullable',
                'exists:weight_classes',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or not presents in weight_classes table!',
                ],
            ],
        ],

        'length' => [
            'checks' => [
                'numeric',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be numeric!',
                ],
            ],
        ],

        'width' => [
            'checks' => [
                'numeric',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be numeric!',
                ],
            ],
        ],

        'height' => [
            'checks' => [
                'numeric',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be numeric!',
                ],
            ],
        ],

        'length_class_id' => [
            'checks' => [
                'integer',
                'nullable',
                'exists:length_classes',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or not presents in length_classes table!',
                ],
            ],
        ],

        'status' => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not boolean!',
                ],
            ],
        ],

        'featured' => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not boolean!',
                ],
            ],
        ],

        'viewed'     => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
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

        'subtract' => [
            'checks' => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not boolean!',
                ],
            ],
        ],

        'minimum' => [
            'checks' => [
                'integer',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Minimal Quantity is not integer!',
                ],
            ],
        ],

        'maximum' => [
            'checks' => [
                'integer',
                'gte:minimum',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Maximum Quantity is not integer or less than minimal.',
                ],
            ],
        ],

        'cost' => [
            'checks' => [
                'numeric',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be numeric!',
                ],
            ],
        ],

        'call_to_order' => [
            'checks' => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not boolean!',
                ],
            ],
        ],

        'product_type_id' => [
            'checks' => [
                'integer',
                'nullable',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],
    ];
    protected $fields = [
        'product_type_id'   => [
            'cast'       => 'int',
            'rule'       => 'integer',
            'access'     => 'read',
            'sort_order' => 10,
            'input_type' => 'selectbox',
            'relation'   => 'getProductTypes',
            'hidable'    => false,
        ],
        'status'            => [
            'cast'       => 'int',
            'access'     => 'read',
            'sort_order' => 10,
            'input_type' => 'switch',
            'hidable'    => false,
        ],
        'featured'          => [
            'cast'       => 'int',
            'access'     => 'read',
            'sort_order' => 10,
            'input_type' => 'switch',
            'hidable'    => true,
        ],
        'product_id'        => [
            'cast'       => 'int',
            'rule'       => 'integer',
            'access'     => 'read',
            'sort_order' => 20,
            'hidable'    => false,
        ],
        'name'              => [
            'cast'       => 'string',
            'rule'       => 'required|max:255',
            'js_rule'    => 'required|max:255',
            'input_type' => 'input',
            'access'     => 'read',
            'sort_order' => 20,
            'hidable'    => false,
        ],
        'blurb'             => [
            'cast'       => 'string',
            'rule'       => '',
            'input_type' => 'textarea',
            'access'     => 'read',
            'sort_order' => 20,
            'hidable'    => true,
        ],
        'description'       => [
            'cast'       => 'string',
            'rule'       => '',
            'input_type' => 'editor',
            'access'     => 'read',
            'sort_order' => 20,
            'hidable'    => true,
        ],
        'meta_keywords'     => [
            'cast'       => 'string',
            'rule'       => '',
            'input_type' => 'textarea',
            'access'     => 'read',
            'sort_order' => 20,
            'hidable'    => true,
        ],
        'meta_description'  => [
            'cast'       => 'string',
            'rule'       => '',
            'input_type' => 'textarea',
            'access'     => 'read',
            'sort_order' => 20,
            'hidable'    => true,
        ],
        'tags'              => [
            'cast'       => 'string',
            'rule'       => '',
            'input_type' => 'input',
            'access'     => 'read',
            'sort_order' => 20,
            'hidable'    => true,
        ],
        'categories'        => [
            'cast'       => 'int',
            'rule'       => 'integer',
            'js_rule'    => 'integer',
            'access'     => 'read',
            'sort_order' => 10,
            'input_type' => 'selectbox',
            'relation'   => 'getProductCategories',
            'props'      => [
                'multiple'        => true,
                'chips'           => true,
                'deletable-chips' => true,
            ],
            'hidable'    => false,
        ],
        'product_store'     => [
            'cast'       => 'int',
            'rule'       => 'integer',
            'access'     => 'read',
            'sort_order' => 10,
            'input_type' => 'selectbox',
            'relation'   => 'getProductStores',
            'props'      => [
                'multiple'        => true,
                'chips'           => true,
                'deletable-chips' => true,
            ],
            'hidable'    => false,
        ],
        'manufacturer_id'   => [
            'cast'       => 'int',
            'rule'       => 'integer',
            'access'     => 'read',
            'sort_order' => 10,
            'input_type' => 'selectbox',
            'relation'   => 'getManufacturers',
            'props'      => [
                'chips'           => true,
                'deletable-chips' => true,
            ],
            'hidable'    => true,
        ],
        'model'             => [
            'cast'       => 'string',
            'rule'       => 'required|max:64',
            'js_rule'    => 'required|max:64',
            'input_type' => 'input',
            'access'     => 'read',
            'sort_order' => 20,
            'hidable'    => false,
        ],
        'call_to_order'     => [
            'cast'       => 'int',
            'access'     => 'read',
            'sort_order' => 10,
            'input_type' => 'switch',
            'hidable'    => true,
        ],
        'price'             => [
            'cast'       => 'float',
            'rule'       => 'number',
            'input_type' => 'input',
            'access'     => 'read',
            'sort_order' => 30,
            'props'      => [
                'type' => 'number',
                'step' => 0.01,
            ],
            'hidable'    => true,
        ],
        'cost'              => [
            'cast'       => 'float',
            'rule'       => 'number',
            'input_type' => 'input',
            'access'     => 'read',
            'sort_order' => 30,
            'props'      => [
                'type' => 'number',
                'step' => 0.01,
            ],
            'hidable'    => true,
        ],
        'tax_class_id'      => [
            'cast'       => 'int',
            'rule'       => 'integer',
            'access'     => 'read',
            'sort_order' => 10,
            'input_type' => 'selectbox',
            'relation'   => 'getTaxClasses',
            'props'      => [
                'chips'           => true,
                'deletable-chips' => true,
            ],
            'hidable'    => true,
        ],
        'subtract'          => [
            'cast'       => 'int',
            'access'     => 'read',
            'sort_order' => 10,
            'input_type' => 'switch',
            'hidable'    => true,
        ],
        'quantity'          => [
            'cast'         => 'int',
            'rule'         => 'integer',
            'input_type'   => 'input',
            'input_format' => 'number',
            'access'       => 'read',
            'sort_order'   => 50,
            'props'        => [
                'type' => 'number',
                'step' => 1,
                'min'  => 0,
            ],
            'hidable'      => true,
        ],
        'minimum'           => [
            'cast'         => 'int',
            'rule'         => 'integer',
            'input_type'   => 'input',
            'input_format' => 'number',
            'access'       => 'read',
            'sort_order'   => 50,
            'props'        => [
                'type' => 'number',
                'step' => 1,
                'min'  => 0,
            ],
            'hidable'      => true,
        ],
        'maximum'           => [
            'cast'         => 'int',
            'rule'         => 'integer',
            'input_type'   => 'input',
            'input_format' => 'number',
            'access'       => 'read',
            'sort_order'   => 50,
            'props'        => [
                'type' => 'number',
                'step' => 1,
                'min'  => 0,
            ],
            'hidable'      => true,
        ],
        'stock_checkout'    => [
            'cast'       => 'int',
            'rule'       => 'integer',
            'access'     => 'read',
            'sort_order' => 10,
            'input_type' => 'selectbox',
            'relation'   => 'getStockCheckouts',
            'hidable'    => true,
        ],
        'stock_status'      => [
            'cast'       => 'int',
            'rule'       => 'integer',
            'access'     => 'read',
            'sort_order' => 10,
            'input_type' => 'selectbox',
            'relation'   => 'getStockStatuses',
            'hidable'    => true,
        ],
        'sku'               => [
            'cast'       => 'string',
            'rule'       => 'max:64|nullable',
            'input_type' => 'input',
            'access'     => 'read',
            'sort_order' => 30,
            'hidable'    => true,
        ],
        'location'          => [
            'cast'       => 'string',
            'rule'       => 'max:128',
            'input_type' => 'input',
            'access'     => 'read',
            'sort_order' => 40,
            'hidable'    => true,
        ],
        'keyword'           => [
            'cast'       => 'string',
            'rule'       => 'max:128',
            'input_type' => 'input',
            'access'     => 'read',
            'sort_order' => 40,
            'hidable'    => true,
        ],
        'date_available'    => [
            'cast'       => 'date',
            'rule'       => 'date',
            'input_type' => 'date',
            'access'     => 'read',
            'sort_order' => 40,
            'hidable'    => true,
        ],
        'sort_order'        => [
            'cast'       => 'int',
            'rule'       => 'integer',
            'input_type' => 'input',
            'access'     => 'read',
            'sort_order' => 1,
            'props'      => [
                'type' => 'number',
                'step' => 1,
                'min'  => 0,
            ],
            'hidable'    => true,
        ],
        'shipping'          => [
            'cast'       => 'int',
            'rule'       => '',
            'input_type' => 'checkbox',
            'access'     => 'read',
            'sort_order' => 1,
            'hidable'    => true,
        ],
        'free_shipping'     => [
            'cast'       => 'int',
            'rule'       => '',
            'input_type' => 'checkbox',
            'access'     => 'read',
            'sort_order' => 110,
            'hidable'    => true,
        ],
        'ship_individually' => [
            'cast'       => 'int',
            'rule'       => '',
            'input_type' => 'checkbox',
            'access'     => 'read',
            'sort_order' => 100,
            'hidable'    => true,
        ],
        'shipping_price'    => [
            'cast'         => 'float',
            'rule'         => 'integer',
            'input_type'   => 'input',
            'input_format' => 'number',
            'access'       => 'read',
            'sort_order'   => 50,
            'props'        => [
                'type' => 'number',
                'step' => 0.01,
                'min'  => 0,
            ],
            'hidable'      => true,
        ],
        'length'            => [
            'cast'         => 'float',
            'rule'         => 'number',
            'input_type'   => 'input',
            'input_format' => 'number',
            'access'       => 'read',
            'sort_order'   => 50,
            'props'        => [
                'type' => 'number',
                'step' => 0.01,
                'min'  => 0,
            ],
            'hidable'      => true,
        ],
        'width'             => [
            'cast'         => 'float',
            'rule'         => 'number',
            'input_type'   => 'input',
            'input_format' => 'number',
            'access'       => 'read',
            'sort_order'   => 50,
            'props'        => [
                'type' => 'number',
                'step' => 0.01,
                'min'  => 0,
            ],
            'hidable'      => true,
        ],
        'height'            => [
            'cast'         => 'float',
            'rule'         => 'number',
            'input_type'   => 'input',
            'input_format' => 'number',
            'access'       => 'read',
            'sort_order'   => 50,
            'props'        => [
                'type' => 'number',
                'step' => 0.01,
                'min'  => 0,
            ],
            'hidable'      => true,
        ],
        'length_class_id'   => [
            'cast'       => 'int',
            'rule'       => 'integer',
            'access'     => 'read',
            'sort_order' => 10,
            'input_type' => 'selectbox',
            'relation'   => 'getLengthClasses',
            'hidable'    => true,
        ],
        'weight'            => [
            'cast'         => 'float',
            'rule'         => 'number',
            'input_type'   => 'input',
            'input_format' => 'number',
            'access'       => 'read',
            'sort_order'   => 50,
            'props'        => [
                'type' => 'number',
                'step' => 0.01,
                'min'  => 0,
            ],
            'hidable'      => true,
        ],
        'weight_class_id'   => [
            'cast'       => 'int',
            'rule'       => 'integer',
            'access'     => 'read',
            'sort_order' => 10,
            'input_type' => 'selectbox',
            'relation'   => 'getWeightClasses',
            'hidable'    => true,
        ],

    ];
    protected $images = [];
    /** seo-keywords */
    protected $keywords = [];
    protected $thumbURL;

    public static $auditExcludes = ['sku'];
    /**
     * @var string
     * @see Product::getProducts()
     */
    public static $searchMethod = 'getProducts',
        $searchParams = [
        'with_final_price',
        'with_discount_price',
        'with_special_price',
        'with_review_count',
        'with_rating',
        'with_stock_info',
        'with_option_count',

        'filter' =>
            [
                'keyword',
                'keyword_search_parameters'
                => [
                    'match'     => 'all',
                    'search_by' => [
                        'name',
                        'model',
                        'sku'
                    ]
                ],
                'only_enabled',
                'category_id',
                'customer_group_id',
                'language_id',
                'store_id',
                // current date for comparison with available_date and also for promotions
                'date',
            ],
        //pagination
        'sort',
        'order',
        'start',
        'limit',
    ];

    public static function boot()
    {
        parent::boot();
        static::updated(
            function ($product) {
                $product->categories->each(
                    function ($category) {
                        $category->setUpdatedAt($category->freshTimestamp());
                        $category->save(); /// Will trigger updated on the category model
                    }
                );
            }
        );
    }

    /**
     * @param mixed $value
     */
    public function setSettings($value)
    {
        $this->attributes['settings'] = serialize($value);
    }

    /**
     * @param QueryBuilder $builder
     * @param int $customer_group_id
     * @param Carbon|null $toDate
     * @return string - sql sub-query of final price
     */
    public static function scopeWithFinalPrice($builder, $customer_group_id, Carbon $toDate = null)
    {
        if (!($toDate instanceof Carbon)) {
            $inc = "NOW()";
        } else {
            $inc = "'" . $toDate->toDateTimeString() . "'";
        }

        $sql = " ( SELECT p2sp.price
                    FROM " . Registry::db()->table_name("product_specials") . " p2sp
                    WHERE p2sp.product_id = " . Registry::db()->table_name("products") . ".product_id
                            AND p2sp.customer_group_id = '" . (int)$customer_group_id . "'
                            AND ((p2sp.date_start IS NULL OR p2sp.date_start < " . $inc . ")
                            AND (p2sp.date_end IS NULL OR p2sp.date_end > " . $inc . "))
                    ORDER BY p2sp.priority ASC, p2sp.price ASC 
                    LIMIT 1
                 ) ";
        $sql = "COALESCE( " . $sql . ", " . Registry::db()->table_name("products") . ".price)";
        $builder->selectRaw($sql . " as final_price");
        return $sql;
    }

    /**
     * @param QueryBuilder $builder
     * @param bool $only_enabled
     */
    public static function scopeWithReviewCount($builder, $only_enabled = true)
    {
        $sql = " ( SELECT COUNT(rw.review_id)
                     FROM " . Registry::db()->table_name("reviews") . " rw
                     WHERE " . Registry::db()->table_name("products") . ".product_id = rw.product_id ";
        if ($only_enabled) {
            $sql .= " AND status = 1 ";
        }
        $sql .= "GROUP BY rw.product_id) AS review_count ";
        $builder->selectRaw($sql);
    }

    /**
     * @param QueryBuilder $builder
     * @param bool $only_enabled
     */
    public static function scopeWithOptionCount($builder, $only_enabled = true)
    {
        $sql = "( SELECT COUNT(po.product_option_id)
                 FROM " . Registry::db()->table_name("product_options") . " po
                 WHERE " . Registry::db()->table_name("products") . ".product_id = po.product_id
                    AND (po.group_id = 0 OR po.group_id IS NULL) ";
        if ($only_enabled) {
            $sql .= " AND status = 1 ";
        }
        $sql .= ") as option_count";
        $builder->selectRaw($sql);
    }

    /**
     * @param QueryBuilder $builder
     * @param bool $only_enabled
     */
    public static function scopeWithAvgRating($builder, $only_enabled = true)
    {
        $db = Registry::db();
        $sql = " ( SELECT ROUND(AVG(rw.rating))
                 FROM " . $db->table_name("reviews") . " rw
                 WHERE " . $db->table_name("products") . ".product_id = rw.product_id ";
        if ($only_enabled) {
            $sql .= " AND status = 1 ";
        }
        $sql .= "GROUP BY rw.product_id) AS rating ";
        $builder->selectRaw($sql);
    }

    /**
     * @param QueryBuilder $builder
     * @param int $customer_group_id
     * @param null $date
     */
    public static function scopeWithFirstSpecialPrice($builder, $customer_group_id, $date = null)
    {
        $db = Registry::db();
        if ($date) {
            if ($date instanceof Carbon) {
                $now = $date->toDateTimeString();
            } else {
                $now = Carbon::parse($date)->toDateTimeString();
            }
        } else {
            $now = Carbon::now()->toDateTimeString();
        }

        $sql = "(SELECT price
                FROM " . $db->table_name("product_specials") . " ps
                WHERE ps.product_id = " . $db->table_name("products") . ".product_id
                        AND customer_group_id = '" . $customer_group_id . "'
                        AND ((date_start IS NULL OR date_start < '" . $now . "')
                        AND (date_end IS NULL OR date_end > '" . $now . "'))
                ORDER BY ps.priority ASC, ps.price ASC
                LIMIT 1) as special_price";
        $builder->selectRaw($sql);
    }

    /**
     * @param QueryBuilder $builder
     * @param int $customer_group_id
     * @param Carbon|string|null $date
     */
    public static function scopeWithFirstDiscountPrice($builder, $customer_group_id, $date = null)
    {
        $db = Registry::db();
        if ($date) {
            if ($date instanceof Carbon) {
                $now = $date->toDateTimeString();
            } else {
                $now = Carbon::parse($date)->toDateTimeString();
            }
        } else {
            $now = Carbon::now()->toDateTimeString();
        }

        $sql = "(SELECT price
                FROM " . $db->table_name("product_discounts") . " pd
                WHERE pd.product_id = " . $db->table_name("products") . ".product_id
                        AND quantity = 1
                        AND customer_group_id = '" . $customer_group_id . "'
                        AND ((date_start IS NULL OR date_start < '" . $now . "')
                        AND (date_end IS NULL OR date_end > '" . $now . "'))
                ORDER BY pd.priority ASC, pd.price ASC
                LIMIT 1) as discount_price";
        $builder->selectRaw($sql);
    }

    /**
     * @param QueryBuilder $builder
     */
    public static function scopeWithStockInfo($builder)
    {
        $db = Registry::db();
        $sql = "(SELECT CASE WHEN COALESCE(" . $db->table_name('products') . ".subtract,0) + SUM(COALESCE(pov.subtract,0)) > 0 THEN 1 ELSE 0 END
                FROM " . $db->table_name("product_option_values") . " pov
                WHERE pov.product_id = " . $db->table_name('products') . ".product_id
                GROUP BY pov.product_id) as subtract";
        $builder->selectRaw($sql);
        $sql = "(SELECT COALESCE(" . $db->table_name('products') . ".quantity,0) + SUM(COALESCE(pov.quantity,0))
                FROM " . $db->table_name("product_option_values") . " pov
                WHERE pov.product_id = " . $db->table_name('products') . ".product_id 
                GROUP BY pov.product_id) as quantity ";
        $builder->selectRaw($sql);
    }

    /**
     * @param QueryBuilder $builder
     */
    public static function scopeWithCategoryIds($builder)
    {
        $db = Registry::db();
        $sql = "(SELECT GROUP_CONCAT(p2c.category_id)
                FROM " . $db->table_name("products_to_categories") . " p2c
                WHERE p2c.product_id = " . $db->table_name('products') . ".product_id
                GROUP BY p2c.product_id) as category_ids";
        $builder->selectRaw($sql);
    }

    /**
     * @return HasOne
     */
    public function stock_status()
    {
        return $this->hasOne(StockStatus::class, 'stock_status_id', 'stock_status_id')
            ->where('language_id', '=', static::$current_language_id);
    }

    /**
     * @return BelongsToMany
     */
    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'coupons_products', 'product_id', 'coupon_id');
    }

    /**
     * @return HasMany
     */
    public function descriptions()
    {
        return $this->hasMany(ProductDescription::class, 'product_id');
    }

    /**
     * @return HasOne
     */
    public function description()
    {
        return $this->hasOne(ProductDescription::class, 'product_id', 'product_id')
            ->where('language_id', '=', static::$current_language_id);
    }

    /**
     * @return HasMany
     */
    public function discounts()
    {
        return $this->hasMany(ProductDiscount::class, 'product_id');
    }

    /**
     * @return HasMany
     */
    public function options()
    {
        return $this->hasMany(ProductOption::class, 'product_id');
    }

    /**
     * TODO: ???is needed?
     *
     * @return HasMany
     */
    public function option_descriptions()
    {
        return $this->hasMany(ProductOptionDescription::class, 'product_id');
    }

    /**TODO: ???is needed?
     *
     * @return HasMany
     */
    public function option_values()
    {
        return $this->hasMany(ProductOptionValue::class, 'product_id');
    }

    /**TODO: ???is needed?
     *
     * @return HasMany
     */
    public function option_value_descriptions()
    {
        return $this->hasMany(ProductOptionValueDescription::class, 'product_id');
    }

    /**
     * @return HasMany
     */
    public function specials()
    {
        return $this->hasMany(ProductSpecial::class, 'product_id');
    }

    /**
     * @return HasMany
     */
    public function tags()
    {
        return $this->hasMany(ProductTag::class, 'product_id');
    }

    /**
     * @return HasMany
     */
    public function tagsByLanguage()
    {
        return $this->hasMany(ProductTag::class, 'product_id')
            ->where('language_id', '=', static::$current_language_id);
    }

    /**
     * @return BelongsToMany
     */
    public function related()
    {
        return $this->belongsToMany(Product::class, 'products_related', 'product_id', 'related_id');
    }

    /**
     * @return HasMany
     */
    public function active_reviews()
    {
        return $this->hasMany(Review::class, 'product_id', 'product_id')
            ->where('status', '=', 1);
    }

    /**
     * @return HasMany
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'product_id', 'product_id');
    }

    /**
     * @return BelongsToMany
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'products_to_categories', 'product_id', 'category_id');
    }

    /**
     * @return HasOne
     */
    public function manufacturer()
    {
        return $this->hasOne(Manufacturer::class, 'manufacturer_id', 'manufacturer_id');
    }

    /**
     * @return BelongsToMany
     */
    public function downloads()
    {
        return $this->belongsToMany(Download::class, 'products_to_downloads', 'product_id', 'download_id');
    }

    /**
     * @return BelongsToMany
     */
    public function stores()
    {
        return $this->belongsToMany(Store::class, 'products_to_stores', 'product_id', 'store_id');
    }

    /**
     * @return MorphMany
     */
    public function attributes()
    {
        return $this->morphMany(ObjectAttributeValue::class, 'object');
    }

    /**
     * @return array
     */
    public function getProductTypes()
    {
        return Registry::db()->table('object_types as ot')
            ->join('object_type_descriptions as otd', 'ot.object_type_id', '=', 'otd.object_type_id')
            ->where(
                [
                    'ot.object_type'  => 'Product',
                    'ot.status'       => 1,
                    'otd.language_id' => static::$current_language_id,
                ]
            )
            ->select('otd.object_type_id as id', 'otd.name')
            ->get()
            ->toArray();
    }

    /**
     * @return array
     * @throws ReflectionException
     * @throws AException
     * @throws InvalidArgumentException
     */
    public function getProductCategories()
    {
        $categories = Category::getCategories(0);
        $product_categories = [];
        foreach ($categories as $category) {
            $product_categories[] = (object)[
                'id'   => $category['category_id'],
                'name' => htmlspecialchars_decode($category['name']),
            ];
        }
        return $product_categories;
    }

    public function getProductStores()
    {
        $stores = Store::active()->select(['store_id as id', 'name'])->get();
        $result[] = (object)['id' => 0, 'name' => 'Default'];
        foreach ($stores as $store) {
            $result[] = (object)['id' => $store->id, 'name' => $store->name];
        }
        return $result;
    }

    public function getManufacturers()
    {
        $manufacturers = Manufacturer::select(['manufacturer_id as id', 'name'])->get();
        $result = [];
        foreach ($manufacturers as $manufacturer) {
            $result[] = (object)['id' => $manufacturer->id, 'name' => $manufacturer->name];
        }
        return $result;
    }

    public function getTaxClasses()
    {
        $tax_classes = TaxClass::with('description')->get();
        $result = [];
        $result[] = (object)['id' => 0, 'name' => Registry::language()->get('text_none')];
        foreach ($tax_classes as $tax_class) {
            $result[] = (object)['id' => $tax_class->tax_class_id, 'name' => $tax_class->description->title];
        }
        return $result;
    }

    /**
     * @return array
     * @throws AException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function getStockCheckouts()
    {
        $language = Registry::language();
        return [
            (object)[
                'id'   => '',
                'name' => $language->get('text_default'),
            ],
            (object)[
                'id'   => 0,
                'name' => $language->get('text_no'),
            ],
            (object)[
                'id'   => 1,
                'name' => $language->get('text_yes'),
            ],
        ];
    }

    /**
     * @param int $language_id
     *
     * @return array
     */
    public function getStockStatuses($language_id = 0)
    {
        $language_id = $language_id ?: static::$current_language_id;
        $stock_statuses = StockStatus::where('language_id', '=', $language_id)
            ->select(['stock_status_id as id', 'name'])
            ->get();
        $result = [];
        foreach ($stock_statuses as $stock_status) {
            $result[] = (object)[
                'id'   => $stock_status->id,
                'name' => $stock_status->name,
            ];
        }
        return $result;
    }

    public function getLengthClasses()
    {
        $length_classes = LengthClass::with('description')->get();
        $result = [];
        foreach ($length_classes as $length_class) {
            $result[] = (object)[
                'id'   => $length_class->length_class_id,
                'name' => $length_class->description->title
            ];
        }
        return $result;
    }

    public function getWeightClasses()
    {
        $weight_classes = WeightClass::with('description')->get();
        $result = [];
        foreach ($weight_classes as $weight_class) {
            $result[] = (object)['id' => $weight_class->weight_class_id, 'name' => $weight_class->description->title];
        }
        return $result;
    }

    /**
     * @return array
     * @throws ReflectionException|Exception
     */
    public function getAllData()
    {
        // eagerLoading!
        $rels = array_keys(static::getRelationships('HasMany', 'HasOne', 'belongsToMany'));
        unset($rels['options']);
        $this->load($rels);
        $data = $this->toArray();
        foreach ($this->options as $option) {
            $data['options'][] = $option->getAllData();
        }
        $data['keywords'] = $this->keywords();
        return $data;
    }

    /**
     * @return mixed
     * @throws ReflectionException
     * @throws AException
     * @throws InvalidArgumentException
     */
    public function thumbnail()
    {
        if ($this->thumbURL) {
            return $this->thumbURL;
        }

        $resource = new AResource('image');
        $thumbnail = $resource->getMainThumb(
            'products',
            $this->product_id,
            Registry::config()->get('config_image_thumb_width'),
            Registry::config()->get('config_image_thumb_height')
        );
        return $this->thumbURL = $thumbnail['thumb_url'];
    }

    /**
     * @return array
     * @throws ReflectionException
     * @throws AException
     * @throws InvalidArgumentException
     */
    public function images()
    {
        $config = Registry::config();
        if ($this->images) {
            return $this->images;
        }
        $resource = new AResource('image');
        // main product image
        $sizes = [
            'main'  => [
                'width'  => $config->get('config_image_popup_width'),
                'height' => $config->get('config_image_popup_height'),
            ],
            'thumb' => [
                'width'  => $config->get('config_image_thumb_width'),
                'height' => $config->get('config_image_thumb_height'),
            ],
        ];
        $this->images['image_main'] = $resource->getResourceAllObjects('products', $this->getKey(), $sizes, 1, false);
        if ($this->images['image_main']) {
            $this->images['image_main']['sizes'] = $sizes;
        }

        // additional images
        $sizes = [
            'main'   => [
                'width'  => $config->get('config_image_popup_width'),
                'height' => $config->get('config_image_popup_height'),
            ],
            'thumb'  => [
                'width'  => $config->get('config_image_additional_width'),
                'height' => $config->get('config_image_additional_height'),
            ],
            'thumb2' => [
                'width'  => $config->get('config_image_thumb_width'),
                'height' => $config->get('config_image_thumb_height'),
            ],
        ];
        $this->images['images'] = $resource->getResourceAllObjects('products', $this->getKey(), $sizes, 0, false);
        return $this->images;
    }

    /**
     * @return int
     */
    public function isStockTrackable()
    {
        $track_status = 0;

        //check product option values
        if ($this->product_options && $this->product_options->values) {
            foreach ($this->product_options->values as $opv) {
                /** @var ProductOptionValue $opv */
                $track_status += $opv->subtract;
            }
        }

        //if no options - check whole product subtract
        if (!$track_status && !$this->product_options && !$this->product_options->values) {
            //check main product
            $track_status = $this->subtract;
        }
        return $track_status;
    }

    /**
     * @return int
     */
    public function hasAnyStock()
    {
        $total_quantity = 0;
        //check product option values
        $option_values = ProductOption::select(['product_option_values.quantity', 'product_option_values.subtract'])
            ->where(
                [
                    'product_options.product_id' => $this->product_id,
                    'status'                     => 1,
                ]
            )
            ->join(
                'product_option_values',
                'product_option_values.product_option_id',
                '=',
                'product_options.product_option_id'
            )
            ->get();
        if ($option_values) {
            $notrack_qnt = 0;
            foreach ($option_values as $row) {
                //if tracking of stock disabled - set quantity as big
                if (!$row->subtract) {
                    $notrack_qnt += 10000000;
                    continue;
                }
                $total_quantity += max($row->quantity, 0);
            }
        } else {
            //get product quantity without options
            $total_quantity = $this->quantity;
        }

        return $total_quantity;
    }

    public function updateImages($data = [], $language_id = null)
    {
        if (!$data['images'] || !is_array($data['images'])) {
            return false;
        }
        if (!$language_id && $data['language_id']) {
            $language_id = (int)$data['language_id'];
        }

        $resource_mdl = new ResourceLibrary();
        $desc = $this->descriptions()->get()->toArray();

        if (!$language_id) {
            $title = current($desc)['name'];
        } else {
            $title = $desc[$language_id]['name'];
        }

        $result = $resource_mdl->updateImageResourcesByUrls($data, 'products', $this->product_id, $title, $language_id);
        if ($resource_mdl->errors()) {
            $this->errors = array_merge($this->errors, $resource_mdl->errors());
        }
        Registry::cache()->flush('product');
        return $result;
    }

    /**
     * @param array $data - nested array of options with descriptions, values and value descriptions
     *
     * @return bool
     * @throws Exception
     */
    public function replaceOptions($data)
    {
        $productId = $this->product_id;
        if (!$productId) {
            return false;
        }
        $this->options()->forceDelete();
        $resource_mdl = new ResourceLibrary();
        foreach ($data as $option) {
            $option['product_id'] = $productId;
            $option['attribute_id'] = 0;
            unset($option['product_option_id']);

            $optionData = $this->removeSubArrays($option);

            $optionObj = new ProductOption();
            $optionObj->fill($optionData)->save();
            $productOptionId = $optionObj->getKey();
            unset($optionObj);

            foreach ((array)$option['descriptions'] as $option_description) {
                $option_description['product_id'] = $productId;
                $option_description['product_option_id'] = $productOptionId;
                $optionDescData = $this->removeSubArrays($option_description);

                $optionDescObj = new ProductOptionDescription();
                $optionDescObj->fill($optionDescData)->save();
                unset($optionDescObj);
            }

            foreach ((array)$option['values'] as $option_value) {
                $option_value['product_id'] = $productId;
                $option_value['product_option_id'] = $productOptionId;
                $option_value['attribute_value_id'] = 0;

                $optionValueData = $this->removeSubArrays($option_value);
                $optionValueObj = new ProductOptionValue();
                $optionValueObj->fill($optionValueData)->save();
                $productOptionValueId = $optionValueObj->getKey();

                unset($optionValueObj);

                $optionValueDescData = [];
                foreach ((array)$option_value['descriptions'] as $option_value_description) {
                    $option_value_description['product_id'] = $productId;
                    $option_value_description['product_option_value_id'] = $productOptionValueId;

                    $optionValueDescData = $this->removeSubArrays($option_value_description);
                    $optionValueDescObj = new ProductOptionValueDescription();
                    $optionValueDescObj->fill($optionValueDescData)->save();
                    unset($optionValueDescObj);
                }
                if ($option_value['images']) {
                    $title = $optionValueDescData['name'];
                    $title = is_array($title) ? current($title) : (string)$title;

                    $language_id = $optionValueDescData['language_id'];
                    $language_id = is_array($language_id) ? current($language_id) : (string)$language_id;

                    $result = $resource_mdl->updateImageResourcesByUrls(
                        $option_value,
                        'product_option_value',
                        $productOptionValueId,
                        $title,
                        $language_id
                    );

                    if (!$result) {
                        $this->errors = array_merge($this->errors, $resource_mdl->errors());
                    }
                }
            }
        }
        Registry::cache()->flush('product');
        return true;
    }

    protected function removeSubArrays(array $array)
    {
        foreach ($array as $k => &$v) {
            if (is_array($v)) {
                unset($array[$k]);
            }
        }
        return $array;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function keywords()
    {
        if ($this->keywords) {
            return $this->keywords;
        }

        $urlAliases = UrlAlias::where('query', '=', 'product_id=' . $this->product_id)->get();
        if ($urlAliases) {
            foreach ($urlAliases as $urlAlias) {
                $this->keywords[$urlAlias->language_id] = [
                    'keyword'     => H::SEOEncode($urlAlias->keyword, 'product_id', $this->product_id),
                    'language_id' => $urlAlias->language_id,
                ];
            }
        }
        return $this->keywords;
    }

    public function auditables()
    {
        return $this->morphMany(Audit::class, 'auditable');
    }

    /**
     * @param $product_id
     *
     * @return array
     * @throws Exception
     */
    public static function getProductInfo($product_id)
    {
        $product_id = (int)$product_id;
        if (!$product_id) {
            return [];
        }
        /** @var Product $product */
        $product = Product::with('description', 'categories', 'stores', 'tagsByLanguage')
            ->find($product_id);
        if (!$product) {
            return [];
        }

        $productArray = $product->toArray();
        $output = $productArray;
        unset($output['description'], $output['keyword']);

        if ($productArray['description']) {
            $output = array_merge($output, $productArray['description']);
        }
        $keywords = $product->keywords();
        $output['keyword'] = $keywords[static::$current_language_id]['keyword'];
        $output['has_track_options'] = $product->hasTrackOptions();

        if ($output['has_track_options']) {
            $output['quantity'] = $product->hasAnyStock();
        }
        return $output;
    }

    public function hasTrackOptions()
    {
        $query = ProductOptionValue::select();
        $query->join('product_options',
            function ($join) {
                /** @var JoinClause $join */
                $join->on(
                    'product_options.product_option_id',
                    '=',
                    'product_option_values.product_option_id'
                );
                $join->where('product_option_values.subtract', '=', 1);
            }
        );
        $query->where(
            'product_options.product_id',
            '=',
            $this->getKey()
        );
        return ($query->count());
    }

    /**
     * @param array $product_data
     *
     * @return Product
     * @throws Exception
     */
    public static function createProduct(array $product_data)
    {
        $product_data['new_product'] = true;
        if (empty($product_data['product_store'])) {
            $product_data['product_store'] = [0 => 0];
        }
        $product = new Product($product_data);
        $product->save();
        $productId = $product->product_id;
        if ($productId) {
            if ($product_data['product_description']) {
                $description = new ProductDescription($product_data['product_description']);
                $product->descriptions()->save($description);
            }
            if ($product_data['keyword'] || $product_data['product_description']['name']) {
                UrlAlias::setProductKeyword(
                    $product_data['keyword'] ?: $product_data['product_description']['name'],
                    $product
                );
            }
            self::updateProductLinks($product, $product_data);
        }
        return $product;
    }

    /**
     *
     * @return bool|array
     * @throws AException
     * @throws ReflectionException
     */
    /*   public function copyProduct()
       {
           $product_id = $this->getKey();
           if (!$product_id) {
               return false;
           }

   return false;
           $this->load('descriptions');
           $clone = $this->replicate();
           $clone->push(); //Push before to get id of $clone


           return $clone->product_id;



















           $db = Registry::db();

           $productInfo = $this->getAllData();

           foreach ($productInfo['descriptions'] as &$description) {
               unset($description['product_id']);
               $description['name'] .= '(Copy)';
           }
           $productInfo['sku'] = $productInfo['sku'] ? $productInfo['sku'].' (copy)' : null;
           foreach ($productInfo['options'] as &$option) {
               unset(
                   $option['product_id'],
                   $option['product_option_id']
               );
               foreach ($option['descriptions'] as &$optionDesc) {
                   unset(
                       $optionDesc['product_id'],
                       $optionDesc['product_option_id']
                   );
               }
               foreach ($option['values'] as &$optionValues) {
                   unset(
                       $optionValues['product_id'],
                       $optionValues['product_option_id'],
                       $optionValues['product_option_value_id']
                   );
                   $optionValues['sku'] = $optionValues['sku'] ? $optionValues['sku'].' (copy)' : null;
                   foreach ($optionValues['descriptions'] as &$optionDesc) {
                       unset(
                           $optionDesc['product_id'],
                           $optionDesc['product_option_value_id']
                       );
                   }
               }
           }

           foreach ($productInfo['discounts'] as &$discount) {
               unset($discount['product_id']);
           }


           unset(
               $productInfo['product_id'],
               $productInfo['description'],
               $productInfo['uuid'],
               $productInfo['reviews'],
               $productInfo['tags_by_language']
           );
           foreach($productInfo['tags'] as &$r){
               unset($r['product_id'],$r['id']);
           }
           foreach($productInfo['options'] as &$r){
               unset($r['product_id'],$r['id']);
           }

           //set status to off for cloned product
           $productInfo['status'] = 0;

           //get product resources
   //        $rm = new AResourceManager();
   //        $resources = $rm->getResourcesList(
   //            [
   //                'object_name' => 'products',
   //                'object_id'   => $product_id,
   //                'sort'        => 'sort_order',
   //            ]);
           $db->beginTransaction();
           $product = new Product($productInfo);
           $product->save();
           $productId = $product->product_id;

           if ($productId) {
               foreach($productInfo['descriptions'] as $item) {
                   $description = new ProductDescription($item);
                   $product->descriptions()->save($item);
               }

   //            UrlAlias::setProductKeyword($productInfo['keywords'] ?: $product_data['product_description']['name'], $productId);
   //            self::updateProductLinks($product, $product_data);
               return $productId;
           }

           exit;

           foreach ($data['product_discount'] as $item) {
               //sign to prevent converting date from display format to iso
               $item['iso_date'] = true;
               $this->addProductDiscount($new_product_id, $item);
           }
           foreach ($data['product_special'] as $item) {
               $item['iso_date'] = true;
               $this->addProductSpecial($new_product_id, $item);
           }

           $this->updateProductLinks($new_product_id, $data);
           $this->_clone_product_options($new_product_id, $data);

           foreach ($resources as $r) {
               $rm->mapResource(
                   'products',
                   $new_product_id,
                   $r['resource_id']
               );
           }
           $this->cache->flush('product');

           //clone layout for the product if present
           $layout_clone_result = $this->_clone_product_layout($product_id, $new_product_id);

           return [
               'name'         => $data['name'],
               'id'           => $new_product_id,
               'layout_clone' => $layout_clone_result,
           ];
       }*/

    /**
     * @param int $product_id
     * @param array $product_data
     *
     * @return bool
     * @throws Exception
     */
    public static function updateProduct(int $product_id, array $product_data)
    {
        $product = Product::find($product_id);
        if (!$product) {
            return false;
        }
        if (isset($product_data['product_category'])) {
            $product->load('categories');
            $product_data['product_category_prev'] = $product->categories->pluck('category_id')->toArray();
        }
        if (isset($product_data['product_tags'])) {
            $product->load('tags');
        }

        $product->update($product_data);
        $descriptionFields = $product->description()->getModel()->getFillable();
        $update = [];
        foreach ($descriptionFields as $fieldName) {
            if (isset($product_data[$fieldName])) {
                $update[$fieldName] = $product_data[$fieldName];
            }
        }
        $productDescriptionId = ProductDescription::where('product_id', '=', $product->product_id)
            ->where('language_id', '=', Product::$current_language_id)->first()?->id;
        ProductDescription::find($productDescriptionId)?->update($update);

        if (trim($product_data['keyword'])) {
            UrlAlias::setProductKeyword(
                $product_data['keyword'],
                $product
            );
        }

        $attributes = array_filter(
            $product_data,
            function ($k) {
                return (str_starts_with($k, 'attribute_'));
            },
            ARRAY_FILTER_USE_KEY
        );

        if (!empty($attributes) && $product_data['product_type_id']) {
            self::updateProductAttributes($product_id, $product_data['product_type_id'], $attributes);
        }
        self::updateProductLinks($product, $product_data);

        return true;
    }

    /**
     * @param int $productId
     * @param int $productTypeId
     * @param array $attributes
     */
    public static function updateProductAttributes(int $productId, int $productTypeId, array $attributes)
    {
        foreach ($attributes as $name => $value) {
            $attributeId = (int)substr($name, strlen('attribute_'), strlen($name));
            if (!$attributeId) {
                continue;
            }

            $attribute = [
                'object_id'      => $productId,
                'object_type'    => 'Product',
                'object_type_id' => $productTypeId,
                'attribute_id'   => $attributeId,
                'attribute_name' => $name,
            ];

            if (is_array($value)) {
                $value = json_encode($value);
            }

            $attributeVal = [
                'attribute_value' => $value,
            ];

            ObjectAttributeValue::updateOrCreate($attribute, $attributeVal);
        }
    }

    /**
     * @param int|Product $product
     * @param array $product_data
     *
     * @return bool
     * @throws Exception
     */
    public static function updateProductLinks(&$product, array $product_data)
    {
        if (is_numeric($product)) {
            $model = Product::find($product);
        } else {
            $model = $product;
        }

        if (!$model instanceof Product) {
            return false;
        }

        if (!is_array($product_data['product_category_prev'])) {
            $product_data['product_category_prev'] = $model->categories()
                ->where('product_id', '=', $model->getKey())
                ->get()->pluck('category_id')->toArray();
        }

        if (isset($product_data['product_category'])
            && $product_data['product_category'] != $product_data['product_category_prev']) {
            $ids = (array)$product_data['product_category'];
            $product_data['product_category'] = [];
            foreach ($ids as $id) {
                $id = (int)$id;
                if ($id) {
                    $product_data['product_category'][] = $id;
                }
            }
            if ($product_data['product_category']) {
                $model->categories()->sync($product_data['product_category']);
            } else {
                $model->categories()->detach($product_data['product_category_prev']);
            }
        }

        if (isset($product_data['product_store'])) {
            $model->stores()->sync($product_data['product_store']);
        }

        if (isset($product_data['product_download'])) {
            $model->downloads()->sync($product_data['product_download']);
        }

        if (isset($product_data['product_related'])) {
            $model->related()->sync($product_data['product_related']);
        }
        if (isset($product_data['product_tags'])) {
            $tags = explode(',', $product_data['product_tags']);
            if (is_array($tags)) {
                $languageId = static::$current_language_id;
                $productTags = [];
                foreach ($tags as $tag) {
                    $productTag = ProductTag::create([
                        'tag'         => trim($tag),
                        'product_id'  => $model->product_id,
                        'language_id' => $languageId,
                    ]);
                    $productTags[] = $productTag->id;
                }

                if ($product->tags) {
                    ProductTag::where(
                        [
                            'product_id'  => $model->product_id,
                            'language_id' => $languageId,
                        ]
                    )->whereNotIn('id', $productTags)
                        ->forceDelete();
                }
            }
        }
        $model->touch();
        return true;
    }

    /**
     * @param array $product_ids
     *
     * @return bool
     * @throws Exception
     */
    public static function relateProducts($product_ids = [])
    {
        if (!$product_ids || !is_array($product_ids)) {
            return false;
        }
        $product_ids = array_unique($product_ids);
        foreach ($product_ids as $product_id) {
            $key = array_search($product_id, $product_ids);
            $ids = $product_ids;
            unset($ids[$key]);
            $product = Product::find($product_id);
            if ($product) {
                $product->related()->sync($ids);
                $product->touch();
            }
        }

        return true;
    }

    /**
     * @param int $productId
     *
     * @return array|bool
     * @throws AException
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    public static function getProductTypeSettings(int $productId)
    {
        if (!$productId) {
            return false;
        }

        /** @var Product $product */
        $product = self::where('product_id', '=', $productId)->first();
        if (!$product) {
            return false;
        }

        $registry = Registry::getInstance();
        $store_id = $registry->get('config')->get('config_store_id');

        $settings = Setting::where('store_id', $store_id)
            ->where('group', 'object_type')
            ->where('group_id', $product->product_type_id)
            ->get();

        if (!$settings) {
            return false;
        }
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['key']] = $setting['value'];
        }
        return $result;
    }

    /**
     * TODO: remove it!
     * @deprecated
     *
     */
    public function getCatalogOnlyProducts(int $limit = null)
    {
        $db = Registry::db();
        $arSelect = [$db->raw('SQL_CALC_FOUND_ROWS *'), 'pd.name as name'];

        //special prices
        if (Registry::Customer()?->isLogged()) {
            $customer_group_id = (int)Registry::Customer()?->getCustomerGroupId();
        } else {
            $customer_group_id = (int)Registry::config()?->get('config_customer_group_id');
        }

        $sql
            = " ( SELECT p2sp.price
                    FROM " . $db->table_name("product_specials") . " p2sp
                    WHERE p2sp.product_id = " . $db->table_name("products") . ".product_id
                            AND p2sp.customer_group_id = '" . $customer_group_id . "'
                            AND ((p2sp.date_start IS NULL OR p2sp.date_start < NOW())
                            AND (p2sp.date_end IS NULL OR p2sp.date_end > NOW()))
                    ORDER BY p2sp.priority ASC, p2sp.price ASC 
                    LIMIT 1
                 ) ";
        $arSelect[] = $db->raw("COALESCE( " . $sql . ", " . $db->table_name("products") . ".price) as final_price");

        $languageId = (int)Registry::config()->get('storefront_language_id');

        $products_info = Product::select($arSelect)
            ->where('products.catalog_only', '=', 1)
            ->leftJoin('product_descriptions as pd', function ($join) use ($languageId) {
                /** @var JoinClause $join */
                $join->on('products.product_id', '=', 'pd.product_id')
                    ->where('pd.language_id', '=', $languageId);
            })
            ->leftJoin('products_to_stores as p2s', 'products.product_id', '=', 'p2s.product_id')
            ->leftJoin('manufacturers as m', 'products.manufacturer_id', '=', 'm.manufacturer_id')
            ->leftJoin('stock_statuses as ss', function ($join) use ($languageId) {
                /** @var JoinClause $join */
                $join->on('products.stock_status_id', '=', 'ss.stock_status_id')
                    ->where('ss.language_id', '=', $languageId);
            })
            ->active('products');

        if ($limit) {
            $products_info = $products_info->limit($limit);
        }

        $products_info = $products_info->get();

        if (!$products_info) {
            return false;
        }

        return [
            'products_info'  => $products_info->toArray(),
            'total_num_rows' => Registry::db()->sql_get_row_count(),
        ];
    }

    /**
     * @param int $product_id
     *
     * @return array
     */
    public static function getProductOptionsWithValues($product_id)
    {
        if (!(int)$product_id) {
            return [];
        }
        $query = ProductOption::with('description', 'values', 'values.description')
            ->where('product_id', '=', $product_id)
            ->whereRaw('COALESCE(group_id,0) = 0')
            ->active()
            ->orderBy('sort_order');
        //allow to extend this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query);

        $productOptions = $query->useCache('product')->get()?->toArray();

        $elements = HtmlElementFactory::getAvailableElements();
        foreach ($productOptions as &$option) {
            $option['html_type'] = $elements[$option['element_type']]['type'];
        }
        return $productOptions;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function getBestSellerProductIds(array $data)
    {
        $db = Registry::db();
        $aliasOP = $db->table_name('order_products');

        /** @var QueryBuilder $query */
        $query = OrderProduct::select(['order_products.product_id']);
        $query->leftJoin(
            'orders',
            'order_products.order_id',
            '=',
            'orders.order_id'
        )
            ->leftJoin(
                'products',
                'order_products.product_id',
                '=',
                'products.product_id'
            )
            ->where('orders.order_status_id', '>', 0)
            ->where('order_products.product_id', '>', 0)
            ->groupBy('order_products.product_id')
            ->orderBy($db->raw('SUM(' . $aliasOP . '.quantity) '), 'DESC');

        //allow to extend this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, $data);
        return $query->useCache('product')->get()?->pluck('product_id')?->toArray();
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function getBestSellerProducts(array $data)
    {
        $limit = (int)$data['limit'] ?: 20;
        $order = $data['order'];
        $start = (int)$data['start'];
        $sort = $data['sort'];
        $db = Registry::db();
        $config = Registry::config();

        $language_id = (int)$config->get('storefront_language_id');
        $store_id = (int)$config->get('config_store_id');

        $aliasP = $db->table_name('products');
        $aliasPD = $db->table_name('product_descriptions');
        $aliasSS = $db->table_name('stock_statuses');

        $select = [
            $db->raw($aliasSS . '.name as stock'),
            'products.*',
        ];

        $bestSellerIds = self::getBestSellerProductIds($data);

        $query = self::selectRaw($db->raw_sql_row_count() . " " . $aliasPD . ".*")
            ->addSelect($select)
            ->leftJoin('product_descriptions', function ($subQuery) use ($language_id) {
                /** @var JoinClause $subQuery */
                $subQuery->on(
                    'products.product_id',
                    '=',
                    'product_descriptions.product_id'
                )
                    ->where('product_descriptions.language_id', '=', $language_id);
            })
            ->leftJoin(
                'products_to_stores',
                'products.product_id',
                '=',
                'products_to_stores.product_id'
            )
            ->leftJoin('stock_statuses', function ($subQuery) use ($language_id) {
                /** @var JoinClause $subQuery */
                $subQuery->on(
                    'products.stock_status_id',
                    '=',
                    'stock_statuses.stock_status_id'
                )
                    ->where('stock_statuses.language_id', '=', $language_id);
            })
            ->whereIn('products.product_id', $bestSellerIds)
            ->whereRaw($aliasP . '.date_available<=NOW()')
            ->where('products.status', '=', 1)
            ->where('products_to_stores.store_id', '=', $store_id);

        $sort_data = [
            'pd.name'       => 'product_descriptions.name',
            'p.sort_order'  => 'products.sort_order',
            'p.price'       => 'products.price',
            'rating'        => 'rating',
            'date_modified' => 'products.date_modified',
        ];

        if (!array_key_exists($sort, $sort_data)) {
            $sort = 'p.sort_order';
        }
        if (!$order) {
            $order = 'ASC';
        }
        if ($sort === 'pd.name') {
            $query = $query->orderByRaw('LCASE(' . $aliasPD . '.name)', $order);
        } else {
            $query = $query->orderBy($sort_data[$sort], $order);
        }

        if ($start < 0) {
            $start = 0;
        }
        if ($limit) {
            $query = $query->offset($start)->limit($limit);
        }

        //allow to extend this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, $data);
        return $query->useCache('product')->get()?->toArray();
    }

    /**
     * @param array $productIds
     *
     * @return array
     * @throws ReflectionException
     */
    public static function getProductsAllInfo(array $productIds)
    {
        $result = [];
        foreach ($productIds as $productId) {
            $product = Product::find($productId);
            if ($product) {
                $result[] = $product->getAllData();
            }
        }
        return $result;
    }

    /**
     * Destroy the models for the given IDs.
     *
     * @param Collection|array|int $ids
     *
     * @return int
     */
    public static function destroy($ids)
    {
        $IDs = null;
        if ($ids instanceof Collection) {
            $IDs = $ids->all();
        }

        $IDs = is_array($IDs) ? $IDs : func_get_args();
        $arr = [];
        foreach ($IDs as $id) {
            $arr[] = 'product_id=' . $id;
        }

        $aliases = UrlAlias::whereIn('query', $arr)
            ->pluck('url_alias_id');
        UrlAlias::destroy($aliases);

        return parent::destroy($IDs);
    }

    /**
     * @return bool|null
     * @throws Exception
     */
    public function delete()
    {
        UrlAlias::where('query', '=', 'product_id=' . $this->getKey())->delete();
        return parent::delete();
    }

    /**
     * @param array $data
     * @param null|int $attribute_id
     *
     * @return int|false
     * @throws Exception
     */
    public function addProductOption($data = [], $attribute_id = null)
    {
        $product_id = $this->getKey();
        $attribute_id = $attribute_id ?: $data['attribute_id'];
        if (!$product_id) {
            Registry::log()?->error(__CLASS__ . ": " . __FUNCTION__ . ': Unknown product ID');
            return false;
        }

        $data['product_id'] = $product_id;

        $db = Registry::db();
        $db->beginTransaction();
        try {
            /** @var AttributeManager $am */
            $am = ABC::getObjectByAlias('AttributeManager');
            $attribute = $am->getAttribute($attribute_id);
            if ($attribute) {
                $data = array_merge($data, $attribute);
                $attributeDescriptions = $am->getAttributeDescriptions($attribute_id);
                $data['attribute_id'] = $attribute_id;
            } else {
                $data['placeholder'] = $data['option_placeholder'];
                $attributeDescriptions = [];
                $data['attribute_id'] = null;
            }
            $productOption = new ProductOption($data);
            $productOption->save();

            $product_option_id = $productOption->getKey();

            if (!empty($data['option_name'])) {
                $productOption->description()->insert(
                    [
                        'product_option_id'  => $product_option_id,
                        'product_id'         => $product_id,
                        'language_id'        => static::$current_language_id,
                        'name'               => $data['option_name'],
                        'error_text'         => $data['error_text'],
                        'option_placeholder' => $data['placeholder'],
                    ]
                );
            }

            foreach ($attributeDescriptions as $language_id => $descr) {
                $productOption->description()->updateOrInsert(

                    [
                        'product_option_id' => $product_option_id,
                        'product_id'        => $product_id,
                        'language_id'       => $language_id,
                    ],
                    [
                        'name'               => $descr['name'],
                        'error_text'         => $descr['error_text'],
                        'option_placeholder' => $data['placeholder'],
                    ]
                );
            }

            //add empty option value for single value attributes
            $elements_with_options = HtmlElementFactory::getElementsWithOptions();
            if (!in_array($data['element_type'], $elements_with_options)) {
                $optionValue = new ProductOptionValue(
                    [
                        'product_id'        => $product_id,
                        'product_option_id' => $product_option_id,
                    ]
                );
                $optionValue->save();
            }

            $this->touch();
            $db->commit();
        } catch (Exception $e) {
            Registry::log()->error($e->getMessage() . "\n\n" . $e->getTraceAsString());
            $db->rollback();
            return false;
        }

        return $product_option_id;
    }

    public function getProductOptions($group_id = 0)
    {
        if (!$this->getKey()) {
            return [];
        }

        $product_option_data = [];
        $where = ['product_id' => $this->getKey()];
        if ((int)$group_id) {
            $where['group_id'] = (int)$group_id;
        }

        $options = ProductOption::where($where)->orderBy('sort_order')->get();

        if ($options) {
            foreach ($options as $product_option) {
                $product_option_data[] = Product::getProductOption($product_option->product_option_id);
            }
        }

        return $product_option_data;
    }

    public static function getProductOption($option_id)
    {
        $option = ProductOption::with('descriptions')
            ->useCache('product')->find($option_id)
            ?->toArray();

        $optionData = [];
        foreach ($option['descriptions'] as $desc) {
            $optionData['language'][$desc['language_id']] = $desc;
        }
        $option_data = array_merge($option, $optionData);
        $option_data['product_option_value'] = ProductOptionValue::getProductOptionValues($option_id);

        return $option_data;
    }

    /**
     * Search method of model
     * @param array $params = [
     *   common parameters:
     *              - sort
     *              - order
     *              - start
     *              - limit
     *  filter parameters - $params['filter']:
     *              - include - filter by list of product ids
     *              - exclude - do not include products from list of product ids
     *              - category_id
     *              - manufacturer_id
     *              - description
     *              - model
     *              - only_enabled - with status 1 and date_available less than current time
     *              - customer_group_id
     *              - keyword
     *              - keyword_search_parameters - array(
     * 'search_by' => [ .. list of column which use in search such as description, model, sku]
     * 'match' => can be "any" (of words) or "all" or "exact"
     * )
     *              - language_id
     *              - store_id
     *              - price_from
     *              - price_to
     *
     *  parameters for data set:
     *              - with_all
     *              - with_final_price
     *              - with_special_price
     *              - with_discount_price
     *              - with_review_count
     *              - with_option_count
     *              - with_rating
     *              - with_stock_info
     *
     * @return Collection|stdClass
     */
    public static function getProducts(array $params = [])
    {
        $finalPriceSql = '';

        $params['sort'] = $params['sort'] ?? 'products.sort_order';
        $params['order'] = $params['order'] ?? 'ASC';
        $params['start'] = max($params['start'], 0);
        $params['limit'] = $params['limit'] >= 1 ? $params['limit'] : 20;

        $filter = (array)$params['filter'];
        $filter['include'] = $filter['include'] ?? [];
        $filter['exclude'] = $filter['exclude'] ?? [];
        $filter['category_id'] = $filter['category_id'] ?? 0;
        $filter['manufacturer_id'] = $filter['manufacturer_id'] ?? 0;

        $filter['only_enabled'] = (bool)$filter['only_enabled'];
        $filter['customer_group_id'] = $filter['customer_group_id']
            ?? Registry::config()?->get('config_customer_group_id');
        $filter['keyword'] = trim($filter['keyword']);
        $filter['language_id'] = (int)$filter['language_id'] ?: static::$current_language_id;

        if (!isset($filter['store_id'])) {
            $filter['store_id'] = ABC::env('IS_ADMIN') === true
                ? (int)Registry::session()->data['current_store_id']
                : (int)Registry::config()->get('config_store_id');
        } else {
            $filter['store_id'] = (int)$filter['store_id'];
        }

        $db = Registry::db();
        //override to use prepared version of filter inside hooks
        $params['filter'] = $filter;

        //full table names
        $p_table = $db->table_name('products');
        $pd_table = $db->table_name('product_descriptions');
        $pSpecialsTable = $db->table_name('product_specials');

        /** @var Product|QueryBuilder $query */
        $query = self::selectRaw(Registry::db()->raw_sql_row_count() . ' ' . $p_table . '.*');
        if ($params['with_final_price'] || $params['with_all']) {
            /** @see Product::scopeWithFinalPrice() */
            $finalPriceSql = $query->WithFinalPrice($filter['customer_group_id']);
        }
        if ($params['with_special_price'] || $params['with_all']) {
            /** @see Product::scopeWithFirstSpecialPrice() */
            $query->WithFirstSpecialPrice($filter['customer_group_id'], $filter['date']);
        }
        if ($params['with_discount_price'] || $params['with_all']) {
            /** @see Product::scopeWithFirstSpecialPrice() */
            $query->WithFirstDiscountPrice($filter['customer_group_id'], $filter['date']);
        }

        if ($params['with_review_count'] || $params['with_all']) {
            /** @see Product::scopeWithReviewCount() */
            $query->WithReviewCount($filter['only_enabled']);
        }

        if ($params['with_option_count'] || $params['with_all']) {
            /** @see Product::scopeWithOptionCount() */
            $query->WithOptionCount($filter['with_option_count']);
        }

        if ($params['with_rating'] || $params['with_all']) {
            /** @see Product::scopeWithAvgRating() */
            $query->WithAvgRating($filter['only_enabled']);
        }

        if ($params['with_stock_info'] || $params['with_all']) {
            /** @see Product::scopeWithStockInfo() */
            $query->WithStockInfo();
        }

        $query->addSelect(
            [
                'product_descriptions.*',
                'manufacturers.name as manufacturer',
                'stock_statuses.name as stock_status_name',
            ]
        );

        $query->leftJoin(
            'product_descriptions',
            function ($join) use ($filter) {
                /** @var JoinClause $join */
                $join->on('product_descriptions.product_id', '=', 'products.product_id')
                    ->where('product_descriptions.language_id', '=', $filter['language_id']);
            }
        );

        $query->leftJoin(
            'manufacturers',
            function ($join) {
                /** @var JoinClause $join */
                $join->on('manufacturers.manufacturer_id', '=', 'products.manufacturer_id');
            }
        );

        $query->leftJoin(
            'product_tags',
            function ($join) use ($filter) {
                /** @var JoinClause $join */
                $join->on('product_tags.product_id', '=', 'products.product_id')
                    ->where('product_tags.language_id', '=', $filter['language_id']);
            }
        );

        $query->leftJoin(
            'stock_statuses',
            function ($join) use ($filter) {
                /** @var JoinClause $join */
                $join->on('stock_statuses.stock_status_id', '=', 'products.stock_status_id')
                    ->where('stock_statuses.language_id', '=', $filter['language_id']);
            }
        );

        $query->join(
            'products_to_stores',
            function ($join) use ($filter) {
                /** @var JoinClause $join */
                $join->on('products_to_stores.product_id', '=', 'products.product_id')
                    ->where('products_to_stores.store_id', '=', $filter['store_id']);
            }
        );

        if ($filter['only_specials']) {
            $query->join(
                'product_specials',
                function ($join) use ($filter, $pSpecialsTable) {
                    /** @var JoinClause $join */
                    $join->on('product_specials.product_id', '=', 'products.product_id')
                        ->where('product_specials.customer_group_id', '=', (int)$filter['customer_group_id'])
                        ->whereRaw("COALESCE(" . $pSpecialsTable . ".date_start, '1970-01-01')< NOW()")
                        ->whereRaw("COALESCE(" . $pSpecialsTable . ".date_end, NOW() ) >= NOW()");
                }
            );
        }
        if ($filter['only_featured']) {
            $query->where('products.featured', '=', 1);
        }

        if ($filter['keyword']) {
            self::scopeSearchByKeyword($query, $params);
        }
        if ($filter['category_id']) {
            if (is_array($filter['category_id'])) {
                $categoryIds = $filter['category_id'];
                $query->WithCategoryIds();
            } else {
                $mode = $filter['only_enabled'] ? 'active_only' : '';
                $categoryIds = Category::getChildrenIDs($filter['category_id'], $mode);
                $categoryIds[] = $filter['category_id'];
            }

            $query->join(
                "products_to_categories",
                function ($join) use ($categoryIds) {
                    /** @var JoinClause $join */
                    $join->on('products.product_id', '=', 'products_to_categories.product_id')
                        ->whereIn('products_to_categories.category_id', $categoryIds);
                }
            );
        }

        if (isset($filter['status'])) {
            $query->where('products.status', '=', (int)$filter['status']);
        }

        if ($filter['manufacturer_id']) {
            $query->where('products.manufacturer_id', $filter['manufacturer_id']);
        }

        if ((array)$filter['include']) {
            $query->whereIn('products.product_id', (array)$filter['include']);
        }
        if ((array)$filter['exclude']) {
            $query->whereNotIn('products.product_id', (array)$filter['exclude']);
        }
        if ((array)$filter['include_sku']) {
            $query->whereIn('products.sku', (array)$filter['include_sku']);
        }
        if ((array)$filter['exclude_sku']) {
            $query->whereNotIn('products.sku', (array)$filter['exclude_sku']);
        }

        if ($filter['price_from'] || $filter['price_to']) {
            if ($finalPriceSql) {
                $query->whereRaw(
                    $finalPriceSql . ' BETWEEN ' . ((double)$filter['price_from'] ?: 0.0) . ' AND ' . ((double)$filter['price_to'] ?: 100000000)
                );
            } else {
                $query->whereBetween(
                    'price',
                    [
                        (double)$filter['price_from'] ?: 0.0,
                        (double)$filter['price_to'] ?: 100000000
                    ]
                );
            }
        }
        if ($filter['cost_from'] || $filter['cost_to']) {
            if ($finalPriceSql) {
                $query->whereRaw(
                    $finalPriceSql . ' BETWEEN ' . ((double)$filter['cost_from'] ?: 0.0) . ' AND ' . ((double)$filter['cost_to'] ?: 100000000)
                );
            } else {
                $query->whereBetween(
                    'cost',
                    [
                        (double)$filter['cost_from'] ?: 0.0,
                        (double)$filter['cost_to'] ?: 100000000
                    ]
                );
            }
        }

        //show only enabled and available products for storefront!
        if (ABC::env('IS_ADMIN') !== true) {
            if ($filter['date']) {
                if ($filter['date'] instanceof Carbon) {
                    $now = $filter['date']->toDateTimeString();
                } else {
                    $now = Carbon::parse($filter['date'])->toDateTimeString();
                }
            } else {
                $now = Carbon::now()->toDateTimeString();
            }

            $query->where('products.date_available', '<=', $now)
                ->active('products');
        }

        $query->groupBy('products.product_id');

        //NOTE: order by must be raw sql string
        $sort_data = [
            'name'          => "LCASE(" . $pd_table . ".name)",
            'sort_order'    => $p_table . ".sort_order",
            'price'         => "final_price",
            'special'       => "final_price",
            'rating'        => "rating",
            'date_modified' => $p_table . ".date_modified",
            'review'        => "review",
            'viewed'        => $p_table . ".viewed",
        ];

        $orderBy = $sort_data[$params['sort']] ?: 'name';
        if (isset($params['order']) && (strtoupper($params['order']) == 'DESC')) {
            $sorting = "desc";
        } else {
            $sorting = "asc";
        }

        $query->orderByRaw($orderBy . " " . $sorting);

        //pagination
        if (isset($params['start']) || isset($params['limit'])) {
            $params['start'] = max(0, $params['start']);
            if ($params['limit'] < 1) {
                $params['limit'] = 20;
            }
            $query->offset((int)$params['start'])->limit((int)$params['limit']);
        }

        //allow to extend this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, $params);
        $output = $query->useCache('product')->get();
        //add total number of rows into each row
        $output->total = $totalNumRows = $db->sql_get_row_count();
        foreach ($output as &$item) {
            $item->total_num_rows = $totalNumRows;
        }

        return $output;
    }

    /**
     * Wrapper. Needs to be used in the abstract calls for listing blocks etc
     *
     * @param int $limit
     * @param array $filter
     *
     * @return Collection
     */
    public static function getPopularProducts($limit = 0, $filter = [])
    {
        $limit = (int)$limit;
        return static::getProducts(
            [
                'with_all' => true,
                'sort'     => 'viewed',
                'order'    => 'DESC',
                'limit'    => $limit,
                'filter'   => $filter,
            ]
        );
    }

    /**
     * @param $limit
     * @param $filter
     *
     * @return Collection
     */
    public static function getLatestProducts($limit = 0, $filter = [])
    {
        $limit = (int)$limit;
        return static::getProducts(
            [
                'with_all' => true,
                'sort'     => 'date_modified',
                'order'    => 'DESC',
                'limit'    => $limit ?: 20,
                'filter'   => $filter,
            ]
        );
    }

    /**
     * @param array $data
     *
     * @return Collection
     *
     */
    public static function getProductSpecials($data = [])
    {
        return static::getProducts(
            [
                'with_all' => true,
                'sort'     => $data['sort'] ?: 'sort_order',
                'order'    => $data['order'] ?: 'ASC',
                'start'    => (int)$data['start'],
                'limit'    => (int)$data['limit'],
                'filter'   => array_merge(
                    (array)$data['filter'],
                    ['only_specials' => true]
                ),
            ]
        );
    }

    public static function getFeaturedProducts($data = [])
    {
        return static::getProducts(
            [
                'with_all' => true,
                'sort'     => $data['sort'] ?: 'sort_order',
                'order'    => $data['order'] ?: 'ASC',
                'start'    => (int)$data['start'],
                'limit'    => (int)$data['limit'] ?: 20,
                'filter'   => array_merge(
                    (array)$data['filter'],
                    ['only_featured' => true]
                ),
            ]
        );
    }

    /**
     * @param QueryBuilder $query
     * @param array $params
     * @return void
     */
    public static function scopeSearchByKeyword($query, $params)
    {
        $db = Registry::db();
        $p_table = $db->table_name('products');
        $pt_table = $db->table_name('product_tags');
        $pd_table = $db->table_name('product_descriptions');

        $query->where(
            function ($subQuery) use ($params, $db, $pt_table, $pd_table, $p_table) {
                /** @var QueryBuilder $subQuery */
                $filter = $params['filter'];
                $searchBy = (array)$filter['keyword_search_parameters']['search_by'] ?: ['name', 'model', 'sku'];
                $attrProduct = (new static())->getFillable();
                $attrDesc = (new ProductDescription())->getFillable();
                $tableSearchBy = [];
                foreach ($searchBy as $colName) {
                    if (in_array($colName, $attrProduct)) {
                        $tableSearchBy[$p_table][] = $colName;
                    }
                    if (in_array($colName, $attrDesc)) {
                        $tableSearchBy[$pd_table][] = $colName;
                    }
                }

                $match = in_array(
                    $filter['keyword_search_parameters']['match'],
                    ['any', 'all', 'exact']
                )
                    ? $filter['keyword_search_parameters']['match'] : 'any';

                $keyWord = $db->escape(mb_strtolower($filter['keyword']));
                //search by exact product_id
                if (is_numeric($filter['keyword'])) {
                    $subQuery->orWhere('products.product_id', '=', (int)$filter['keyword']);
                }

                //search by tag
                $words = array_map('mb_strtolower', array_filter(explode(' ', $keyWord)));
                $words = array_map('htmlentities', $words);
                if (sizeof($words) > 1) {
                    $subQuery->orWhereRaw("LOWER(" . $pt_table . ".tag) = '" . $keyWord . "'");
                }
                foreach ($words as $word) {
                    $subQuery->orWhereRaw("LOWER(" . $pt_table . ".tag) = '" . $word . "'");
                }

                if ($match == 'any') {
                    foreach ($tableSearchBy as $tableName => $cols) {
                        foreach ($cols as $column) {
                            foreach ($words as $word) {
                                $subQuery->orWhereRaw($tableName . "." . $column . " LIKE '%" . $word . "%'");
                            }
                        }
                    }
                } //if all words presents in the column
                elseif ($match == 'all') {
                    foreach ($tableSearchBy as $tableName => $cols) {
                        $subQuery->orWhere(
                            function ($subSubQuery) use ($words, $cols, $tableName) {
                                foreach ($cols as $column) {
                                    $subSubQuery->orWhere(
                                        function ($colQuery) use ($words, $column, $tableName) {
                                            foreach ($words as $word) {
                                                $colQuery->whereRaw($tableName . "." . $column . " LIKE '%" . $word . "%'");
                                            }
                                        }
                                    );
                                }
                            }
                        );
                    }
                } elseif ($match == 'exact') {
                    foreach ($tableSearchBy as $tableName => $cols) {
                        $subQuery->orWhere(
                            function ($subSubQuery) use ($keyWord, $cols, $tableName) {
                                foreach ($cols as $column) {
                                    $subSubQuery->orWhereRaw($tableName . "." . $column . " LIKE '%" . $keyWord . "%'");
                                }
                            }
                        );
                    }
                }

                //allow to extend search criteria
                Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $subQuery, $params);
            }
        );
    }
}