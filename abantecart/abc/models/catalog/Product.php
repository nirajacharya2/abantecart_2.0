<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use abc\core\engine\AResource;
use abc\models\order\CouponsProduct;
use abc\models\order\OrderProduct;
use abc\models\system\Audit;
use abc\models\system\Store;
use Exception;
use H;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Product
 *
 * @property int $product_id
 * @property string $model
 * @property string $sku
 * @property string $location
 * @property int $quantity
 * @property string $stock_checkout
 * @property int $stock_status_id
 * @property int $manufacturer_id
 * @property int $shipping
 * @property int $ship_individually
 * @property int $free_shipping
 * @property float $shipping_price
 * @property float $price
 * @property int $tax_class_id
 * @property \Carbon\Carbon $date_available
 * @property float $weight
 * @property int $weight_class_id
 * @property float $length
 * @property float $width
 * @property float $height
 * @property int $length_class_id
 * @property int $status
 * @property int $viewed
 * @property int $sort_order
 * @property int $subtract
 * @property int $minimum
 * @property int $maximum
 * @property float $cost
 * @property int $call_to_order
 * @property string $settings
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * @property ProductOption $options
 * @property CouponsProduct $coupons_products
 * @property OrderProduct $order_products
 * @property ProductDescription $product_descriptions
 * @property ProductDiscount $product_discounts
 * @property ProductOptionDescription $product_option_descriptions
 * @property ProductOptionValueDescription $product_option_value_descriptions
 * @property ProductOptionValue $product_option_values
 * @property ProductOption $product_options
 * @property ProductSpecial $product_specials
 * @property ProductTag $product_tags
 * @property ProductsFeatured $products_featured
 * @property ProductsRelated $products_related
 * @property Review $reviews
 *
 * @package abc\models
 */
class Product extends BaseModel
{
    use SoftDeletes;
    const DELETED_AT = 'date_deleted';
    /**
     * Access policy properties
     * Note: names must be without dashes and whitespaces
     * policy rule will be named as {userType-userGroup}.product-product-read
     * For example: system-www-data.product-product-read
     */
    protected $policyGroup  = 'product';
    protected $policyObject = 'product';

    /**
     * @var string
     */
    protected $primaryKey = 'product_id';

    /**
     * @var bool
     */
    public $timestamps = false;

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
    ];

    protected $rules = [
        'product_id'       => 'integer',
        'model'            => 'string|max:64',
        //NOTE
        //if need sku as mandatory use "present" instead "required"
        'sku'              => 'string|max:64|nullable',
        'location'         => 'string|max:128',
        'quantity'         => 'integer',
        'stock_checkout'   => 'max:1|nullable',
        'stock_status_id'  => 'integer',
        'manufacturer_id'  => 'integer',
        'shipping'         => 'integer|max:1|min:0',
        'ship_individually'=> 'integer|max:1|min:0',
        'free_shipping'    => 'integer|max:1|min:0',
        'shipping_price'   => 'numeric',
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
    public static $auditExcludes = ['sku' ];

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
     * @return mixed
     */
    public function coupons()
    {
        return $this->hasMany(CouponsProduct::class, 'product_id');
    }

    /**
     * @return mixed
     */
    public function descriptions()
    {
        return $this->hasMany(ProductDescription::class, 'product_id');
    }

    /**
     * @return mixed
     */
    public function discounts()
    {
        return $this->hasMany(ProductDiscount::class, 'product_id');
    }

    /**
     * @return mixed
     */
    public function options()
    {
        return $this->hasMany(ProductOption::class, 'product_id');
    }

    /**
     * @return mixed
     */
    public function option_descriptions()
    {
        return $this->hasMany(ProductOptionDescription::class, 'product_id');
    }

    /**
     * @return mixed
     */
    public function option_values()
    {
        return $this->hasMany(ProductOptionValue::class, 'product_id');
    }

    /**
     * @return mixed
     */
    public function option_value_descriptions()
    {
        return $this->hasMany(ProductOptionValueDescription::class, 'product_id');
    }

    /**
     * @return mixed
     */
    public function specials()
    {
        return $this->hasMany(ProductSpecial::class, 'product_id');
    }

    /**
     * @return mixed
     */
    public function tags()
    {
        return $this->hasMany(ProductTag::class, 'product_id');
    }

    /**
     * @return mixed
     */
    public function featured()
    {
        return $this->hasOne(ProductsFeatured::class, 'product_id');
    }

    /**
     * @return mixed
     */
    public function related()
    {
        return $this->hasMany(ProductsRelated::class, 'product_id');
    }

    /**
     * @return mixed
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'product_id');
    }

    /**
     * @return mixed
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'products_to_categories', 'product_id', 'category_id');
    }

    /**
     * @return mixed
     */
    public function manufacturer()
    {
        return $this->hasOne(Manufacturer::class, 'manufacturer_id');
    }

    /**
     * @return mixed
     */
    public function downloads()
    {
        return $this->belongsToMany(Download::class, 'products_to_downloads', 'product_id', 'download_id');
    }

    /**
     * @return mixed
     */
    public function stores()
    {
        return $this->belongsToMany(Store::class, 'products_to_stores', 'product_id', 'store_id');
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
                if($manufacturer) {
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
     * @return bool|int
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
            //if some of option value have subtract NO - think product is available
            if ($total_quantity == 0 && $notrack_qnt) {
                $total_quantity = true;
            }
        } else {
            //get product quantity without options
            $total_quantity = (int)$this::find($this->product_id)->first()->quantity;
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
        $this->options()->delete();
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

            foreach ((array)$option['option_descriptions'] as $option_description) {
                $option_description['product_id'] = $productId;
                $option_description['product_option_id'] = $productOptionId;
                $optionDescData = $this->removeSubArrays($option_description);

                $optionDescObj = new ProductOptionDescription();
                $optionDescObj->fill($optionDescData)->save();
                unset($optionDescObj);
            }

            foreach ((array)$option['option_values'] as $option_value) {
                $option_value['product_id'] = $productId;
                $option_value['product_option_id'] = $productOptionId;
                $option_value['attribute_value_id'] = 0;

                $optionValueData = $this->removeSubArrays($option_value);
                $optionValueObj = new ProductOptionValue();
                $optionValueObj->fill($optionValueData)->save();
                $productOptionValueId = $optionValueObj->getKey();

                unset($optionValueObj);

                $optionValueDescData = [];
                foreach ((array)$option_value['option_value_descriptions'] as $option_value_description) {
                    $option_value_description['product_id'] = $productId;
                    $option_value_description['product_option_value_id'] = $productOptionValueId;

                    $optionValueDescData = $this->removeSubArrays($option_value_description);
                    $optionValueDescObj = new ProductOptionValueDescription();
                    $optionValueDescObj->fill($optionValueDescData)->save();
                    unset($optionValueDescObj);
                }
                if($option_value['images']){

                    $title = current($optionValueDescData['name']);
                    $language_id = current($optionValueDescData['language_id']);

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
        if($urlAliases){
            foreach($urlAliases as $urlAlias)
            $this->keywords[] = [
                'keyword' => H::SEOEncode($urlAlias->keyword,'product_id',$this->product_id),
                'language_id'=> $urlAlias->language_id
            ];
        }
        $this->cache->remove('product');
        return $this->keywords;
    }

    public function replaceKeywords($data)
    {
        $query = 'product_id='.$this->product_id;
        $urlAlias = new UrlAlias();
        $urlAlias->where('query', '=', $query)->delete();
        unset($urlAlias);

        foreach ((array)$data as $keyword) {
            $urlAlias = new UrlAlias();
            $urlAlias->query = $query;
            $urlAlias->language_id = (int)$keyword['language_id'];
            $urlAlias->keyword = H::SEOEncode($keyword['keyword'],'product_id',$this->product_id);
            $urlAlias->save();
        }
        $this->cache->remove('product');
    }

    public function auditables() {
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
        $product = new Product($product_data);
        $product->save();
        $productId = $product->product_id;
        if ($productId) {
            $description = new ProductDescription($product_data['product_description']);
            $product->descriptions()->save($description);

            self::updateProductLinks($productId, $product_data);
            return $productId;
        }
    }

    /**
     * @param int   $product_id
     * @param array $product_data
     * @param int   $language_id
     */
    public static function updateProduct(int $product_id, array $product_data, int $language_id)
    {
        $product = Product::find($product_id);
        $product->update($product_data);
        $product->descriptions()->where('language_id', $language_id)->update($product_data['product_description']);

        self::updateProductLinks($product_id, $product_data);
    }

    /**
     * @param int   $product_id
     * @param array $product_data
     */
    public static function updateProductLinks(int $product_id, array $product_data)
    {
        $product = Product::find($product_id);

        if (isset($product_data['product_category'])) {
            $product->categories()->sync($product_data['product_category']);
        }

        if (isset($product_data['product_store'])) {
            $product->stores()->sync($product_data['product_store']);
        }

        if (isset($product_data['product_download'])) {
            $product->downloads()->sync($product_data['product_download']);
        }

        if (isset($product_data['product_related'])) {
            $product->related()->sync($product_data['product_related']);
        }
    }

}
