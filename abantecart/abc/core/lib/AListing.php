<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2023 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\core\lib;

use abc\models\catalog\Category;
use abc\models\catalog\Manufacturer;
use abc\models\catalog\Product;
use abc\models\layout\CustomList;

class AListing
{

    /** @var int */
    protected $custom_block_id;
    /** @var array */
    public $data_sources = [];

    /**
     * @param int $custom_block_id
     */
    public function __construct($custom_block_id)
    {
        $this->custom_block_id = (int)$custom_block_id;
        // data-sources
        $this->data_sources = [
            'catalog_product_getPopularProducts' => [
                'model'          => Product::class,
                /** @see Product::getPopularProducts() */
                'method'         => 'getPopularProducts',
                'text'           => 'text_products_popular',
                'rl_object_name' => 'products',
                'data_type'      => 'product_id'
            ],
            'catalog_product_getSpecialProducts' => [
                'model'          => Product::class,
                /** @see Product::getProductSpecials() */
                'method'         => 'getProductSpecials',
                'text'           => 'text_products_special',
                'rl_object_name' => 'products',
                'data_type'      => 'product_id'
            ],
            'catalog_product_getFeatured'        => [
                'model'          => Product::class,
                /** @see Product::getFeaturedProducts() */
                'method'         => 'getFeaturedProducts',
                'text'           => 'text_featured',
                'rl_object_name' => 'products',
                'data_type'      => 'product_id'
            ],
            'catalog_product_getLatest'          => [
                'model'          => Product::class,
                /** @see Product::getLatestProducts() */
                'method'         => 'getLatestProducts',
                'text'           => 'text_latest',
                'rl_object_name' => 'products',
                'data_type'      => 'product_id',
            ],
            'catalog_product_getBestsellers'     => [
                'model'          => Product::class,
                /** @see Product::getBestsellerProducts() */
                'method'         => 'getBestsellerProducts',
                'text'           => 'text_bestsellers',
                'rl_object_name' => 'products',
                'data_type'      => 'product_id'
            ],
            'catalog_category_getCategories'     => [
                'model'          => Category::class,
                /** @see Category::getCategoriesData() */
                'method'         => 'getCategoriesData',
                'text'           => 'text_categories',
                'rl_object_name' => 'categories',
                'data_type'      => 'category_id'
            ],
            'catalog_manufacturer_getManufacturers' => [
                'model'          => Manufacturer::class,
                /** @see Manufacturer::getManufacturers() */
                'method'         => 'getManufacturers',
                'text'           => 'text_manufacturers',
                'rl_object_name' => 'manufacturers',
                'data_type'      => 'manufacturer_id',
            ],
            'media' => [
                'text' => 'text_media'
            ],
            'custom_products' => [
                'model'          => Product::class,
                /** @see Product::getProducts() */
                'method'         => 'getProducts',
                'language'       => 'catalog/product',
                'data_type'      => 'product_id',
                'view_path'      => 'catalog/product/update',
                'rl_object_name' => 'products',
                'text'           => 'text_custom_products',
                'items_list_url' => 'product/product/related',
            ],
            'custom_categories'                  => [
                'model'          => Category::class,
                /** @see Category::getCategoriesData() */
                'method'         => 'getCategoriesData',
                'language'       => 'catalog/category',
                'data_type'      => 'category_id',
                'view_path'      => 'catalog/category/update',
                'rl_object_name' => 'categories',
                'text'           => 'text_custom_categories',
                'items_list_url' => 'product/product/product_categories',
            ],
            'custom_manufacturers'               => [
                'model'          => Manufacturer::class,
                /** @see Manufacturer::getManufacturers() */
                'method'         => 'getManufacturers',
                'language'       => 'catalog/manufacturer',
                'data_type'      => 'manufacturer_id',
                'view_path'      => 'catalog/category/update',
                'rl_object_name' => 'manufacturers',
                'text'           => 'text_custom_manufacturers',
                'items_list_url' => 'catalog/manufacturer_listing/getManufacturers',
            ],
        ];
    }

    /**
     * @param int $store_id
     * @return array
     */
    public function getCustomList(int $store_id)
    {
        if (!$this->custom_block_id) {
            return [];
        }

        return (array)CustomList::where(
            [
                'custom_block_id' => $this->custom_block_id,
                'store_id'        => $store_id
            ]
        )->orderBy('sort_order')->useCache('layout')->get()?->toArray();
    }

    /**
     * @return array
     */
    public function getListingDataSources()
    {
        return $this->data_sources;
    }

    /**
     * @param string $key
     * @param array $data
     */
    public function addListingDataSource($key, $data)
    {
        $this->data_sources[$key] = $data;
    }

    /**
     * @param string $key
     */
    public function deleteListingDataSource($key)
    {
        unset($this->data_sources[$key]);
    }

    /**
     * method returns argument for call_user_func function usage when call storefront model to get list
     *
     * @param string $model
     * @param string $listingSourceTextId
     * @param array $args
     *
     * @return array|false
     */
    public function getListingMethodArguments($model, $listingSourceTextId, $args = [])
    {
        if (!$listingSourceTextId || !$model || !$args) {
            return false;
        }
        $output = [];
        if ($model == Category::class) {
            $output = [
                'params' => [
                    'language_id' => $args['language_id'],
                    'store_id'    => $args['store_id'],

                ],
            ];

            if (!str_contains($listingSourceTextId, 'custom')) {
                $output['params']['filter']['parent_id'] = $args['parent_id'];
                $output['params']['limit'] = $args['limit'];
                $output['params']['start'] = (int)$args['start'];
                $output['params']['sort'] = 'sort_order';
            } else {
                //for custom products list
                $include = $this->getCustomListIds($args['store_id'], 'category_id');
                if (!$include) {
                    return false;
                }
                $output['params']['filter'] = [
                    'include' => $include
                ];
            }
        } elseif ($model == Manufacturer::class) {
            $output['params'] = [
                'store_id' => $args['store_id'],
            ];
            if (!str_contains($listingSourceTextId, 'custom')) {
                $output['params']['limit'] = $args['limit'];
                $output['params']['start'] = (int)$args['start'];
                $output['params']['sort'] = 'sort_order';
            } else {
                //for custom products list
                $include = $this->getCustomListIds($args['store_id'], 'manufacturer_id');
                if (!$include) {
                    return false;
                }
                $output['params']['filter'] = [
                    'include' => $include
                ];
            }
        } elseif ($model == Product::class) {

            $output['params'] = [
                'with_all'          => true,
                'store_id'          => $args['store_id'],
                'customer_group_id' => $args['customer_group_id'],
            ];
            if (!str_contains($listingSourceTextId, 'custom')) {
                $output['params']['limit'] = $args['limit'];
                $output['params']['start'] = (int)$args['start'];
                $output['params']['sort'] = 'sort_order';
            } else {
                //for custom products list
                $include = $this->getCustomListIds($args['store_id'], 'product_id');
                if (!$include) {
                    return false;
                }
                $output['params']['filter'] = [
                    'include' => $include
                ];
            }
        }
        return $output;
    }

    protected function getCustomListIds($storeId, $keyName)
    {
        $list = $this->getCustomList((int)$storeId);
        if (!$list) {
            return false;
        }
        $include = [];
        foreach ($list as $item) {
            if ($item['data_type'] == $keyName) {
                $include[] = (int)$item['id'];
            }
        }
        return $include;
    }
}