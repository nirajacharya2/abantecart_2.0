<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
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
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Facades\Cache;

class QueryBuilder extends Builder
{

    protected $cacheStorage = null;

    public function __construct(ConnectionInterface $connection, Grammar $grammar = null, Processor $processor = null)
    {
        parent::__construct($connection, $grammar, $processor);
        $this->cacheStorage = ABC::env('CACHE')['cache.default'];
    }

    /**
    * Returns a Unique String that can identify this Query.
    *
    * @return string
    */
    protected function getCacheKey()
    {
         return json_encode([
             $this->toSql() => $this->getBindings()
         ]);
    }

    public function useCacheStorage($storage = ''){
        if($storage) {
            $this->cacheStorage = $storage;
        }
    }

    protected function runSelect()
    {
        /** @var AbcCache $cache */
        $cache = Registry::cache();
        return $cache->remember(
            $this->getCacheKey(),
            1,
            function() {
                 return parent::runSelect();
            }
        );
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
    public function active($tableName = '') {
        $fieldName = 'status';
        if (!empty($tableName)) {
            $fieldName = $tableName.'.'.$fieldName;
        }
        $this->where($fieldName, '=',1);
        return $this;
    }
}
