<?php

namespace abc\models\catalog;

use abc\core\engine\AResource;
use abc\models\BaseModel;
use abc\models\system\Setting;
use Dyrynda\Database\Support\GeneratesUuid;
use H;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Manufacturer
 *
 * @property int $manufacturer_id
 * @property string $name
 * @property int $sort_order
 *
 * @property \Illuminate\Database\Eloquent\Collection $manufacturers_to_stores
 *
 * @package abc\models
 */
class Manufacturer extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes, GeneratesUuid;

    protected $cascadeDeletes = ['stores'];

    protected $primaryKey = 'manufacturer_id';
    public $timestamps = false;

    protected $casts = [
        'sort_order' => 'int',
    ];

    protected $fillable = [
        'name',
        'sort_order',
        'uuid',
        'date_deleted',
    ];

    public function stores()
    {
        return $this->hasMany(ManufacturersToStore::class, 'manufacturer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'manufacturer_id');
    }

    /**
     * @param $data
     *
     * @return bool|mixed
     * @throws \abc\core\lib\AException
     */
    public function addManufacturer($data)
    {
        $manufacturer = new Manufacturer($data);
        $manufacturer->save();

        if (!$manufacturer) {
            return false;
        }

        $manufacturerId = $manufacturer->getKey();

        if (isset($data['manufacturer_store'])) {
            $manufacturerToStore = [];
            foreach ($data['manufacturer_store'] as $store_id) {
                $manufacturerToStore[] = [
                    'manufacturer_id' => $manufacturerId,
                    'store_id'    => (int)$store_id,
                ];
            }
            $this->db->table('manufacturers_to_stores')->insert($manufacturerToStore);
        }

        if ($data['keyword']) {
            $seo_key = H::SEOEncode($data['keyword'], 'manufacturer_id', $manufacturerId);
        } else {
            //Default behavior to save SEO URL keyword from manufacturer name in default language
            $seo_key = H::SEOEncode($data['name'],
                'manufacturer_id',
                $manufacturerId);
        }
        if ($seo_key) {
            $this->registry->get('language')->replaceDescriptions('url_aliases',
                ['query' => "manufacturer_id=".(int)$manufacturerId],
                [(int)$this->registry->get('language')->getContentLanguageID() => ['keyword' => $seo_key]]);
        } else {
            UrlAlias::where('query', '=', 'manufacturer_id='.(int)$manufacturerId)
                ->where('language_id', '=', (int)$this->registry->get('language')->getContentLanguageID())
                ->forceDelete();
        }

        $this->cache->remove('manufacturer');

        return $manufacturerId;
    }

    /**
     * @param $manufacturerId
     * @param $data
     *
     * @throws \abc\core\lib\AException
     */
    public function editManufacturer($manufacturerId, $data)
    {
        $contentLanguageId = $this->registry->get('language')->getContentLanguageID();

        self::find($manufacturerId)->update($data);

        if (isset($data['manufacturer_store'])) {
            $this->db->table('manufacturers_to_stores')
                ->where('manufacturer_id', '=', (int)$manufacturerId)
                ->delete();

            $manufacturerToStore = [];
            foreach ($data['manufacturer_store'] as $storeId) {
                $manufacturerToStore[] = [
                    'manufacturer_id' => (int)$manufacturerId,
                    'store_id'    => (int)$storeId,
                ];
            }
            $this->db->table('manufacturers_to_stores')->insert($manufacturerToStore);
        }

        if (isset($data['keyword'])) {
            $data['keyword'] = H::SEOEncode($data['keyword']);
            if ($data['keyword']) {
                $this->registry->get('language')->replaceDescriptions('url_aliases',
                    ['query' => "manufacturer_id=".(int)$manufacturerId],
                    [$contentLanguageId => ['keyword' => $data['keyword']]]
                );
            } else {
                UrlAlias::where('query', '=', 'manufacturer_id='.(int)$manufacturerId)
                    ->where('language_id', '=', $contentLanguageId)
                    ->forceDelete();
            }
        }

        $this->cache->remove('manufacturer');
    }

    /**
     * @return array|false|mixed
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public function getAllData()
    {
        $cache_key = 'manufacturer.alldata.'.$this->getKey();
        $data = $this->cache->pull($cache_key);
        if ($data === false) {
            $this->load('stores');
            $data = $this->toArray();
            $data['images'] = $this->getImages();
            $this->cache->push($cache_key, $data);
        }
        return $data;
    }

    /**
     * @return array
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public function getImages()
    {
        $images = [];
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
        $images['image_main'] = $resource->getResourceAllObjects('manufacturers', $this->getKey(), $sizes, 1, false);
        if ($images['image_main']) {
            $images['image_main']['sizes'] = $sizes;
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
        $images['images'] = $resource->getResourceAllObjects('manufacturers', $this->getKey(), $sizes, 0, false);
        if (!empty($images)) {
            $protocolSetting = Setting::select('value')->where('key', '=', 'protocol_url')->first();
            $protocol = 'http';
            if ($protocolSetting) {
                $protocol = $protocolSetting->value;
            }

            if (isset($images['image_main']['direct_url']) && strpos($images['image_main']['direct_url'], 'http') !== 0) {
                $images['image_main']['direct_url'] = $protocol.':'.$images['image_main']['direct_url'];
            }
            if (isset($images['image_main']['main_url']) && strpos($images['image_main']['main_url'], 'http') !== 0) {
                $images['image_main']['main_url'] = $protocol.':'.$images['image_main']['main_url'];
            }
            if (isset($images['image_main']['thumb_url']) && strpos($images['image_main']['thumb_url'], 'http') !== 0) {
                $images['image_main']['thumb_url'] = $protocol.':'.$images['image_main']['thumb_url'];
            }
            if (isset($images['image_main']['thumb2_url']) && strpos($images['image_main']['thumb2_url'], 'http') !== 0) {
                $images['image_main']['thumb2_url'] = $protocol.':'.$images['image_main']['thumb2_url'];
            }

            if ($images['images']) {
                foreach ($images['images'] as &$img) {
                    if (isset($img['direct_url']) && strpos($img['direct_url'], 'http') !== 0) {
                        $img['direct_url'] = $protocol.':'.$img['direct_url'];
                    }
                    if (isset($img['main_url']) && strpos($img['main_url'], 'http') !== 0) {
                        $img['main_url'] = $protocol.':'.$img['main_url'];
                    }
                    if (isset($img['thumb_url']) && strpos($img['thumb_url'], 'http') !== 0) {
                        $img['thumb_url'] = $protocol.':'.$img['thumb_url'];
                    }
                    if (isset($img['thumb2_url']) && strpos($img['thumb2_url'], 'http') !== 0) {
                        $img['thumb2_url'] = $protocol.':'.$img['thumb2_url'];
                    }
                }
            }

        }
        return $images;
    }
}
