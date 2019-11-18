<?php

namespace abc\models\catalog;

use abc\core\ABC;
use abc\core\engine\AResource;
use abc\core\engine\Registry;
use abc\core\lib\AException;
use abc\core\lib\ALayoutManager;
use abc\core\lib\AResourceManager;
use abc\models\BaseModel;
use abc\models\QueryBuilder;
use abc\models\system\Setting;
use Dyrynda\Database\Support\GeneratesUuid;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Manufacturer
 *
 * @property int                                      $manufacturer_id
 * @property string                                   $name
 * @property string                                   $uuid
 * @property int                                      $sort_order
 *
 * @property \Illuminate\Database\Eloquent\Collection $manufacturers_to_stores
 *
 * @method static Manufacturer find(int $customer_id) Manufacturer
 * @package abc\models
 */
class Manufacturer extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes, GeneratesUuid;

    protected $cascadeDeletes = ['stores'];

    protected $primaryKey = 'manufacturer_id';
    protected $casts = [
        'sort_order' => 'int',
    ];

    protected $fillable = [
        'manufacturer_id',
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
     * @throws \Exception
     */
    public static function addManufacturer($data)
    {
        $db = Registry::db();
        $manufacturer = new Manufacturer($data);
        $manufacturer->save();

        if (!$manufacturer) {
            return false;
        }

        $manufacturerId = $manufacturer->getKey();

        $manufacturerToStore = [];
        if (isset($data['manufacturer_store'])) {
            foreach ($data['manufacturer_store'] as $store_id) {
                $manufacturerToStore[] = [
                    'manufacturer_id' => $manufacturerId,
                    'store_id'        => (int)$store_id,
                ];
            }
        } else {
            $manufacturerToStore[] = [
                'manufacturer_id' => $manufacturerId,
                'store_id'        => 0,
            ];
        }
        $db->table('manufacturers_to_stores')->insert($manufacturerToStore);

        if ($data['keyword'] || $data['name']) {
            UrlAlias::setManufacturerKeyword($data['keyword'] ?: $data['name'], $manufacturerId);
        }elseif( $data['keywords']){
            UrlAlias::replaceKeywords($data['keywords'], $manufacturer->getKeyName(), $manufacturer->getKey());
        }

        Registry::cache()->flush('manufacturer');

        return $manufacturerId;
    }

    /**
     * @param $manufacturerId
     * @param $data
     *
     */
    public function editManufacturer($manufacturerId, $data)
    {
        self::find($manufacturerId)->update($data);
        $manufacturerToStore = [];
        if (isset($data['manufacturer_store'])) {
            $this->db->table('manufacturers_to_stores')
                ->where('manufacturer_id', '=', (int)$manufacturerId)
                ->delete();

            foreach ($data['manufacturer_store'] as $store_id) {
                $manufacturerToStore[] = [
                    'manufacturer_id' => $manufacturerId,
                    'store_id'        => (int)$store_id,
                ];
            }
        }

        $this->db->table('manufacturers_to_stores')->insert($manufacturerToStore);

        if ($data['keyword'] || $data['name']) {
            UrlAlias::setManufacturerKeyword($data['keyword'] ?: $data['name'], $manufacturerId);
        }

        $this->cache->flush('manufacturer');
    }

    /**
     * @return array|false|mixed
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getAllData()
    {
        $cache_key = 'manufacturer.alldata.'.$this->getKey();
        $data = $this->cache->get($cache_key);
        if ($data === null) {
            $this->load('stores');
            $data = $this->toArray();
            $data['images'] = $this->getImages();
            $data['keyword'] = UrlAlias::getManufacturerKeyword($this->getKey(), $this->registry->get('language')->getContentLanguageID());
            $this->cache->put($cache_key, $data);
        }
        return $data;
    }

    /**
     * @return array
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
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
        $output = $this->cache->get($cacheKey);

        if ($output !== null) {
            return $output;
        }

        /** @var QueryBuilder $query */
        $query = self::leftJoin(
            'manufacturers_to_stores',
            'manufacturers_to_stores.manufacturer_id',
            '=',
            'manufacturers.manufacturer_id'
        );
        $query->where('manufacturers_to_stores.store_id', '=', $storeId);
        $query->where('manufacturers.manufacturer_id', '=', $manufacturerId);
        $manufacturer = $query->get()->first();

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

        $this->cache->put($cacheKey, $output);
        return $output;
    }

    public function deleteManufacturer($manufacturer_id)
    {
        if (!(int)$manufacturer_id) {
            return false;
        }
        $manufacturer = self::withTrashed()->find((int)$manufacturer_id);

        if ($manufacturer) {
            $manufacturer->delete();
        }

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
        $this->cache->flush('manufacturer');
        return true;
    }

}
