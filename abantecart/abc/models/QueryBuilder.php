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

namespace abc\models;

use abc\core\ABC;
use abc\core\engine\Registry;
use abc\core\lib\AbcCache;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\RedisTaggedCache;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class QueryBuilder extends Builder
{

    protected $cacheStatus = false;
    protected $cacheStore = '';
    protected $cacheTags = [];
    protected $cacheKey = '';

    /**
     * Wrapper to use cache for some generated queries
     * See also noCache() method to use it in the controllers
     *
     * @return array|mixed
     */
    protected function runSelect()
    {
        $cache = Registry::cache();
        if (!$this->cacheStatus || !$cache instanceof AbcCache) {
            $this->cacheKey = '';
            return parent::runSelect();
        }

        $key = 'sql_' . md5($this->toSql() . '~' . var_export($this->getBindings(), true));
        /** @var RedisTaggedCache|FileStore $repository */
        $repository = $cache->tags($this->cacheTags);
        $this->cacheKey = $cacheKey = method_exists($repository, 'taggedItemKey')
            ? $repository->taggedItemKey($key)
            : ($this->cacheTags ? implode('.', $this->cacheTags).'.' : '') .$key;
        $ttl = (int)ABC::env('CACHE')['stores'][$cache::$currentStore]['ttl'] ?: 777;
        $output = $cache->remember(
            $cacheKey,
            $ttl,
            function () use ($cacheKey, $ttl) {
                $result = parent::runSelect();
                //NOTE: saving of total founded rows count
                if (str_contains($this->toSql(), Registry::db()->raw_sql_row_count())) {
                    Registry::cache()->put(
                        $cacheKey . '_total_rows_count',
                        Registry::db()->sql_get_row_count(),
                        $ttl
                    );
                }
                return $result;
            }
        );

        $this->cacheStatus = false;
        return $output;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param array|string $columns
     * @return Collection
     */
    public function get($columns = ['*'])
    {
        $collection = collect($this->onceWithColumns(Arr::wrap($columns), function () {
            return $this->processor->processSelect($this, $this->runSelect());
        }));


        //add additional property into collection (total found rows count)
        if (str_contains($this->toSql(), Registry::db()->raw_sql_row_count())) {
            $foundRowsCount = $this->cacheKey
                ? Registry::cache()->get($this->cacheKey . '_total_rows_count')
                : Registry::db()->sql_get_row_count();
            $collection::macro(
                'getFoundRowsCount',
                function () use ($foundRowsCount) {
                    return (int)$foundRowsCount;
                }
            );
        }

        return $collection;
    }

    public function setGridRequest($data = [])
    {
        if ($data['sort'] != 'description.title') {
            if (isset($data['order']) && (strtoupper($data['order']) == 'DESC')) {
                return $this->orderBy($data['sort'], 'desc');
            } else {
                return $this->orderBy($data['sort']);
            }
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $this->offset((int)$data['start'])
                 ->limit((int)$data['limit']);
        }

        $this->whereNull('date_deleted');

        return $this;
    }

    /**
     * @param string $tableName
     *
     * @return $this
     */
    public function active($tableName = '')
    {
        $fieldName = 'status';
        if (!empty($tableName)) {
            $fieldName = $tableName.'.'.$fieldName;
        }
        $this->where($fieldName, '=', 1);
        return $this;
    }

    public function noCache()
    {
        $this->cacheStatus = false;
        return $this;
    }

    /**
     * @param array | string $tags
     * @param string $store
     *
     * @return $this
     */
    public function useCache($tags, $store = '')
    {
        //if cache disabled - returns query
        if (!Registry::config()->get('config_cache_enable')) {
            return $this;
        }

        $this->cacheTags = $tags ?: [];
        $this->cacheTags = is_string($this->cacheTags) ? [$this->cacheTags] : $this->cacheTags;
        $this->cacheStore = $store ? : Registry::cache()->getCurrentStore();
        $this->cacheStatus = true;
        return $this;
    }
}