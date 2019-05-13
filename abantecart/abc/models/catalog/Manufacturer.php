<?php

namespace abc\models\catalog;

use abc\core\ABC;
use abc\core\engine\AResource;
use abc\core\lib\AException;
use abc\core\lib\ALayoutManager;
use abc\core\lib\AResourceManager;
use abc\models\BaseModel;
use abc\models\system\Setting;
use Dyrynda\Database\Support\GeneratesUuid;
use H;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Manufacturer
 *
 * @property int                                      $manufacturer_id
 * @property string                                   $name
 * @property int                                      $sort_order
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
                    'store_id'        => (int)$store_id,
                ];
            }
            $this->db->table('manufacturers_to_stores')->insert($manufacturerToStore);
        }

        UrlAlias::setManufacturerKeyword($data['keyword'] ?? '' , $manufacturerId);

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
                    'store_id'        => (int)$storeId,
                ];
            }
            $this->db->table('manufacturers_to_stores')->insert($manufacturerToStore);
        }

        UrlAlias::setManufacturerKeyword($data['keyword'] ?? '' , $manufacturerId);

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

    public function getManufacturer($manufacturerId)
    {
        $manufacturerId = (int)$manufacturerId;
        if (!$manufacturerId) {
            return false;
        }
        $storeId = (int)$this->config->get('config_store_id');
        $cacheKey = 'manufacturer.'.$manufacturerId.'.store_'.$storeId;
        $output = $this->cache->pull($cacheKey);

        if ($output !== false) {
            return $output;
        }

        $manufacturer = self::leftJoin('manufacturers_to_stores', 'manufacturers_to_stores.manufacturer_id', '=', 'manufacturers.manufacturer_id')
            ->where('manufacturers_to_stores.store_id', '=', $storeId)
            ->where('manufacturers.manufacturer_id', '=', $manufacturerId);
        $manufacturer = $manufacturer->get()->first();

        if (!$manufacturer) {
            return false;
        }

        $output = $manufacturer->toArray();

        if (ABC::env('IS_ADMIN')) {
            $seoUrl = $this->db->table('url_aliases')
                ->where('query', '=', 'manufacturer_id='.(int)$manufacturerId)
                ->get()
                ->first();
            if ($seoUrl) {
                $output['keyword'] = $seoUrl->keyword;
            }
        }

        $this->cache->push($cacheKey, $output);
        return $output;
    }

    public function deleteManufacturer($manufacturer_id)
    {
        if (!(int)$manufacturer_id) {
            return false;
        }
        self::withTrashed()->find((int)$manufacturer_id)->delete();

        $this->db->table('manufacturers_to_stores')
            ->where('manufacturer_id', '=', (int)$manufacturer_id)
            ->delete();

        $this->db->table('url_aliases')
            ->where('query', '=', 'manufacturer_id='.(int)$manufacturer_id)
            ->delete();

        try {
            $lm = new ALayoutManager();
            $lm->deletePageLayout('pages/product/manufacturer', 'manufacturer_id', (int)$manufacturer_id);
        } catch (AException $e) {

        } catch (\Exception $e) {

        }

        //delete resources
        try {
            $rm = new AResourceManager();
            $resources = $rm->getResourcesList([
                'object_name' => 'manufacturers',
                'object_id'   => (int)$manufacturer_id,
            ]);
            foreach ($resources as $r) {
                $rm->unmapResource('manufacturers', $manufacturer_id, $r['resource_id']);
                //if resource became orphan - delete it
                if (!$rm->isMapped($r['resource_id'])) {
                    $rm->deleteResource($r['resource_id']);
                }
            }
        } catch (\ReflectionException $e) {

        } catch (\Exception $e) {

        }
        $this->cache->remove('manufacturer');
    }

}
