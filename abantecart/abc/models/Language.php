<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

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
 * @property \Illuminate\Database\Eloquent\Collection $ac_banner_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_block_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_category_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_content_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_country_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_coupon_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_download_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_field_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_fields_group_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_form_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_global_attributes_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_global_attributes_value_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_language_definitions
 * @property \Illuminate\Database\Eloquent\Collection $ac_length_class_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_order_data_types
 * @property \Illuminate\Database\Eloquent\Collection $ac_order_status_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_orders
 * @property \Illuminate\Database\Eloquent\Collection $ac_page_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_product_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_product_option_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_product_option_value_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_product_tags
 * @property \Illuminate\Database\Eloquent\Collection $ac_resource_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_stock_statuses
 * @property \Illuminate\Database\Eloquent\Collection $ac_store_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_tax_class_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_tax_rate_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_url_aliases
 * @property \Illuminate\Database\Eloquent\Collection $ac_weight_class_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_zone_descriptions
 *
 * @package App\Models
 */
class AcLanguage extends Eloquent
{
	protected $primaryKey = 'language_id';
	public $timestamps = false;

	protected $casts = [
		'sort_order' => 'int',
		'status' => 'int'
	];

	protected $fillable = [
		'name',
		'code',
		'locale',
		'image',
		'directory',
		'filename',
		'sort_order',
		'status'
	];

	public function ac_banner_descriptions()
	{
		return $this->hasMany(\App\Models\AcBannerDescription::class, 'language_id');
	}

	public function ac_block_descriptions()
	{
		return $this->hasMany(\App\Models\AcBlockDescription::class, 'language_id');
	}

	public function ac_category_descriptions()
	{
		return $this->hasMany(\App\Models\AcCategoryDescription::class, 'language_id');
	}

	public function ac_content_descriptions()
	{
		return $this->hasMany(\App\Models\AcContentDescription::class, 'language_id');
	}

	public function ac_country_descriptions()
	{
		return $this->hasMany(\App\Models\AcCountryDescription::class, 'language_id');
	}

	public function ac_coupon_descriptions()
	{
		return $this->hasMany(\App\Models\AcCouponDescription::class, 'language_id');
	}

	public function ac_download_descriptions()
	{
		return $this->hasMany(\App\Models\AcDownloadDescription::class, 'language_id');
	}

	public function ac_field_descriptions()
	{
		return $this->hasMany(\App\Models\AcFieldDescription::class, 'language_id');
	}

	public function ac_fields_group_descriptions()
	{
		return $this->hasMany(\App\Models\AcFieldsGroupDescription::class, 'language_id');
	}

	public function ac_form_descriptions()
	{
		return $this->hasMany(\App\Models\AcFormDescription::class, 'language_id');
	}

	public function ac_global_attributes_descriptions()
	{
		return $this->hasMany(\App\Models\AcGlobalAttributesDescription::class, 'language_id');
	}

	public function ac_global_attributes_value_descriptions()
	{
		return $this->hasMany(\App\Models\AcGlobalAttributesValueDescription::class, 'language_id');
	}

	public function ac_language_definitions()
	{
		return $this->hasMany(\App\Models\AcLanguageDefinition::class, 'language_id');
	}

	public function ac_length_class_descriptions()
	{
		return $this->hasMany(\App\Models\AcLengthClassDescription::class, 'language_id');
	}

	public function ac_order_data_types()
	{
		return $this->hasMany(\App\Models\AcOrderDataType::class, 'language_id');
	}

	public function ac_order_status_descriptions()
	{
		return $this->hasMany(\App\Models\AcOrderStatusDescription::class, 'language_id');
	}

	public function ac_orders()
	{
		return $this->hasMany(\App\Models\AcOrder::class, 'language_id');
	}

	public function ac_page_descriptions()
	{
		return $this->hasMany(\App\Models\AcPageDescription::class, 'language_id');
	}

	public function ac_product_descriptions()
	{
		return $this->hasMany(\App\Models\AcProductDescription::class, 'language_id');
	}

	public function ac_product_option_descriptions()
	{
		return $this->hasMany(\App\Models\AcProductOptionDescription::class, 'language_id');
	}

	public function ac_product_option_value_descriptions()
	{
		return $this->hasMany(\App\Models\AcProductOptionValueDescription::class, 'language_id');
	}

	public function ac_product_tags()
	{
		return $this->hasMany(\App\Models\AcProductTag::class, 'language_id');
	}

	public function ac_resource_descriptions()
	{
		return $this->hasMany(\App\Models\AcResourceDescription::class, 'language_id');
	}

	public function ac_stock_statuses()
	{
		return $this->hasMany(\App\Models\AcStockStatus::class, 'language_id');
	}

	public function ac_store_descriptions()
	{
		return $this->hasMany(\App\Models\AcStoreDescription::class, 'language_id');
	}

	public function ac_tax_class_descriptions()
	{
		return $this->hasMany(\App\Models\AcTaxClassDescription::class, 'language_id');
	}

	public function ac_tax_rate_descriptions()
	{
		return $this->hasMany(\App\Models\AcTaxRateDescription::class, 'language_id');
	}

	public function ac_url_aliases()
	{
		return $this->hasMany(\App\Models\AcUrlAlias::class, 'language_id');
	}

	public function ac_weight_class_descriptions()
	{
		return $this->hasMany(\App\Models\AcWeightClassDescription::class, 'language_id');
	}

	public function ac_zone_descriptions()
	{
		return $this->hasMany(\App\Models\AcZoneDescription::class, 'language_id');
	}
}
