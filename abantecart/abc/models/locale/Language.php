<?php

namespace abc\models\locale;

use abc\models\BaseModel;
use abc\models\catalog\CategoryDescription;
use abc\models\catalog\DownloadDescription;
use abc\models\catalog\GlobalAttributesDescription;
use abc\models\catalog\GlobalAttributesValueDescription;
use abc\models\catalog\ProductDescription;
use abc\models\catalog\ProductOptionDescription;
use abc\models\catalog\ProductOptionValueDescription;
use abc\models\catalog\ProductTag;
use abc\models\catalog\ResourceDescription;
use abc\models\catalog\StockStatus;
use abc\models\catalog\UrlAlias;
use abc\models\content\ContentDescription;
use abc\models\layout\BannerDescription;
use abc\models\layout\BlockDescription;
use abc\models\layout\PageDescription;
use abc\models\order\CouponDescription;
use abc\models\order\Order;
use abc\models\order\OrderDataType;
use abc\models\order\OrderStatusDescription;
use abc\models\system\FieldDescription;
use abc\models\system\FieldsGroupDescription;
use abc\models\system\FormDescription;
use abc\models\system\StoreDescription;
use abc\models\system\TaxClassDescription;
use abc\models\system\TaxRateDescription;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Language
 *
 * @property int $language_id
 * @property string $name
 * @property string $code
 * @property string $locale
 * @property string $image
 * @property string $directory
 * @property string $filename
 * @property int $sort_order
 * @property int $status
 *
 * @property \Illuminate\Database\Eloquent\Collection $banner_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $block_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $category_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $content_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $country_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $coupon_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $download_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $field_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $fields_group_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $form_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $global_attributes_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $global_attributes_value_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $language_definitions
 * @property \Illuminate\Database\Eloquent\Collection $length_class_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $order_data_types
 * @property \Illuminate\Database\Eloquent\Collection $order_status_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $orders
 * @property \Illuminate\Database\Eloquent\Collection $page_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $product_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $product_option_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $product_option_value_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $product_tags
 * @property \Illuminate\Database\Eloquent\Collection $resource_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $stock_statuses
 * @property \Illuminate\Database\Eloquent\Collection $store_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $tax_class_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $tax_rate_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $url_aliases
 * @property \Illuminate\Database\Eloquent\Collection $weight_class_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $zone_descriptions
 *
 * @method static Language find(int $language_id) Language
 * @method static Language select(mixed $select) Builder
 * @package abc\models
 */
class Language extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    //Note: list of related model for cascade deleting to except Orders
    protected $cascadeDeletes = [
        'banner_descriptions',
        'block_descriptions',
        'category_descriptions',
        'content_descriptions',
        'country_descriptions',
        'coupon_descriptions',
        'download_descriptions',
        'field_descriptions',
        'fields_group_descriptions',
        'form_descriptions',
        'global_attributes_descriptions',
        'global_attributes_value_descriptions',
        'definitions',
        'length_class_descriptions',
        'order_status_descriptions',
        'page_descriptions',
        'product_descriptions',
        'product_option_descriptions',
        'product_option_value_descriptions',
        'product_tags',
        'resource_descriptions',
        'stock_statuses',
        'store_descriptions',
        'tax_class_descriptions',
        'tax_rate_descriptions',
        'url_aliases',
        'weight_class_descriptions',
        'zone_descriptions',
        'product_type_descriptions',
    ];
    protected $primaryKey = 'language_id';
    public $timestamps = false;

    protected $casts = [
        'sort_order' => 'int',
        'status' => 'int',
    ];

    protected $fillable = [
        'language_id',
        'name',
        'code',
        'locale',
        'image',
        'directory',
        'filename',
        'sort_order',
        'status',

    ];
    protected $rules = [
        'language_id' => [
            'checks' => [
                'integer',
                'required',
                'sometimes',
                'min:1'
            ],

            'messages' => [
                'integer' => [
                    'language_key' => 'error_language_id',
                    'language_block' => 'localisation/language',
                    'default_text' => 'language id must be integer!',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_language_id',
                    'language_block' => 'localisation/language',
                    'default_text' => 'language id required!',
                    'section' => 'admin'
                ],
                'min' => [
                    'language_key' => 'error_language_id',
                    'language_block' => 'localisation/language',
                    'default_text' => 'language id must be more 1!',
                    'section' => 'admin'
                ],
            ],
        ],
        'name' => [
            'checks' => [
                'string',
                'required',
                'sometimes',
                'min:2',
                'max:32'
            ],
            'messages' => [
                'min' => [
                    'language_key' => 'error_name',
                    'language_block' => 'localisation/language',
                    'default_text' => 'Name must be more 2 characters',
                    'section' => 'admin'
                ],
                'max' => [
                    'language_key' => 'error_name',
                    'language_block' => 'localisation/language',
                    'default_text' => 'Name must be no more than 32 characters',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_name',
                    'language_block' => 'localisation/language',
                    'default_text' => 'name required!',
                    'section' => 'admin'
                ],
                'string' => [
                    'language_key' => 'error_name',
                    'language_block' => 'localisation/language',
                    'default_text' => 'name must be string!',
                    'section' => 'admin'
                ],
            ]
        ],
        'code' => [
            'checks' => [
                'string',
                'required',
                'sometimes',
                'max:2'
            ],
            'messages' => [
                'max' => [
                    'language_key' => 'error_code',
                    'language_block' => 'localisation/language',
                    'default_text' => 'Language Code must be at least 2 characters!',
                    'section' => 'admin'
                ],
                'string' => [
                    'language_key' => 'error_code',
                    'language_block' => 'localisation/language',
                    'default_text' => 'Language Code must be string!',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_code',
                    'language_block' => 'localisation/language',
                    'default_text' => 'Language Code required!',
                    'section' => 'admin'
                ]
            ]
        ],
        'locale' => [
            'checks' => [
                'string',
                'required',
                'sometimes',
                'max:255'
            ],
            'messages' => [
                'max' => [
                    'language_key' => 'error_locale',
                    'language_block' => 'localisation/language',
                    'default_text' => 'Locale must be at least 255 characters!',
                    'section' => 'admin'
                ],
                'string' => [
                    'language_key' => 'error_locale',
                    'language_block' => 'localisation/language',
                    'default_text' => 'Locale Code must be string!',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_locale',
                    'language_block' => 'localisation/language',
                    'default_text' => 'Locale required!',
                    'section' => 'admin'
                ]
            ]
        ],
        'image' => [
            'checks' => [
                'string',
                'min:2',
                'max:64'
            ],
            'messages' => [
                'max' => [
                    'language_key' => 'error_image',
                    'language_block' => 'localisation/language',
                    'default_text' => 'Image must be at least 64 characters!',
                    'section' => 'admin'
                ],
                'string' => [
                    'language_key' => 'error_image',
                    'language_block' => 'localisation/language',
                    'default_text' => 'Image Code must be string!',
                    'section' => 'admin'
                ],
                'min' => [
                    'language_key' => 'error_image',
                    'language_block' => 'localisation/language',
                    'default_text' => 'Image must be more 2 characters!',
                    'section' => 'admin'
                ]
            ]
        ],
        'directory' => [
            'checks' => [
                'string',
                'sometimes',
                'required',
            ],
            'messages' => [
                'string' => [
                    'language_key' => 'error_directory',
                    'language_block' => 'localisation/language',
                    'default_text' => 'directory must be string!',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_directory',
                    'language_block' => 'localisation/language',
                    'default_text' => 'directory required!',
                    'section' => 'admin'
                ]
            ]
        ],
        'filename' => [
            'checks' => [
                'string',
                'min:2',
                'max:64'
            ],
            'messages' => [
                'max' => [
                    'language_key' => 'error_filename',
                    'language_block' => 'localisation/language',
                    'default_text' => 'filename must be at least 64 characters!',
                    'section' => 'admin'
                ],
                'string' => [
                    'language_key' => 'error_filename',
                    'language_block' => 'localisation/language',
                    'default_text' => 'filename Code must be string!',
                    'section' => 'admin'
                ],
                'min' => [
                    'language_key' => 'error_filename',
                    'language_block' => 'localisation/language',
                    'default_text' => 'filename must be more 2 characters!',
                    'section' => 'admin'
                ]
            ]
        ],
        'sort_order' => [
            'checks' => [
                'integer',
                'sometimes'
            ],
            'messages' => [
                '*' => ['default_text' => 'sort_order is not integer']
            ],
        ],
        'status' => [
            'checks' => [
                'integer'
            ],
            'messages' => [
                '*' => ['default_text' => 'status is not integer']
            ],
        ]
    ];

    public function banner_descriptions()
    {
        return $this->hasMany(BannerDescription::class, 'language_id');
    }

    public function block_descriptions()
    {
        return $this->hasMany(BlockDescription::class, 'language_id');
    }

    public function category_descriptions()
    {
        return $this->hasMany(CategoryDescription::class, 'language_id');
    }

    public function content_descriptions()
    {
        return $this->hasMany(ContentDescription::class, 'language_id');
    }

    public function country_descriptions()
    {
        return $this->hasMany(CountryDescription::class, 'language_id');
    }

    public function coupon_descriptions()
    {
        return $this->hasMany(CouponDescription::class, 'language_id');
    }

    public function download_descriptions()
    {
        return $this->hasMany(DownloadDescription::class, 'language_id');
    }

    public function field_descriptions()
    {
        return $this->hasMany(FieldDescription::class, 'language_id');
    }

    public function fields_group_descriptions()
    {
        return $this->hasMany(FieldsGroupDescription::class, 'language_id');
    }

    public function form_descriptions()
    {
        return $this->hasMany(FormDescription::class, 'language_id');
    }

    public function global_attributes_descriptions()
    {
        return $this->hasMany(GlobalAttributesDescription::class, 'language_id');
    }

    public function global_attributes_value_descriptions()
    {
        return $this->hasMany(GlobalAttributesValueDescription::class, 'language_id');
    }

    public function definitions()
    {
        return $this->hasMany(LanguageDefinition::class, 'language_id');
    }

    public function length_class_descriptions()
    {
        return $this->hasMany(LengthClassDescription::class, 'language_id');
    }

    public function order_data_types()
    {
        return $this->hasMany(OrderDataType::class, 'language_id');
    }

    public function order_status_descriptions()
    {
        return $this->hasMany(OrderStatusDescription::class, 'language_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'language_id');
    }

    public function page_descriptions()
    {
        return $this->hasMany(PageDescription::class, 'language_id');
    }

    public function product_descriptions()
    {
        return $this->hasMany(ProductDescription::class, 'language_id');
    }

    public function product_option_descriptions()
    {
        return $this->hasMany(ProductOptionDescription::class, 'language_id');
    }

    public function product_option_value_descriptions()
    {
        return $this->hasMany(ProductOptionValueDescription::class, 'language_id');
    }

    public function product_tags()
    {
        return $this->hasMany(ProductTag::class, 'language_id');
    }

    public function resource_descriptions()
    {
        return $this->hasMany(ResourceDescription::class, 'language_id');
    }

    public function stock_statuses()
    {
        return $this->hasMany(StockStatus::class, 'language_id');
    }

    public function store_descriptions()
    {
        return $this->hasMany(StoreDescription::class, 'language_id');
    }

    public function tax_class_descriptions()
    {
        return $this->hasMany(TaxClassDescription::class, 'language_id');
    }

    public function tax_rate_descriptions()
    {
        return $this->hasMany(TaxRateDescription::class, 'language_id');
    }

    public function url_aliases()
    {
        return $this->hasMany(UrlAlias::class, 'language_id');
    }

    public function weight_class_descriptions()
    {
        return $this->hasMany(WeightClassDescription::class, 'language_id');
    }

    public function zone_descriptions()
    {
        return $this->hasMany(ZoneDescription::class, 'language_id');
    }

    public function product_type_descriptions()
    {
        return $this->hasMany(ProductDescription::class, 'language_id');
    }

    public static function getCodeById($language_id)
    {
        /**
         * @var Language $language
         */
        $language = static::find($language_id);
        if (!$language) {
            return false;
        }
        return $language->code;
    }
}
