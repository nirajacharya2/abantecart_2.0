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
                'text'                 => 'text_products_popular',
                'rl_object_name'       => 'products',
                'data_type'            => 'product_id',
                'storefront_model'     => Product::class,
                'storefront_method'    => 'getPopularProducts',
                'storefront_view_path' => 'product/product',
            ],
            'catalog_product_getSpecialProducts' => [
                'text'                 => 'text_products_special',
                'rl_object_name'       => 'products',
                'data_type'            => 'product_id',
                'storefront_model'     => Product::class,
                'storefront_method'    => 'getProductSpecials',
                'storefront_view_path' => 'product/product',
            ],
            'catalog_category_getCategories'     => [
                'text'                 => 'text_categories',
                'rl_object_name'       => 'categories',
                'data_type'            => 'category_id',
                'storefront_model'     => Category::class,
                'storefront_method'    => 'getCategoriesData',
                'storefront_view_path' => 'product/category',
            ],
            'catalog_category_getManufacturers'  => [
                'text'                 => 'text_manufacturers',
                'rl_object_name'       => 'manufacturers',
                'data_type'            => 'manufacturer_id',
                'storefront_model'     => Manufacturer::class,
                'storefront_method'    => 'getManufacturers',
                'storefront_view_path' => 'product/manufacturer',
            ],
            'catalog_product_getFeatured'        => [
                'text'                 => 'text_featured',
                'rl_object_name'       => 'products',
                'data_type'            => 'product_id',
                'storefront_model'     => Product::class,
                'storefront_method'    => 'getFeaturedProducts',
                'storefront_view_path' => 'product/product',
            ],
            'catalog_product_getLatest'          => [
                'text'                 => 'text_latest',
                'rl_object_name'       => 'products',
                'data_type'            => 'product_id',
                'storefront_model'     => Product::class,
                'storefront_method'    => 'getLatestProducts',
                'storefront_view_path' => 'product/product',
            ],
            'catalog_product_getBestsellers'     => [
                'text'                 => 'text_bestsellers',
                'rl_object_name'       => 'products',
                'data_type'            => 'product_id',
                'storefront_model'     => Product::class,
                'storefront_method'    => 'getBestsellerProducts',
                'storefront_view_path' => 'product/product',
            ],
            'media'                              => ['text' => 'text_media'],
            'custom_products'                    => [

                'model'                => 'catalog/product',
                'total_method'         => 'getTotalProducts',
                'method'               => 'getProducts',
                'language'             => 'catalog/product',
                'data_type'            => 'product_id',
                'view_path'            => 'catalog/product/update',
                'rl_object_name'       => 'products',
                'text'                 => 'text_custom_products',
                'storefront_model'     => Product::class,
                'storefront_method'    => 'getProducts',
                'storefront_view_path' => 'product/product',
                'items_list_url'       => 'product/product/related',
            ],
            'custom_categories'                  => [
                'model'                => 'catalog/category',
                'total_method'         => 'getTotalCategories',
                'method'               => 'getCategoriesData',
                'language'             => 'catalog/category',
                'data_type'            => 'category_id',
                'view_path'            => 'catalog/category/update',
                'rl_object_name'       => 'categories',
                'text'                 => 'text_custom_categories',
                'storefront_model'     => Category::class,
                'storefront_method'    => 'getCategoriesData',
                'storefront_view_path' => 'product/category',
                'items_list_url'       => 'product/product/product_categories',
            ],
            'custom_manufacturers'               => [
                'model'                => 'catalog/manufacturer',
                'total_method'         => 'getTotalManufacturers',
                'method'               => 'getManufacturers',
                'language'             => 'catalog/manufacturer',
                'data_type'            => 'manufacturer_id',
                'view_path'            => 'catalog/category/update',
                'rl_object_name'       => 'manufacturers',
                'text'                 => 'text_custom_manufacturers',
                'storefront_model'     => Manufacturer::class,
                'storefront_method'    => 'getManufacturers',
                'storefront_view_path' => 'product/manufacturer',
                'items_list_url'       => 'catalog/manufacturer_listing/getManufacturers',
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
     * @param string $method
     * @param array $args
     *
     * @return array|false
     */
    public function getListingMethodArguments($model, $method, $args = [])
    {
        if (!$method || !$model || !$args) {
            return false;
        }
        $output = [];
        if ($model == Category::class && $method == 'getCategoriesData') {
            $output = [
                'params' => [
                    'limit'       => $args['limit'],
                    'language_id' => $args['language_id'],
                    'store_id'    => $args['store_id'],
                    'parent_id'   => (int) $args['parent_id'],
                ],
            ];

            //in case when custom list of categories
            $include = $this->getCustomListIds($args['store_id'], 'category_id');
            if ($include) {
                $output['params']['include'] = $include;
                unset(
                    $output['params']['limit'],
                    $output['params']['parent_id']
                );
            }
        } elseif ($model == Manufacturer::class && $method == 'getManufacturers') {
            $output = [
                'params' => [
                    'limit'       => $args['limit'],
                    'language_id' => $args['language_id'],
                    'store_id'    => $args['store_id'],
                ],
            ];

            //in case when custom list of categories
            $include = $this->getCustomListIds($args['store_id'], 'manufacturer_id');
            if ($include) {
                $output['params']['include'] = $include;
                unset(
                    $output['params']['limit']
                );
            }
        } elseif ($model == Product::class) {
            if (in_array(
                $method,
                [
                    'getPopularProducts',
                    'getProductSpecials',
                    'getBestsellerProducts',
                    'getFeaturedProducts',
                    'getLatestProducts',
                ]
            )
            ) {
                $output = [
                    'limit'  => $args['limit'],
                    'filter' => [
                        'customer_group_id' => $args['customer_group_id'],
                        'store_id'          => $args['store_id'],
                    ],
                ];

                if (in_array(
                    $method,
                    ['getProductSpecials', 'getBestsellerProducts', 'getFeaturedProducts']
                )) {
                    $output['start'] = 0;
                    $output['sort'] = 'sort_order';
                    $output = ['params' => $output];
                }
            }
            //for custom products list
            if ($method == 'getProducts') {
                $include = $this->getCustomListIds($args['store_id'], 'product_id');
                if (!$include) {
                    return false;
                }

                $output = [
                    'params' => [
                        'with_all' => true,
                        'filter'   => ['include' => $include],
                    ],
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
                $include[] = (int) $item['id'];
            }
        }
        return $include;
    }
}