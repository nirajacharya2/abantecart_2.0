<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2022 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\controllers\storefront;

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\engine\AResource;
use abc\core\engine\Registry;
use abc\models\catalog\Category;
use abc\models\catalog\CategoryDescription;
use abc\models\catalog\Product;
use abc\modules\traits\ProductListingTrait;

/**
 * Class ControllerPagesProductCategory
 *
 * @package abc\controllers\storefront
 */
class ControllerPagesProductCategory extends AController
{
    public $data = ['sorts' => []];

    use ProductListingTrait;

    public function __construct(Registry $registry, $instance_id, $controller, $parent_controller = '')
    {
        parent::__construct($registry, $instance_id, $controller, $parent_controller);
        $this->fillSortsList();
    }

    /**
     * Check if HTML Cache is enabled for the method
     *
     * @return array - array of data keys to be used for cache key building
     */
    public static function main_cache_keys()
    {
        return ['path', 'category_id', 'page', 'limit', 'sort', 'order'];
    }

    public function main()
    {
        $request = $this->request->get;
        $whishlist = '';

        //is this an embed mode
        if ($this->config->get('embed_mode') == true) {
            $cart_rt = 'r/checkout/cart/embed';
        } else {
            $cart_rt = 'checkout/cart';
        }
        $this->loadModel('catalog/product');
        $this->loadModel('tool/seo_url');
        $this->loadModel('catalog/review');

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('product/category');
        $this->document->resetBreadcrumbs();
        $this->document->addBreadcrumb([
                                           'href'      => $this->html->getHomeURL(),
                                           'text'      => $this->language->get('text_home'),
                                           'separator' => false,
                                       ]);

        if ($this->config->get('config_require_customer_login') && !$this->customer->isLogged()) {
            abc_redirect($this->html->getSecureURL('account/login'));
        }

        if (!isset($request['path']) && isset($request['category_id'])) {
            $request['path'] = $request['category_id'];
        }

        if (isset($request['path'])) {
            $path = '';
            $parts = explode('_', $request['path']);
            $category_id = end($parts);
            if (count($parts) == 1) {
                $category = Category::find((int) $request['path']);
                if ($category) {
                    $parts = explode('_', ($category->path ? : $request['path']));
                }
            }

            $query = CategoryDescription::select(['category_id', 'name']);
            $descriptions = $query->whereIn('category_id', $parts)
                                  ->where('language_id', '=', $this->language->getLanguageID())
                                  ->get()->toArray();
            $categoryNames = array_column($descriptions, 'name', 'category_id');

            foreach ($parts as $path_id) {
                if ($categoryNames[$path_id]) {
                    if (!$path) {
                        $path = $path_id;
                    } else {
                        $path .= '_'.$path_id;
                    }

                    $this->document->addBreadcrumb(
                        [
                           'href'      => $this->html->getSEOURL(
                               'product/category',
                               '&path='.$path, '&encode'
                           ),
                           'text'      => $categoryNames[$path_id],
                           'separator' => $this->language->get('text_separator'),
                       ]
                    );
                }
            }
        } else {
            $category_id = 0;
        }

        $category_info = [];
        if ($category_id) {
            $category_info = Category::getCategory($category_id);
        } elseif ($this->config->get('embed_mode') == true || isset($request['path'])) {
            //Display Top category when embed mode or have PATH parameter
            $category_info['name'] = $this->language->get('text_top_category');
        }

        if ($category_info) {
            $this->document->setTitle($category_info['name']);
            $this->document->setKeywords($category_info['meta_keywords']);
            $this->document->setDescription($category_info['meta_description']);

            $this->view->assign('heading_title', $category_info['name']);
            $this->view->assign(
                'description',
                html_entity_decode($category_info['description'], ENT_QUOTES, ABC::env('APP_CHARSET'))
            );
            $this->view->assign('text_sort', $this->language->get('text_sort'));

            $page = $request['page'] ?? 1;
            if (isset($request['limit'])) {
                $limit = (int) $request['limit'];
                $limit = min($limit, 50);
            } else {
                $limit = $this->config->get('config_catalog_limit');
            }

            $sorting_href = $request['sort'];
            if (!$sorting_href || !isset($this->data['sorts'][$request['sort']])) {
                $sorting_href = $this->config->get('config_product_default_sort_order');
            }
            list($sort, $order) = explode("-", $sorting_href);
            if ($sort == 'name') {
                $sort = 'pd.'.$sort;
            } elseif (in_array($sort, ['sort_order', 'price'])) {
                $sort = 'p.'.$sort;
            }

            $url = '&sort='.$sorting_href;

            if (isset($request['order'])) {
                $url .= '&order='.$request['order'];
            }

            $category_total = Category::getTotalCategoriesByCategoryId($category_id);
            $product_total = $this->model_catalog_product->getTotalProductsByCategoryId($category_id);

            if ($category_total || $product_total) {
                $categories = [];
                $results = Category::getCategories($category_id);
                $category_ids = array_map('intval', array_column($results, 'category_id'));

                //get thumbnails by one pass
                $resource = new AResource('image');
                $thumbnails = $resource->getMainThumbList(
                    'categories',
                    $category_ids,
                    $this->config->get('config_image_category_width'),
                    $this->config->get('config_image_category_height')
                );

                foreach ($results as $result) {
                    $thumbnail = $thumbnails[$result['category_id']];
                    $categories[] = [
                        'name'  => $result['name'],
                        'href'  => $this->html->getSEOURL(
                            'product/category',
                            '&path='.$request['path'].'_'.$result['category_id'].$url,
                            '&encode'
                        ),
                        'thumb' => $thumbnail,
                    ];
                }
                $this->view->assign('categories', $categories);
                $this->view->assign('button_add_to_cart', $this->language->get('button_add_to_cart'));
                $products_result = $this->model_catalog_product->getProductsByCategoryId(
                    $category_id,
                    $sort,
                    $order,
                    ($page - 1) * $limit,
                    $limit
                );
                $product_ids = array_map('intval', array_column($products_result, 'product_id'));
                $products = [];
                $products_info = $this->model_catalog_product->getProductsAllInfo($product_ids);
                $thumbnails = $resource->getMainThumbList(
                    'products',
                    $product_ids,
                    $this->config->get('config_image_product_width'),
                    $this->config->get('config_image_product_height')
                );
                $stock_info = $this->model_catalog_product->getProductsStockInfo($product_ids);

                $this->data['is_customer'] = false;
                if ($this->customer->isLogged() || $this->customer->isUnauthCustomer()) {
                    $this->data['is_customer'] = true;
                    $whishlist = $this->customer->getWishList();
                }

                foreach ($products_result as $k => $result) {
                    $thumbnail = $thumbnails[$result['product_id']];
                    $rating = $products_info[$result['product_id']]['rating'];
                    $special = false;
                    $discount = $products_info[$result['product_id']]['discount'];
                    if ($discount) {
                        $price = $this->currency->format(
                            $this->tax->calculate(
                                $discount,
                                $result['tax_class_id'],
                                $this->config->get('config_tax')
                            )
                        );
                    } else {
                        $price = $this->currency->format(
                            $this->tax->calculate(
                                $result['price'],
                                $result['tax_class_id'],
                                $this->config->get('config_tax')
                            )
                        );
                        $special = $products_info[$result['product_id']]['special'];
                        if ($special) {
                            $special = $this->currency->format(
                                $this->tax->calculate(
                                    $special,
                                    $result['tax_class_id'],
                                    $this->config->get('config_tax')
                                )
                            );
                        }
                    }

                    if ($products_info[$result['product_id']]['options']) {
                        $add = $this->html->getSEOURL(
                            'product/product',
                            '&product_id='.$result['product_id'],
                            '&encode'
                        );
                    } else {
                        if ($this->config->get('config_cart_ajax')) {
                            $add = '#';
                        } else {
                            $add = $this->html->getSecureURL($cart_rt, '&product_id='.$result['product_id'], '&encode');
                        }
                    }
                    //check for stock status, availability and config
                    $track_stock = false;
                    $in_stock = false;
                    $no_stock_text = $this->language->get('text_out_of_stock');
                    $total_quantity = 0;
                    $stock_checkout = $result['stock_checkout'] === ''
                        ? $this->config->get('config_stock_checkout')
                        : $result['stock_checkout'];
                    if ($stock_info[$result['product_id']]['subtract']) {
                        $track_stock = true;
                        $total_quantity = $stock_info[$result['product_id']]['quantity'];
                        //we have stock or out of stock checkout is allowed
                        if ($total_quantity > 0 || $stock_checkout) {
                            $in_stock = true;
                        }
                    }

                    $in_wishlist = false;
                    if ($whishlist && $whishlist[$result['product_id']]) {
                        $in_wishlist = true;
                    }

                    $catalog_mode = false;
                    if ($result['product_type_id']) {
                        $prodTypeSettings = Product::getProductTypeSettings((int) $result['product_id']);

                        if ($prodTypeSettings && is_array($prodTypeSettings)
                            && isset($prodTypeSettings['catalog_mode'])) {
                            $catalog_mode = (bool) $prodTypeSettings['catalog_mode'];
                        }
                    }

                    $products[$k] = array_merge($result,
                        [
                            'rating'                      => $rating,
                            'stars'                       => sprintf($this->language->get('text_stars'), $rating),
                            'thumb'                       => $thumbnail,
                            'price'                       => $price,
                            'raw_price'                   => $result['price'],
                            'options'                     => $products_info[$result['product_id']]['options'],
                            'special'                     => $special,
                            'href'                        => $this->html->getSEOURL(
                                'product/product',
                                '&path='.$request['path'].'&product_id='.$result['product_id'],
                                '&encode'
                            ),
                            'add'                         => $add,
                            'description'                 => html_entity_decode(
                                $result['description'],
                                ENT_QUOTES,
                                ABC::env('APP_CHARSET')
                            ),
                            'track_stock'                 => $track_stock,
                            'in_stock'                    => $in_stock,
                            'no_stock_text'               => $no_stock_text,
                            'total_quantity'              => $total_quantity,
                            'in_wishlist'                 => $in_wishlist,
                            'product_wishlist_add_url'    => $this->html->getURL(
                                'product/wishlist/add',
                                '&product_id='.$result['product_id']
                            ),
                            'product_wishlist_remove_url' => $this->html->getURL(
                                'product/wishlist/remove',
                                '&product_id='.$result['product_id']
                            ),
                            'catalog_mode'                => $catalog_mode,
                        ]);
                }
                $this->data['products'] = $products;

                if ($this->config->get('config_customer_price')) {
                    $display_price = true;
                } elseif ($this->customer->isLogged()) {
                    $display_price = true;
                } else {
                    $display_price = false;
                }
                $this->view->assign('display_price', $display_price);

                $sort_options = [];
                foreach ($this->data['sorts'] as $item => $text) {
                    $sort_options[$item] = $text;
                }
                $sorting = $this->html->buildSelectbox(
                    [
                       'name'    => 'sort',
                       'options' => $sort_options,
                       'value'   => $sort.'-'.$order,
                   ]
                );
                $this->view->assign('sorting', $sorting);
                $this->view->assign('url', $this->html->getSEOURL('product/category', '&path='.$request['path']));

                $pagination_url = $this->html->getSEOURL(
                    'product/category',
                    '&path='.$request['path'].'&sort='.$sorting_href.'&page={page}'.'&limit='.$limit,
                    '&encode'
                );

                $this->view->assign(
                    'pagination_bootstrap', $this->html->buildElement(
                        [
                            'type'       => 'Pagination',
                            'name'       => 'pagination',
                            'text'       => $this->language->get('text_pagination'),
                            'text_limit' => $this->language->get('text_per_page'),
                            'total'      => $product_total,
                            'page'       => $page,
                            'limit'      => $limit,
                            'url'        => $pagination_url,
                            'style'      => 'pagination',
                        ]
                    )
                );

                $this->view->assign('sort', $sort);
                $this->view->assign('order', $order);
                $this->view->setTemplate('pages/product/category.tpl');
            } else {
                $this->document->setTitle($category_info['name']);
                $this->document->setDescription($category_info['meta_description']);
                $this->view->assign('heading_title', $category_info['name']);
                $this->view->assign('button_continue', $this->language->get('button_continue'));
                $this->view->assign('continue', $this->html->getHomeURL());
                $this->view->assign('categories', []);
                $this->data['products'] = [];
                $this->view->setTemplate('pages/product/category.tpl');
            }

            $this->view->assign('text_error', $this->language->get('text_empty'));

            $this->data['review_status'] = $this->config->get('enable_reviews');
            $this->view->batchAssign($this->data);
        } else {
            $url = '';
            if (isset($request['sort'])) {
                $url .= '&sort='.$request['sort'];
            }

            if (isset($request['order'])) {
                $url .= '&order='.$request['order'];
            }

            if (isset($request['page'])) {
                $url .= '&page='.$request['page'];
            }

            if (isset($request['path'])) {
                $this->document->addBreadcrumb(
                    [
                       'href'      => $this->html->getSEOURL(
                           'product/category',
                           '&path='.$request['path'].$url,
                           '&encode'
                       ),
                       'text'      => $this->language->get('text_error'),
                       'separator' => $this->language->get('text_separator'),
                   ]
                );
            }

            $this->document->setTitle($this->language->get('text_error'));
            $this->view->assign('heading_title', $this->language->get('text_error'));
            $this->view->assign('text_error', $this->language->get('text_error'));
            $this->view->assign(
                'button_continue',
                $this->html->buildElement(
                    [
                        'type'  => 'button',
                        'name'  => 'continue_button',
                        'text'  => $this->language->get('button_continue'),
                        'style' => 'button',
                    ]
                )
            );
            $this->view->assign('continue', $this->html->getHomeURL());
            $this->view->setTemplate('pages/error/not_found.tpl');
        }

        $this->processTemplate();

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}
