<?php

namespace abc\models\catalog;

use abc\core\engine\HtmlElementFactory;
use abc\core\engine\Registry;
use abc\core\lib\ADB;
use abc\models\BaseModel;
use abc\core\engine\AResource;
use abc\models\locale\LengthClass;
use abc\models\locale\WeightClass;
use abc\models\order\CouponsProduct;
use abc\models\order\OrderProduct;
use abc\models\QueryBuilder;
use abc\models\system\Audit;
use abc\models\system\Setting;
use abc\models\system\Store;
use abc\models\system\TaxClass;
use Dyrynda\Database\Support\GeneratesUuid;
use Exception;
use H;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

/**
 * Class Product
 *
 * @property int                           $product_id
 * @property string                        $model
 * @property string                        $sku
 * @property string                        $uuid
 * @property string                        $location
 * @property int                           $quantity
 * @property string                        $stock_checkout
 * @property int                           $stock_status_id
 * @property int                           $manufacturer_id
 * @property int                           $shipping
 * @property int                           $ship_individually
 * @property int                           $free_shipping
 * @property float                         $shipping_price
 * @property float                         $price
 * @property int                           $tax_class_id
 * @property \Carbon\Carbon                $date_available
 * @property float                         $weight
 * @property int                           $weight_class_id
 * @property float                         $length
 * @property float                         $width
 * @property float                         $height
 * @property int                           $length_class_id
 * @property int                           $status
 * @property int                           $viewed
 * @property int                           $sort_order
 * @property int                           $subtract
 * @property int                           $minimum
 * @property int                           $maximum
 * @property float                         $cost
 * @property int                           $call_to_order
 * @property string                        $settings
 * @property \Carbon\Carbon                $date_added
 * @property \Carbon\Carbon                $date_modified
 * @property ProductDescription            $description
 * @property ProductDescription            $descriptions
 * @property Collection                    $categories
 * @property ProductOption                 $options
 * @property CouponsProduct                $coupons_products
 * @property ProductDescription            $product_descriptions
 * @property ProductDiscount               $product_discounts
 * @property ProductOptionDescription      $product_option_descriptions
 * @property ProductOptionValueDescription $product_option_value_descriptions
 * @property ProductOptionValue            $product_option_values
 * @property ProductOption                 $product_options
 * @property ProductSpecial                $product_specials
 * @property ProductTag                    $product_tags
 * @property ProductsFeatured              $products_featured
 * @property ProductsRelated               $products_related
 * @property Review                        $reviews
 * @property int                           $product_type_id
 *
 * @method static Product find(int $product_id) Product
 * @method static Product select(mixed $select) Builder
 *
 * @package abc\models
 */
class Product extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes, GeneratesUuid;

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

    public $timestamps = false;
    protected $touches = ['categories'];
    /**
     * @var array
     */
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
        'product_type_id'   => 'int',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'date_available',
        'date_added',
        'date_modified',
    ];

    /**
     * @var array
     */
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
        'product_type_id',
        'uuid',
        'date_deleted'
    ];

    protected $rules = [
        'product_id'        => 'integer',
        'model'             => 'string|max:64',
        //NOTE
        //if need sku as mandatory use "present" instead "required"
        'sku'               => 'string|max:64|nullable',
        'location'          => 'string|max:128',
        'quantity'          => 'integer',
        'stock_checkout'    => 'max:1|nullable',
        'stock_status_id'   => 'integer',
        'manufacturer_id'   => 'integer',
        'shipping'          => 'integer|max:1|min:0',
        'ship_individually' => 'integer|max:1|min:0',
        'free_shipping'     => 'integer|max:1|min:0',
        'shipping_price'    => 'numeric',
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
        'product_store'    => [
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

    /**
     * @var array
     */
    protected $images = [];

    /**
     * seo-keywords
     * @var array
     */
    protected $keywords = [];

    /**
     * @var
     */
    protected $thumbURL;

    /**
     * Auditing properties
     *
     */
    public static $auditExcludes = ['sku'];

    /**
     * @param array $options
     *
     * @return bool|void
     * @throws \Exception
     */
    public function save(array $options = [])
    {
        if ($this->hasPermission('update')) {
            parent::save();
            $this->registry->get('cache')->remove('product');
        } else {
            throw new Exception('No permission for object to save the model.');
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function coupons()
    {
        return $this->hasMany(CouponsProduct::class, 'product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function descriptions()
    {
        return $this->hasMany(ProductDescription::class, 'product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function description()
    {
        return $this->hasOne(ProductDescription::class, 'product_id')
                    ->where('language_id', '=', static::$current_language_id);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function discounts()
    {
        return $this->hasMany(ProductDiscount::class, 'product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function options()
    {
        return $this->hasMany(ProductOption::class, 'product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function option_descriptions()
    {
        return $this->hasMany(ProductOptionDescription::class, 'product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function option_values()
    {
        return $this->hasMany(ProductOptionValue::class, 'product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function option_value_descriptions()
    {
        return $this->hasMany(ProductOptionValueDescription::class, 'product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function specials()
    {
        return $this->hasMany(ProductSpecial::class, 'product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tags()
    {
        return $this->hasMany(ProductTag::class, 'product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tagLanguaged()
    {
        return $this->hasMany(ProductTag::class, 'product_id')
            ->where('language_id', '=', $this->registry->get('language')->getContentLanguageID());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function featured()
    {
        return $this->hasOne(ProductsFeatured::class, 'product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function related()
    {
        return $this->hasMany(ProductsRelated::class, 'product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'products_to_categories', 'product_id', 'category_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function manufacturer()
    {
        return $this->hasOne(Manufacturer::class, 'manufacturer_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function downloads()
    {
        return $this->belongsToMany(Download::class, 'products_to_downloads', 'product_id', 'download_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function stores()
    {
        return $this->belongsToMany(Store::class, 'products_to_stores', 'product_id', 'store_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
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
        return $this->db->table('object_types as ot')
            ->join('object_type_descriptions as otd', 'ot.object_type_id', '=', 'otd.object_type_id')
            ->where(
                [
                    'ot.object_type' => 'Product',
                    'ot.status' => 1,
                    'otd.language_id' => static::$current_language_id
                ]
            )
            ->select('otd.object_type_id as id', 'otd.name')
            ->get()
            ->toArray();
    }

    /**
     * @return array
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
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
        $result[] = (object)['id' => 0, 'name' => $this->registry->get('language')->get('text_none')];
        foreach ($tax_classes as $tax_class) {
            $result[] = (object)['id' => $tax_class->tax_class_id, 'name' => $tax_class->description->title];
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getStockCheckouts()
    {
        $language = $this->registry->get('language');
        $result = [
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
        return $result;
    }

    /**
     * @param int $language_id
     *
     * @return array
     */
    public function getStockStatuses($language_id = 0)
    {
        $language_id = $language_id ?? $this->registry->get('language')->getContentLanguageID();
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
            $result[] = (object)['id' => $length_class->length_class_id, 'name' => $length_class->description->title];
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
     * @return mixed
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public function getAllData()
    {
        $cache_key = 'product.alldata.'.$this->getKey();
        $data = $this->cache->pull($cache_key);
        if ($data === false) {
            $this->load('descriptions', 'discounts', 'tags', 'stores', 'categories');
            $data = $this->toArray();
            foreach ($this->options as $option) {
                $data['options'][] = $option->getAllData();
            }
            $data['images'] = $this->images();
            $data['keywords'] = $this->keywords();

            //TODO: need to rewrite into relations
            if ($this->manufacturer_id) {
                $manufacturer = Manufacturer::find($this->manufacturer_id);
                if ($manufacturer) {
                    $data['manufacturer'] = $manufacturer->toArray();
                }
            }
            $this->cache->push($cache_key, $data);
        }
        return $data;
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
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
            $this->config->get('config_image_thumb_width'),
            $this->config->get('config_image_thumb_height')
        );
        return $this->thumbURL = $thumbnail['thumb_url'];
    }

    /**
     * @return array
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public function images()
    {
        if ($this->images) {
            return $this->images;
        }
        $resource = new AResource('image');
        // main product image
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
        $this->images['image_main'] = $resource->getResourceAllObjects('products', $this->getKey(), $sizes, 1, false);
        if ($this->images['image_main']) {
            $this->images['image_main']['sizes'] = $sizes;
        }

        // additional images
        $sizes = [
            'main'   => [
                'width'  => $this->config->get('config_image_popup_width'),
                'height' => $this->config->get('config_image_popup_height'),
            ],
            'thumb'  => [
                'width'  => $this->config->get('config_image_additional_width'),
                'height' => $this->config->get('config_image_additional_height'),
            ],
            'thumb2' => [
                'width'  => $this->config->get('config_image_thumb_width'),
                'height' => $this->config->get('config_image_thumb_height'),
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
        if (is_array($this->product_option_values)) {
            foreach ($this->product_option_values as $opv) {
                $track_status += $opv->subtract;
            }
        }

        //if no options - check whole product subtract
        if (!$track_status && !$this->product_option_values) {
            //check main product
            $track_status = (int)$this->first()->subtract;
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
        $option_values = $this->query()->from('product_options')
            ->where('product_options.product_id', $this->product_id)
            ->where('status', 1)
            ->join(
                'product_option_values',
                'product_option_values.product_option_id',
                '=',
                'product_options.product_option_id'
            )->select('product_option_values.quantity', 'product_option_values.subtract')
            ->get();
        if ($option_values) {
            $notrack_qnt = 0;
            foreach ($option_values as $row) {
                //if tracking of stock disabled - set quantity as big
                if (!$row->subtract) {
                    $notrack_qnt += 10000000;
                    continue;
                }
                $total_quantity += $row->quantity < 0 ? 0 : $row->quantity;
            }
        } else {
            //get product quantity without options
            $total_quantity = (int)$this::find($this->product_id)->quantity;
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
        if (!$result) {
            $this->errors = array_merge($this->errors, $resource_mdl->errors());
        }
        $this->cache->remove('product');
        return $result;
    }

    /**
     * @param array $data - nested array of options with descriptions, values and value descriptions
     *
     * @return bool
     * @throws \Exception
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
        $this->cache->remove('product');
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

    public function keywords()
    {
        if ($this->keywords) {
            return $this->keywords;
        }

        $urlAliases = UrlAlias::where('query', '=', 'product_id='.$this->product_id)->get();
        if ($urlAliases) {
            foreach ($urlAliases as $urlAlias) {
                $this->keywords[] = [
                    'keyword'     => H::SEOEncode($urlAlias->keyword, 'product_id', $this->product_id),
                    'language_id' => $urlAlias->language_id,
                ];
            }
        }
        $this->cache->remove('product');
        return $this->keywords;
    }

    public function auditables()
    {
        return $this->morphMany(Audit::class, 'auditable');
    }

    /*
     * User methods ????? Todo add RBAC to check for user
     */

    /**
     * @param array $product_data
     *
     * @return int
     * @throws Exception
     */
    public static function createProduct(array $product_data)
    {
        if (!isset($product_data['product_store']) || empty($product_data['product_store'])) {
            $product_data['product_store'] = [0 => 0];
        }
        $product = new Product($product_data);
        $product->save();
        $productId = $product->product_id;
        if ($productId) {
            $description = new ProductDescription($product_data['product_description']);
            $product->descriptions()->save($description);

            UrlAlias::setProductKeyword($product_data['keyword'] ?: $product_data['product_description']['name'], $productId);
            self::updateProductLinks($product, $product_data);
            return $productId;
        }
    }

    /**
     * @param int   $product_id
     * @param array $product_data
     * @param int   $language_id
     *
     * @return bool
     */
    public static function updateProduct(int $product_id, array $product_data, int $language_id)
    {
        /**
         * @var Product $product
         */
        $product = Product::with('categories')->find($product_id);
        if (!$product) {
            return false;
        }
        $product_data['product_category_prev'] = $product->categories->pluck('category_id')->toArray();

        $product->update($product_data);
        if ($product_data['product_description']) {
            if (!isset($product_data['product_description']['language_id'])) {
                $product_data['product_description']['language_id'] = $language_id;
            }
            $product->descriptions()->update($product_data['product_description']);
        }

        if ($product_data['keyword'] || $product_data['product_description']['name']) {
            UrlAlias::setProductKeyword($product_data['keyword'] ?: $product_data['product_description']['name'], $product_id);
        }

        $attributes = array_filter($product_data, function ($k) {
            return (strpos($k, 'attribute_') === 0);
        }, ARRAY_FILTER_USE_KEY);

        if (is_array($attributes) && !empty($attributes) && $product_data['product_type_id']) {
            self::updateProductAttributes($product_id, $product_data['product_type_id'], $attributes);
        }
        self::updateProductLinks($product, $product_data);

        return true;
    }

    /**
     * @param int   $productId
     * @param int   $productTypeId
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
     */
    public static function updateProductLinks(&$product, array $product_data)
    {
        if(is_numeric($product)) {
            $model = Product::find($product);
        }else{
            $model = $product;
        }

        if(!$model instanceof Product){
            return false;
        }

        if( !is_array($product_data['product_category_prev']) ){
            $product_data['product_category_prev'] = $model->categories()
                                                            ->where('product_id', '=', $model->getKey())
                                                            ->get()->pluck('category_id')->toArray();
        }

        if (isset($product_data['product_category']) &&  $product_data['product_category'] != $product_data['product_category_prev']) {

            $ids = (array)$product_data['product_category'];
            $product_data['product_category'] = [];
            foreach($ids as $id){
                $id = (int)$id;
                if($id){
                    $product_data['product_category'][] = $id;
                }
            }
            if($product_data['product_category']) {
                $model->categories()->sync($product_data['product_category']);
            }else{
                $model->categories()->detach($product_data['product_category_prev']);
            }

            //touch all categories to call update listener that calculates products count in it
            $affectedCategories = [];

            foreach((array)$product_data['product_category']  as $id) {
                $affectedCategories[] = $id;
            }
            foreach((array)$product_data['product_category_prev']  as $id) {
                $affectedCategories[] = $id;
            }

            foreach($affectedCategories as $categoryId){
                $category = Category::find($categoryId);
                $category->touch();
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
                $registry = Registry::getInstance();
                $languageId = $registry->get('language')->getContentLanguageID();
                $productTags = [];
                foreach ($tags as $tag) {
                    $productTag = ProductTag::updateOrCreate([
                        'tag'         => trim($tag),
                        'product_id'  => $model->product_id,
                        'language_id' => $languageId,
                    ]);
                    $productTags[] = $productTag->id;
                }
                ProductTag::where('product_id', '=', $model->product_id)
                    ->whereNotIn('id', $productTags)
                    ->forceDelete();
            }
        }
        return true;
    }

    /**
     * @param int $productId
     *
     * @return array|bool
     */
    public static function getProductTypeSettings(int $productId)
    {
        if (!$productId) {
            return false;
        }

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

    public function getCatalogOnlyProducts(int $limit = null)
    {
        $arSelect = [$this->db->raw('SQL_CALC_FOUND_ROWS *'), 'pd.name as name'];

        //special prices
        if (is_object($this->registry->get('customer')) && $this->registry->get('customer')->isLogged()) {
            $customer_group_id = (int)$this->registry->get('customer')->getCustomerGroupId();
        } else {
            $customer_group_id = (int)$this->config->get('config_customer_group_id');
        }

        $sql
            = " ( SELECT p2sp.price
                    FROM ".$this->db->table_name("product_specials")." p2sp
                    WHERE p2sp.product_id = ".$this->db->table_name("products").".product_id
                            AND p2sp.customer_group_id = '".$customer_group_id."'
                            AND ((p2sp.date_start = '0000-00-00' OR p2sp.date_start < NOW())
                            AND (p2sp.date_end = '0000-00-00' OR p2sp.date_end > NOW()))
                    ORDER BY p2sp.priority ASC, p2sp.price ASC 
                    LIMIT 1
                 ) ";
        $arSelect[] = $this->db->raw("COALESCE( ".$sql.", ".$this->db->table_name("products").".price) as final_price");

        $languageId = (int)$this->config->get('storefront_language_id');

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
            'total_num_rows' => $this->db->sql_get_row_count(),
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
        /**
         * @var QueryBuilder $query
         */
        $query = ProductOption::with('description')
                               ->with('values', 'values.description')
                               ->where(
                                   [
                                       'product_id' => $product_id,
                                       'group_id' => 0
                                   ]
                               )->active()
                                ->orderBy('sort_order');
        //allow to extends this method from extensions
        Registry::extensions()->hk_extendQuery(new static,__FUNCTION__, $query);

        $productOptions = $query->get()->toArray();

        $elements = HtmlElementFactory::getAvailableElements();
        foreach($productOptions as &$option){
            $option['html_type'] = $elements[$option['element_type']]['type'];
        }
        return $productOptions;
    }

    /**
     * @param array $data
     *
     * @return array|bool|false|mixed
     */
    public static function getBestSellerProductIds(array $data)
    {
        /**
         * @var ADB $db
         */
        $db = Registry::db();
        $cache = Registry::getInstance()->get('cache');
        $config = Registry::getInstance()->get('config');
        $limit = (int)$data['limit'];

        $aliasOP = $db->table_name('order_products');
        $language_id = (int)$config->get('storefront_language_id');
        $store_id = (int)$config->get('config_store_id');

        $cache_key = 'product.bestseller.ids.'
            .'.store_'.$store_id
            .'_lang_'.$language_id
            .'_'.md5($limit);
        $productIds = $cache->pull($cache_key);
        if ($productIds === false) {
            $productIds = [];
            /** @var QueryBuilder $query */
            $query = OrderProduct::select(['order_products.product_id']);
            $query->leftJoin('orders',
                    'order_products.order_id',
                    '=',
                    'orders.order_id')
                ->leftJoin('products',
                    'order_products.product_id',
                    '=',
                    'products.product_id')
                ->where('orders.order_status_id', '>', 0)
                ->where('order_products.product_id', '>', 0)
                ->groupBy('order_products.product_id')
                ->orderBy($db->raw('SUM('.$aliasOP.'.quantity) '), 'DESC');

            //allow to extends this method from extensions
            Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, $data);
            /** @var Collection $result_rows */
            $result_rows = $query->get();
            if ($result_rows) {
                $product_data = $result_rows->toArray();
                $productIds = array_column($product_data, 'product_id');
                $cache->push($cache_key, $productIds);
            }
        }
        return $productIds;
    }

    /**
     * @param array $data
     *
     * @return array|bool|false|mixed
     */
    public static function getBestSellerProducts(array $data)
    {

        $limit = (int)$data['limit'];
        $order = $data['order'];
        $start = (int)$data['start'];
        $sort = $data['sort'];
        $total = $data['total'];
        /**
         * @var ADB $db
         */
        $db = Registry::db();
        $cache = Registry::getInstance()->get('cache');
        $config = Registry::getInstance()->get('config');

        $language_id = (int)$config->get('storefront_language_id');
        $store_id = (int)$config->get('config_store_id');
        $cache_key = 'product.bestseller.'
            .'.store_'.$store_id
            .'_lang_'.$language_id
            .'_'.md5($limit.$order.$start.$sort.$total);

        $product_data = $cache->pull($cache_key);
        if ($product_data === false) {
            $product_data = [];

            $aliasP = $db->table_name('products');
            $aliasPD = $db->table_name('product_descriptions');
            $aliasSS = $db->table_name('stock_statuses');

            $select = [
                $db->raw($aliasSS.'.name as stock'),
                'products.*',
            ];

            $bestSellerIds = self::getBestSellerProductIds($data);

            /**
             * @var QueryBuilder $query
             */
            $query = self::selectRaw($db->raw_sql_row_count()." ".$aliasPD.".*")
                ->addSelect($select)
                ->leftJoin('product_descriptions', function ($subQuery) use ($language_id) {
                    $subQuery->on('products.product_id',
                        '=',
                        'product_descriptions.product_id')
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
                    $subQuery->on('products.stock_status_id',
                        '=',
                        'stock_statuses.stock_status_id')
                             ->where('stock_statuses.language_id', '=', $language_id);
                })
                ->whereIn('products.product_id', $bestSellerIds)
                ->whereRaw($aliasP.'.date_available<=NOW()')
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
                $query = $query->orderByRaw('LCASE('.$aliasPD.'.name)', $order);
            } else {
                $query = $query->orderBy($sort_data[$sort], $order);
            }

            if ($start < 0) {
                $start = 0;
            }
            if ((int)$limit) {
                $query = $query->offset($start)->limit($limit);
            }

            //allow to extends this method from extensions
            Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, $data);
            $result_rows = $query->get();
            if ($result_rows) {
                $product_data = $result_rows->toArray();
                $cache->push($cache_key, $product_data);
            }
        }
        return $product_data;
    }

    /**
     * @param array $productIds
     *
     * @return array
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public static function getProductsAllInfo(array $productIds)
    {
        $result = [];
        foreach ($productIds as $productId) {
            /** @var Category $category */
            $category = Category::find($productId);
            if ($category) {
                $result[] = $category->getAllData();
            }
        }
        return $result;
    }

}
