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
 */

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
use Error;
use Exception;
use H;
use Illuminate\Database\Eloquent\Collection;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

/**
 * Class Manufacturer
 *
 * @property int $manufacturer_id
 * @property string $name
 * @property string $uuid
 * @property int $sort_order
 *
 * @property Collection $manufacturers_to_stores
 *
 * @method static Manufacturer find(int $manufacturer_id) Manufacturer
 * @method static WithProductCount(bool $only_enabled = true) - adds "product_count" column into selected fields
 * @package abc\models
 */
class Manufacturer extends BaseModel
{
    use GeneratesUuid;

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
     * @throws Exception
     */
    public static function addManufacturer($data)
    {
        $db = Registry::db();
        $manufacturer = new Manufacturer($data);
        $manufacturer->save();

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
     * @return Manufacturer|false
     */
    public static function editManufacturer($manufacturerId, $data)
    {
        $db = Registry::db();
        $db->beginTransaction();
        try {
            $manufacturer = self::find($manufacturerId);
            $manufacturer->update($data);
            $manufacturerToStore = [];
            if (isset($data['manufacturer_store'])) {
                $db->table('manufacturers_to_stores')
                    ->where('manufacturer_id', '=', (int)$manufacturerId)
                    ->delete();

                foreach ($data['manufacturer_store'] as $store_id) {
                    $manufacturerToStore[] = [
                        'manufacturer_id' => $manufacturerId,
                        'store_id'        => (int)$store_id,
                    ];
                }
            }

            if ($manufacturerToStore) {
                $db->table('manufacturers_to_stores')->insert($manufacturerToStore);
            }

            if ($data['keyword'] || $data['name']) {
                UrlAlias::setManufacturerKeyword($data['keyword'] ?: $data['name'], $manufacturerId);
            }

            Registry::cache()->flush('manufacturer');
            $db->commit();
            return $manufacturer;
        } catch (Exception|Error $e) {
            Registry::log()->error($e->getMessage());
            $db->rollback();
            return false;
        }
    }

    /**
     * @return array|false|mixed
     * @throws ReflectionException
     * @throws AException
     * @throws InvalidArgumentException
     */
    public function getAllData()
    {
        if (!$this->getKey()) {
            return false;
        }
        $cacheKey = 'manufacturer.alldata.' . $this->getKey();
        $data = Registry::cache()->get($cacheKey);
        if ($data !== null) {
            return $data;
        }

        // eagerLoading!
        $toLoad = $nested = [];
        $rels = $this->getRelationships('HasMany', 'HasOne', 'belongsToMany');
        foreach ($rels as $relName => $rel) {
            if (in_array($relName, ['product'])) {
                continue;
            }
            if ($rel['getAllData']) {
                $nested[] = $relName;
            } else {
                $toLoad[] = $relName;
            }
        }

        $this->load($toLoad);
        $data = $this->toArray();
        foreach ($nested as $prop) {
            foreach ($this->{$prop} as $option) {
                /** @var ProductOption $option */
                $data[$prop][] = $option->getAllData();
            }
        }

        $data['images'] = $this->images();
        $data['keyword'] = UrlAlias::getManufacturerKeyword($this->getKey(), static::$current_language_id);
        Registry::cache()->put($cacheKey, $data);

        return $data;
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
        $images = [];
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
        $images['image_main'] = $resource->getResourceAllObjects('manufacturers', $this->getKey(), $sizes, 1, false);
        if ($images['image_main']) {
            $images['image_main']['sizes'] = $sizes;
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
        $images['images'] = $resource->getResourceAllObjects('manufacturers', $this->getKey(), $sizes, 0, false);
        if (!empty($images)) {
            /** @var Setting $protocolSetting */
            $protocolSetting = Setting::select('value')->where('key', '=', 'protocol_url')->first();
            $protocol = 'http';
            if ($protocolSetting) {
                $protocol = $protocolSetting->value;
            }

            if (isset($images['image_main']['direct_url']) && !str_starts_with($images['image_main']['direct_url'], 'http')) {
                $images['image_main']['direct_url'] = $protocol.':'.$images['image_main']['direct_url'];
            }
            if (isset($images['image_main']['main_url']) && !str_starts_with($images['image_main']['main_url'], 'http')) {
                $images['image_main']['main_url'] = $protocol.':'.$images['image_main']['main_url'];
            }
            if (isset($images['image_main']['thumb_url']) && !str_starts_with($images['image_main']['thumb_url'], 'http')) {
                $images['image_main']['thumb_url'] = $protocol.':'.$images['image_main']['thumb_url'];
            }
            if (isset($images['image_main']['thumb2_url']) && !str_starts_with($images['image_main']['thumb2_url'], 'http')) {
                $images['image_main']['thumb2_url'] = $protocol.':'.$images['image_main']['thumb2_url'];
            }

            if ($images['images']) {
                foreach ($images['images'] as &$img) {
                    if (isset($img['direct_url']) && !str_starts_with($img['direct_url'], 'http')) {
                        $img['direct_url'] = $protocol.':'.$img['direct_url'];
                    }
                    if (isset($img['main_url']) && !str_starts_with($img['main_url'], 'http')) {
                        $img['main_url'] = $protocol.':'.$img['main_url'];
                    }
                    if (isset($img['thumb_url']) && !str_starts_with($img['thumb_url'], 'http')) {
                        $img['thumb_url'] = $protocol.':'.$img['thumb_url'];
                    }
                    if (isset($img['thumb2_url']) && !str_starts_with($img['thumb2_url'], 'http')) {
                        $img['thumb2_url'] = $protocol.':'.$img['thumb2_url'];
                    }
                }
            }

        }
        return $images;
    }

    public static function getManufacturer($manufacturerId)
    {
        $manufacturerId = (int)$manufacturerId;
        if (!$manufacturerId) {
            return false;
        }
        $storeId = (int)Registry::config()->get('config_store_id');
        $cacheKey = 'manufacturer.' . $manufacturerId . '.store_' . $storeId;
        $output = Registry::cache()->get($cacheKey);

        if ($output !== null) {
            return $output;
        }
        /** @var QueryBuilder|Manufacturer $query */
        $query = self::select()->leftJoin(
            'manufacturers_to_stores',
            'manufacturers_to_stores.manufacturer_id',
            '=',
            'manufacturers.manufacturer_id'
        );
        $query->where('manufacturers_to_stores.store_id', '=', $storeId);
        $query->where('manufacturers.manufacturer_id', '=', $manufacturerId);

        /** @see Manufacturer::scopeWithProductCount() */
        $query->WithProductCount();
        $manufacturer = $query->get()->first();

        if (!$manufacturer) {
            return false;
        }

        $output = $manufacturer->toArray();

        if (ABC::env('IS_ADMIN')) {
            $seoUrl = Registry::db()->table('url_aliases')
                ->where('query', '=', 'manufacturer_id='.(int)$manufacturerId)
                ->get()
                ->first();
            if ($seoUrl) {
                $output['keyword'] = $seoUrl->keyword;
            }
        }

        Registry::cache()->put($cacheKey, $output);
        return $output;
    }

    public function deleteManufacturer($manufacturer_id)
    {
        if (!(int)$manufacturer_id) {
            return false;
        }
        $manufacturer = self::find((int)$manufacturer_id);
        $manufacturer?->delete();

        Registry::db()->table('manufacturers_to_stores')
            ->where('manufacturer_id', '=', (int)$manufacturer_id)
            ->delete();


        //delete resources
        try {
            $rm = new AResourceManager();
            $resources = $rm->getResourcesList(
                [
                    'object_name' => 'manufacturers',
                    'object_id'   => (int)$manufacturer_id,
                ]
            );
            foreach ($resources as $r) {
                $rm->unmapResource('manufacturers', $manufacturer_id, $r['resource_id']);
                //if resource became orphan - delete it
                if (!$rm->isMapped($r['resource_id'])) {
                    $rm->deleteResource($r['resource_id']);
                }
            }
        } catch (Exception $e) {
        }

        Registry::cache()->flush('manufacturer');
        return true;
    }

    /**
     * @return bool|null
     * @throws InvalidArgumentException
     */
    public function delete()
    {
        try {
            $lm = new ALayoutManager();
            $lm->deleteAllPagesLayouts('pages/product/manufacturer', 'manufacturer_id', $this->getKey());
        } catch (Exception $e) {
        }

        UrlAlias::where('query', '=', 'manufacturer_id=' . $this->getKey())->delete();
        return parent::delete();
    }

    public static function getManufacturers($params = [])
    {
        $params['sort'] = $params['sort'] ?: 'sort_order';
        $params['order'] = $params['order'] ?? 'ASC';
        $params['start'] = max($params['start'], 0);
        $params['limit'] = abs((int)$params['limit']) ?: 20;

        $filter = (array)$params['filter'];
        $filter['include'] = $filter['include'] ?? [];
        $filter['exclude'] = $filter['exclude'] ?? [];
        $db = Registry::db();
        $storeId = $params['store_id'] ?? (int)Registry::config()->get('config_store_id');
        $manTable = $db->table_name('manufacturers');

        $query = self::selectRaw(
            Registry::db()->raw_sql_row_count() . ' ' . $manTable . '.*'
        )->leftJoin(
            'manufacturers_to_stores',
            'manufacturers_to_stores.manufacturer_id',
            '=',
            'manufacturers.manufacturer_id'
        )->where('manufacturers_to_stores.store_id', '=', $storeId);
        //include ids set
        if ($filter['include']) {
            $filter['include'] = array_map('intval', (array)$filter['include']);
            $query->whereIn('manufacturers.manufacturer_id', $filter['include']);
        }
        //exclude already selected in chosen element
        if ($filter['exclude']) {
            $filter['exclude'] = array_map('intval', (array)$filter['exclude']);
            $query->whereNotIn('manufacturers.manufacturer_id', $filter['exclude']);
        }

        if ($filter['name']) {
            if ($params['search_operator'] == 'equal') {
                $query->where('manufacturers.name', '=', $filter['name']);
            } else {
                $query->where('manufacturers.name', 'like', "%" . mb_strtolower($filter['name']) . "%");
            }
        }

        $sort_data = [
            'name'       => $manTable.'.name',
            'sort_order' => $manTable.'.sort_order',
        ];

        if (isset($params['sort']) && in_array($params['sort'], array_keys($sort_data))) {
            $orderBy = $params['sort'];
        } else {
            $orderBy = $sort_data['sort_order'];
        }

        if (isset($params['order']) && (strtoupper($params['order']) == 'DESC')) {
            $sorting = "desc";
        } else {
            $sorting = "asc";
        }
        $query->orderByRaw($orderBy." ".$sorting);
        //pagination
        $query->offset((int)$params['start'])->limit((int)$params['limit']);
        //allow to extend this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, $params);

        return $query->useCache('manufacturer')->get();
    }

    /**
     * @param QueryBuilder $builder
     * @param bool $only_enabled
     */
    public static function scopeWithProductCount($builder, $only_enabled = true)
    {
        $tableName = Registry::db()->table_name("products");
        $sql = "( SELECT COUNT(" . $tableName . ".product_id)
                 FROM " . $tableName . "
                 WHERE " . $tableName . ".manufacturer_id = " . Registry::db()->table_name("manufacturers") . ".manufacturer_id ";
        if ($only_enabled) {
            $sql .= " AND " . $tableName . ".status = 1 ";
        }
        $sql .= ") as product_count";
        $builder->selectRaw($sql);
    }
}