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
use abc\models\casts\Serialized;
use abc\models\system\Store;
use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

/**
 * Class BannerStat
 *
 * @property int $rowid
 * @property int $banner_id
 * @property int $type
 * @property Carbon $time
 * @property int $store_id
 * @property string $user_info
 *
 * @property Banner $banner
 *
 * @package abc\models
 */
class BannerStat extends BaseModel
{
    protected $primaryKey = 'rowid';
    public $timestamps = false;
    public $table = 'banner_stat';

    protected $casts = [
        'banner_id' => 'int',
        'type'      => 'int',
        'time'      => 'datetime',
        'store_id'  => 'int',
        'user_info' => Serialized::class
    ];

    protected $dates = [
        'time',
    ];

    protected $fillable = [
        'banner_id',
        //activity type. 1-view, 2-click, 0 - unknown
        'type',
        'time',
        'store_id',
        'user_info',
    ];

    protected $rules = [
        'banner_id' => [
            'checks'   => [
                'integer',
                'required'
            ],
            'messages' => [
                '*' => ['default_text' => 'Banner ID is not Integer!'],
            ],
        ],
        'store_id'  => [
            'checks'   => [
                'integer',
                'required'
            ],
            'messages' => [
                '*' => ['default_text' => 'Banner ID is not Integer!'],
            ],
        ],
    ];

    public function banner()
    {
        return $this->belongsTo(Banner::class, 'banner_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * @param array $params
     * @return array|Collection
     */
    public static function getStatistic(array $params)
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

        //override to use prepared version of filter inside hooks
        $params['filter'] = $filter;

        $db = Registry::db();
        $prefix = $db->prefix();
        $b_table = $db->table_name('banners');
        $bd_table = $db->table_name('banner_descriptions');

        $query = Banner::selectRaw(Registry::db()->raw_sql_row_count() . ' ' . $b_table . '.banner_id')
            ->addSelect(['banners.banner_type', 'banners.banner_group_name', 'banner_descriptions.name'])
            ->selectRaw(
                'COUNT(' . $prefix . 'bs_view.type) as viewed, COUNT(' . $prefix . 'bs_click.type) as clicked,
            CASE WHEN COUNT(' . $prefix . 'bs_click.type) > 0
                 THEN ROUND(COUNT(' . $prefix . 'bs_view.type) * 100 / COUNT(' . $prefix . 'bs_click.type))
                 ELSE 0 END as percents'
            )
            ->leftJoin(
                'banner_stat as bs_view',
                function ($join) use ($filter) {
                    $alias = 'bs_view';
                    $join->on('bs_view.banner_id', '=', 'banners.banner_id')
                        ->where('bs_view.type', '=', 1);
                    if ($filter['range']) {
                        static::applyDateRange($filter, $join, $alias);
                    }
                }
            )->leftJoin(
                'banner_stat as bs_click',
                function ($join) use ($filter) {
                    $alias = 'bs_click';
                    $join->on('bs_click.banner_id', '=', 'banners.banner_id')
                        ->where('bs_click.type', '=', 2);
                    if ($filter['range']) {
                        static::applyDateRange($filter, $join, $alias);
                    }
                }
            )->leftJoin(
                'banner_descriptions',
                function ($join) use ($params) {
                    /** @var JoinClause $join */
                    $join->on('banner_descriptions.banner_id', '=', 'banners.banner_id')
                        ->where('banner_descriptions.language_id', '=', $params['language_id']);
                }
            )->groupBy(
                'banners.banner_id',
                'banners.banner_group_name',
                'banner_descriptions.name'
            );

        //NOTE: order by must be raw sql string
        $sort_data = [
            'name'              => "LCASE(" . $bd_table . ".name)",
            'sort_order'        => $b_table . ".sort_order",
            'date_modified'     => $b_table . ".date_modified",
            'banner_type'       => $b_table . ".banner_type",
            'banner_group_name' => $b_table . ".banner_group_name",
            'clicked'           => 'clicked',
            'viewed'            => 'viewed',
            'percents'          => 'percents',
        ];

        $orderBy = $sort_data[$params['sort']] ?: 'name';
        if (isset($params['order']) && (strtoupper($params['order']) == 'DESC')) {
            $sorting = "desc";
        } else {
            $sorting = "asc";
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
        return $query->useCache('banner')->get();
    }

}
