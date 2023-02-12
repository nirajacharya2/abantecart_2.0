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

namespace abc\extensions\banner_manager\models;

use abc\core\ABC;
use abc\core\engine\Registry;
use abc\models\BaseModel;
use abc\models\catalog\ResourceMap;
use abc\models\QueryBuilder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\JoinClause;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class Banner
 *
 * @property int $banner_id
 * @property int $status
 * @property int $banner_type
 * @property string $banner_group_name
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property bool $blank
 * @property string $target_url
 * @property int $sort_order
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Collection $banner_descriptions
 * @property Collection $banner_stats
 *
 * @package abc\models
 */
class Banner extends BaseModel
{
    protected $cascadeDeletes = ['descriptions', 'stats'];
    protected $primaryKey = 'banner_id';

    protected $casts = [
        'status'            => 'bool',
        'banner_type'       => 'int',
        'banner_group_name' => 'string',
        'blank'             => 'bool',
        'target_url'        => 'string',
        'start_date'        => 'datetime',
        'end_date'          => 'datetime',
        'sort_order'        => 'int',
        'date_added'        => 'datetime',
        'date_modified'     => 'datetime'
    ];

    protected $fillable = [
        'status',
        'banner_type',
        'banner_group_name',
        'start_date',
        'end_date',
        'blank',
        'target_url',
        'sort_order',
        'date_added',
        'date_modified',
    ];

    protected $rules = [
        'banner_id'         => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => ['default_text' => 'Banner ID is not Integer!'],
            ],
        ],
        'status'            => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Status must be 1 or 0 !',
                ],
            ],
        ],
        'banner_type'       => [
            'checks'   => [
                'integer',
                'required',
                'sometimes'
            ],
            'messages' => [
                '*' => ['default_text' => 'Banner Type is not Integer!'],
            ],
        ],
        'banner_group_name' => [
            'checks'   => [
                'string',
                'max:255',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Banner group name must be a string and less than 255 characters!',
                ],
            ],
        ],
        'start_date'        => [
            'checks'   => [
                'date',
                'nullable',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be a date!',
                ],
            ],
        ],
        'end_date'          => [
            'checks'   => [
                'date',
                'nullable',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be a date!',
                ],
            ],
        ],
        'blank'             => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Blank Sign must be 1 or 0 !',
                ],
            ],
        ],
        'target_url'        => [
            'checks'   => [
                'string',
                'required_if:banner_type,1',
                'max:1500',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Target URL is required for graphic banners!',
                ],
            ],
        ],
        'sort_order'        => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],
    ];

    /**
     * @return HasOne
     */
    public function description()
    {
        return $this->hasOne(BannerDescription::class, 'banner_id', 'banner_id')
            ->where('language_id', '=', static::$current_language_id);
    }

    /**
     * @return HasMany
     */
    public function descriptions()
    {
        return $this->hasMany(BannerDescription::class, 'banner_id');
    }

    public function stats()
    {
        return $this->hasMany(BannerStat::class, 'banner_id');
    }

    /**
     * @param array $data
     * @return false|int
     * @throws InvalidArgumentException
     */
    public static function addBanner(array $data = [])
    {

        $data['start_date'] = isset($data['start_date'])
            ? date("Y-m-d 00:00:00", strtotime($data['start_date']))
            : null;
        $data['end_date'] = isset($data['end_date'])
            ? date("Y-m-d 23:59:59", strtotime($data['end_date']))
            : null;

        Registry::db()->beginTransaction();
        try {
            $banner = new Banner($data);
            $banner->save();
            $bannerId = $banner->banner_id;

            // for graphic banners remap resources
            if ((int)$data['banner_type'] == 1) {
                ResourceMap::where('object_name', '=', 'banners')
                    ->where('object_id', '=', -1)
                    ?->update(['object_id' => $bannerId]);
            }

            $language = Registry::language();

            $language->replaceDescriptions(
                'banner_descriptions',
                ['banner_id' => $bannerId],
                [
                    $language->getContentLanguageID() =>
                        [
                            'name'        => $data['name'],
                            'meta'        => $data['meta'],
                            'description' => $data['description'],
                        ],
                ]
            );
            Registry::db()->commit();
            Registry::cache()->flush('banner');
            return $bannerId;
        } catch (\Exception $e) {
            Registry::log()->error($e->getMessage());
            Registry::db()->rollback();
            return false;
        }
    }

    /**
     * @param int $banner_id
     * @param array $data
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function editBanner(int $banner_id, array $data)
    {
        $language = Registry::language();

        if (isset($data['start_date'])) {
            $data['start_date'] = $data['start_date']
                ? date("Y-m-d 00:00:00", strtotime($data['start_date']))
                : null;
        }

        if (isset($data['end_date'])) {
            $data['end_date'] = $data['end_date']
                ? date("Y-m-d 23:59:59", strtotime($data['end_date']))
                : null;
        }
        Registry::db()->beginTransaction();
        try {
            $banner = Banner::find($banner_id);

            if (!$banner) {
                throw new \Exception(__FUNCTION__ . ': Banner #' . $banner_id . ' not found');
            }

            $banner->update($data);

            $bd = new BannerDescription();
            $fillable = $bd->getFillable();

            $update = [];
            foreach ($fillable as $field_name) {
                if (isset($data[$field_name])) {
                    $update[$field_name] = $data[$field_name];
                }
            }

            if (count($update)) {
                $language->replaceDescriptions('banner_descriptions',
                    ['banner_id' => $banner_id],
                    [$language->getContentLanguageID() => $update]);
            }
            Registry::db()->commit();
        } catch (\Exception $e) {
            Registry::db()->rollback();
            Registry::log()->error($e->getMessage());
            return false;
        }

        Registry::cache()->flush('banner');
        return true;
    }

    /**
     * @param array $params
     * @return array|\Illuminate\Support\Collection
     */
    public static function getBanners(array $params)
    {
        $params['language_id'] = $params['language_id'] ?: static::$current_language_id;
        $params['sort'] = $params['sort'] ?? 'banners.sort_order';
        $params['order'] = $params['order'] ?? 'ASC';
        $params['start'] = max($params['start'], 0);
        $params['limit'] = $params['limit'] >= 1 ? $params['limit'] : 20;

        $filter = (array)$params['filter'];
        if (!isset($filter['store_id'])) {
            $filter['store_id'] = ABC::env('IS_ADMIN') === true
                ? (int)Registry::session()->data['current_store_id']
                : (int)Registry::config()->get('config_store_id');
        } else {
            $filter['store_id'] = (int)$filter['store_id'];
        }

        $filter['include'] = $filter['include'] ?? [];
        $filter['exclude'] = $filter['exclude'] ?? [];

        //override to use prepared version of filter inside hooks
        $params['filter'] = $filter;

        $db = Registry::db();

        $b_table = $db->table_name('banners');
        $bd_table = $db->table_name('banner_descriptions');

        $query = self::selectRaw(Registry::db()->raw_sql_row_count() . ' ' . $bd_table . '.*');
        $query->addSelect('banners.*');
        $query->leftJoin(
            'banner_descriptions',
            function ($join) use ($params) {
                /** @var JoinClause $join */
                $join->on('banner_descriptions.banner_id', '=', 'banners.banner_id')
                    ->where('banner_descriptions.language_id', '=', $params['language_id']);
            }
        );

        // show active banners for sf-side. For admin - returns all
        if (ABC::env('IS_ADMIN') !== true) {
            if ($filter['date']) {
                if ($filter['date'] instanceof Carbon) {
                    $now = $filter['date']->toIso8601String();
                } else {
                    $now = Carbon::parse($filter['date'])->toIso8601String();
                }
            } else {
                $now = Carbon::now()->toIso8601String();
            }

            $query->where('banners.start_date', '<=', $now)
                ->where('banners.end_date', '>=', $now)
                ->active('banners');
        }

        if ((array)$filter['include']) {
            $query->whereIn('banners.banner_id', (array)$filter['include']);
        }
        if ((array)$filter['exclude']) {
            $query->whereNotIn('banners.banner_id', (array)$filter['exclude']);
        }

        if ($filter['banner_group_name']) {
            $query->where('banner_group_name', '=', $filter['banner_group_name']);
        }

        if ($filter['keyword']) {
            $query->where(
                function ($subQuery) use ($params, $db) {
                    $keyWord = $db->escape(mb_strtolower($params['filter']['keyword']));

                    $subQuery->orWhere('banner_descriptions.name', 'like', '%' . $keyWord . '%');
                    $subQuery->orWhere('banner_descriptions.description', 'like', '%' . $keyWord . '%');
                    $subQuery->orWhere('banner_descriptions.meta', 'like', '%' . $keyWord . '%');
                    $subQuery->orWhere('banners.target_url', 'like', '%' . $keyWord . '%');

                    //allow to extend search criteria
                    $hookParams = $params;
                    $hookParams['subquery_keyword'] = true;
                    Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $subQuery, $hookParams);
                }
            );
        }

        //NOTE: order by must be raw sql string
        $sort_data = [
            'name'              => "LCASE(" . $bd_table . ".name)",
            'sort_order'        => $b_table . ".sort_order",
            'date_modified'     => $b_table . ".date_modified",
            'banner_type'       => $b_table . ".banner_type",
            'banner_group_name' => $b_table . ".banner_group_name",
        ];

        $orderBy = $sort_data[$params['sort']] ?: 'name';
        if (isset($params['order']) && (strtoupper($params['order']) == 'DESC')) {
            $sorting = "desc";
        } else {
            $sorting = "asc";
        }

        // for SF sort by banner group first
        if (ABC::env('IS_ADMIN') !== true) {
            $query->orderByRaw($sort_data['banner_group_name']);
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
        $output = $query->useCache('banner')->get();
        //add total number of rows into each row
        $totalNumRows = $db->sql_get_row_count();
        for ($i = 0; $i < $output->count(); $i++) {
            $output[$i]['total_num_rows'] = $totalNumRows;
        }

        return $output;
    }

    public static function getBanner(int $bannerId)
    {
        return Banner::getBanners(
            [
                'filter' => [
                    'include' => [$bannerId]
                ]
            ]
        )?->first();
    }
}