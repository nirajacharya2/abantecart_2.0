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

namespace abc\models\content;

use abc\core\ABC;
use abc\core\engine\Registry;
use abc\core\lib\ALayoutManager;
use abc\models\BaseModel;
use abc\models\casts\Html;
use abc\models\casts\Json;
use abc\models\catalog\UrlAlias;
use Exception;
use H;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class Content
 *
 * @property int $content_id
 * @property int $parent_id
 * @property int $sort_order
 * @property int $status
 *
 * @property ContentDescription $description
 * @property ContentDescription|Collection $descriptions
 * @property ContentsToStore|Collection $stores
 *
 * @package abc\models
 */
class Content extends BaseModel
{
    protected $primaryKey = 'content_id';
    protected $casts = [
        'content_id' => 'int',
        'parent_id'  => 'int',
        'sort_order' => 'int',
        'status'     => 'int',
        'hide_title' => 'bool'
    ];

    protected $fillable = [
        'parent_id',
        'sort_order',
        'status',
        'hide_title'
    ];

    protected $rules = [
        /** @see validate() */
        'status'     => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not boolean!',
                ],
            ],
        ],
        'parent_id'  => [
            'checks'   => [
                'integer',
                'nullable',
                'exists:contents,content_id',
            ],
            'messages' => [
                '*' => ['default_text' => 'Parent ID is not integer or absent in contents table!'],
            ],
        ],
        'hide_title' => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => '"Hide Title" must be 1 or 0 !',
                ],
            ],
        ],
        'sort_order' => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Sort Order is not integer!',
                ],
            ],
        ]
    ];

    /**
     * @return array
     */
    public static function getLeafContents()
    {
        $query = self::select(['contents.content_id']);
        $query->leftJoin(
            'contents as t2',
            't2.parent_id',
            '=',
            'contents.content_id'
        )->whereNull('t2.content_id');

        $contents = $query->useCache('content')->get();

        $result = [];
        if ($contents) {
            return array_column((array)$contents?->toArray(), 'content_id', 'content_id');
        }
        return $result;
    }

    /**
     * @param $params
     * @return Collection
     */
    public static function getContents($params = [])
    {
        $params['language_id'] = $params['language_id'] ?: static::$current_language_id;
        $params['sort'] = $params['sort'] ?: 'contents.sort_order';
        $params['order'] = $params['order'] ?: 'ASC';
        $params['start'] = max($params['start'], 0);
        $params['limit'] = abs((int)$params['limit']) ?: 200;

        $db = Registry::db();
        $b_table = $db->table_name('contents');
        $bd_table = $db->table_name('content_descriptions');

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

        $arSelect = [
            $db->raw('SQL_CALC_FOUND_ROWS ' . $db->table_name('contents') . '.content_id'),
            'content_descriptions.*',
            'contents.*',
            'pb.name as parent_name',
            'url_aliases.keyword'
        ];

        $query = self::select($arSelect);
        $query->join(
            'content_descriptions',
            'content_descriptions.content_id',
            '=',
            'contents.content_id'
        );
        $query->leftJoin(
            'contents as b',
            'b.content_id',
            '=',
            'contents.parent_id'
        )->leftJoin(
            'content_descriptions as pb',
            'pb.content_id',
            '=',
            'b.content_id'
        );

        $query->join(
            'contents_to_stores',
            function ($join) use ($filter) {
                /** @var JoinClause $join */
                $join->on('contents_to_stores.content_id', '=', 'contents.content_id')
                    ->where('contents_to_stores.store_id', '=', $filter['store_id']);
            }
        );
        $query->leftJoin(
            'url_aliases',
            function ($join) use ($params, $db) {
                /** @var JoinClause $join */
                $join->on(
                    'url_aliases.language_id', '=', 'content_descriptions.language_id')
                    ->whereRaw(
                        $db->table_name('url_aliases') . ".query "
                        . "="
                        . " CONCAT('content_id=', " . $db->table_name('contents') . ".content_id)"
                    );
            }
        );

        if (isset($filter['parent_id'])) {
            if ($filter['parent_id'] > 0) {
                $query->where('contents.parent_id', '=', $filter['parent_id']);
            } else {
                $query->whereNull('contents.parent_id');
            }
        }

        if (H::has_value($params['sort'])) {
            $query = $query->orderBy($params['sort'], H::has_value($params['order']) ? $params['order'] : 'asc');
        }

        if (H::has_value($params['start'])) {
            $query = $query->offset((int)$params['start']);
        }

        $query = $query->limit((int)$params['limit'] ?: 20);

        if ($filter['keyword']) {
            $query->where(
                function ($subQuery) use ($params, $db) {
                    $keyWord = mb_strtolower($params['filter']['keyword']);

                    $subQuery->orWhere('content_descriptions.name', 'like', '%' . $keyWord . '%');
                    $subQuery->orWhere('content_descriptions.title', 'like', '%' . $keyWord . '%');
                    //allow to extend search criteria
                    $hookParams = $params;
                    $hookParams['subquery_keyword'] = true;
                    Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $subQuery, $hookParams);
                }
            );
        }

        if ((array)$filter['include']) {
            $query->whereIn('contents.content_id', (array)$filter['include']);
        }
        if ((array)$filter['exclude']) {
            $query->whereNotIn('contents.content_id', (array)$filter['exclude']);
        }

        if (H::has_value($params['filter']['status'])) {
            $query = $query->where('contents.status', $params['filter']['status']);
        }

        //NOTE: order by must be raw sql string
        $sort_data = [
            'name'          => "LCASE(" . $bd_table . ".name)",
            'sort_order'    => $b_table . ".sort_order",
            'parent_name'   => "parent_name",
            'status'        => $b_table . ".status",
            'date_modified' => $b_table . ".date_modified",
        ];

        $orderBy = $sort_data[$params['sort']] ?: 'name';
        if (isset($params['order']) && (strtoupper($params['order']) == 'DESC')) {
            $sorting = "desc";
        } else {
            $sorting = "asc";
        }

        $query->distinct();
        $query->orderByRaw($orderBy . " " . $sorting);

        //pagination
        if (isset($params['start'])) {
            $params['start'] = max(0, $params['start']);
            $query->offset((int)$params['start'])->limit((int)$params['limit']);
        }
        $output = $query->useCache('content')->get();

        //add total number of rows into each row
        $cd = new ContentDescription();
        $casts = $cd->getCasts();
        foreach ($output as &$item) {
            foreach ($item->attributes as $name => &$value) {
                if (isset($casts[$name]) && class_exists($casts[$name])) {
                    /** @var Html|Json $castable */
                    $castable = new $casts[$name];
                    if ($castable instanceof Castable) {
                        $value = $castable->get($cd, $name, $value, []);
                    }
                }
            }
        }
        return $output;
    }

    /**
     * @return HasOne
     */
    public function description()
    {
        return $this->hasOne(ContentDescription::class, 'content_id')
            ->where('language_id', '=', static::$current_language_id);
    }

    public function descriptions()
    {
        return $this->hasMany(ContentDescription::class, 'content_id');
    }

    /**
     * @return HasOne
     */
    public function parent()
    {
        return $this->hasOne(Content::class, 'content_id', 'parent_id');
    }

    /**
     * @return HasMany
     */
    public function children()
    {
        return $this->hasMany(Content::class, 'parent_id', 'content_id');
    }

    public function stores()
    {
        return $this->hasMany(ContentsToStore::class, 'content_id');
    }

    /**
     * @param $data
     *
     * @return bool|mixed
     * @throws Exception
     */
    public static function addContent($data)
    {
        if (!$data) {
            return false;
        }
        $db = Registry::db();
        $data['parent_id'] = (int)$data['parent_id'] > 0 ? (int)$data['parent_id'] : null;
        $data['language_id'] = $data['language_id'] ?: self::$current_language_id;
        $db->beginTransaction();

        try {
            $content = new Content($data);
            $content->save();

            $data['content_id'] = $contentId = $content->getKey();

            $description = new ContentDescription($data);
            $content->descriptions()->save($description);

            $contentToStore = [];
            foreach ((array)$data['stores'] as $store_id) {
                $contentToStore[] = [
                    'content_id' => $contentId,
                    'store_id'   => (int)$store_id,
                ];
            }
            $db->table('contents_to_stores')->insert($contentToStore);

            //allow to extend this method from extensions
            Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $content, func_get_args());
            if ($data['keyword']) {
                UrlAlias::setCurrentLanguageID($data['language_id']);
                UrlAlias::setContentKeyword(($data['keyword']), (int)$contentId);
            }

            Registry::cache()->flush('content');
            $db->commit();
            return $contentId;
        } catch (Exception $e) {
            Registry::log()->error($e->getMessage());
            $db->rollback();
            return false;
        }
    }

    public static function editContent(int $contentId, array $data)
    {
        $language = Registry::language();
        $languageId = $data['language_id'] ?: static::$current_language_id;
        $db = Registry::db();

        $db->beginTransaction();
        try {
            $content = static::find($contentId);

            if (!$content) {
                throw new Exception(__FUNCTION__ . ': Content #' . $contentId . ' not found');
            }

            $content->update($data);

            $bd = new ContentDescription();
            $fillable = $bd->getFillable();

            $update = [];
            foreach ($fillable as $field_name) {
                if (isset($data[$field_name])) {
                    $update[$field_name] = $data[$field_name];
                }
            }

            if (count($update)) {
                $language->replaceDescriptions('content_descriptions',
                    ['content_id' => $contentId],
                    [$languageId => $update]);
            }

            $contentToStore = [];
            $db->table('contents_to_stores')
                ->where('content_id', '=', (int)$contentId)
                ->delete();
            if (isset($data['stores'])) {
                foreach ($data['stores'] as $store_id) {
                    $contentToStore[] = [
                        'content_id' => $contentId,
                        'store_id'   => (int)$store_id,
                    ];
                }
            } else {
                $contentToStore[] = [
                    'content_id' => $contentId,
                    'store_id'   => 0,
                ];
            }
            $db->table('contents_to_stores')
                ->insert($contentToStore);


            if (isset($data['keyword'])) {
                UrlAlias::setCurrentLanguageID($languageId);
                UrlAlias::setContentKeyword(($data['keyword']), $contentId);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            Registry::log()->error($e->getMessage());
            return false;
        }

        Registry::cache()->flush('content');
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
            $lm->deleteAllPagesLayouts('pages/content/content', 'content_id', $this->getKey());
        } catch (Exception $e) {
        }

        UrlAlias::where('query', '=', 'content_id=' . $this->getKey())->delete();
        return parent::delete();
    }


    /**
     * @param int $contentId
     * @return Content|ContentDescription|null
     */
    public static function getContent(int $contentId)
    {

        $params = [
            'filter' => [
                'include' => [$contentId]
            ]
        ];

        if (ABC::env('IS_ADMIN') !== true) {
            $params['filter']['status'] = 1;
            $params['filter']['store_id'] = (int)Registry::config()->get('config_store_id');
        }
        $output = static::getContents($params)?->first();

        if (ABC::env('IS_ADMIN') === true && $output) {
            $output->stores = Registry::db()->table('contents_to_stores')
                ->where('content_id', '=', $contentId)
                ->get()?->pluck('store_id')->toArray();
        }
        return $output;
    }

    public static function getTree(int $parentId = 0, int $level = 0)
    {
        $searchParams = [
            'limit'  => 10000,
            'filter' => [
                'parent_id' => $parentId
            ],
            'sort'   => 'name'
        ];

        $all = static::getContents($searchParams)?->toArray();
        $output = [];
        foreach ($all as $item) {
            $item['name'] = str_repeat("&nbsp;&nbsp;&nbsp;", $level) . $item['name'];
            $output[] = $item;
            $output = array_merge($output, static::getTree($item['content_id'], $level + 1));
        }

        return $output;
    }

}