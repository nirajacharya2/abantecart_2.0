<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class AcLanguage
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
 * @package abc\models
 */
class Language extends AModelBase
{
    protected $primaryKey = 'language_id';
    public $timestamps = false;

    protected $casts = [
        'sort_order' => 'int',
        'status'     => 'int',
    ];

    protected $fillable = [
        'name',
        'code',
        'locale',
        'image',
        'directory',
        'filename',
        'sort_order',
        'status',
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

    public function language_definitions()
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
        return $this->hasMany(\abc\models\Order::class, 'language_id');
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
}
