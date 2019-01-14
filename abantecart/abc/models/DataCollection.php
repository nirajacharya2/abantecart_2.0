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

class DataCollection
{
    /**
     * @var array
     */
    private $collection = [];

    /**
     * DataCollection constructor.
     */
    public function __construct()
    {
        //load settings

    }

    /**
     * @param string $node
     *
     * @return array|mixed
     */
    public function getCollection($node = '') {
        if ($node) {
            if (isset($this->collection[$node])) {
                return $this->collection[$node];
            }
        } else {
            return $this->collection;
        }
    }

    /**
     * @param $data
     */
    public function addCollection($data) {
        if ($data && is_array($data)) {
            $this->collection = array_merge_recursive($this->collection, $data);
        }
    }
}