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

use Illuminate\Database\Query\Builder;

class QueryBuilder extends Builder
{
    public function setGridRequest($data = [])
    {
        $query = $this->newQuery();
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

    public function active() {
        $this->where('status', 1)
            ->whereNull('date_deleted');
        return $this;
    }

}
