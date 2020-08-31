<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2018 Belavier Commerce LLC

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
use abc\core\engine\Registry;
use abc\core\lib\APromotion;
use abc\core\engine\AResource;
use abc\models\catalog\Category;
use abc\models\catalog\Product;
use abc\models\storefront\ModelCatalogReview;
use abc\modules\traits\ProductListingTrait;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class ControllerPagesProductSearch
 *
 * @package abc\controllers\storefront
 */
class ControllerPagesProductSearch extends AController
{
    protected $category;
    protected $path;
    public $data = [];

    use ProductListingTrait;

    public function __construct(Registry $registry, $instance_id, $controller, $parent_controller = '')
    {
        parent::__construct($registry, $instance_id, $controller, $parent_controller);
        $this->fillSortsList();
    }

    public function main()
    {
        $this->loadModel('tool/seo_url');

        $request = $this->request->get;
        $this->path = explode(',', $request['category_id']);

        //is this an embed mode
        if ($this->config->get('embed_mode') == true) {
            $cart_rt = 'r/checkout/cart/embed';
        } else {
            $cart_rt = 'checkout/cart';
        }

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->resetBreadcrumbs();
        $this->document->addBreadcrumb([
            'href'      => $this->html->getHomeURL(),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);

        $url = '';

        if (isset($request['keyword'])) {
            $url .= '&keyword='.$request['keyword'];
        }

        if (isset($request['category_id'])) {
            $url .= '&category_id='.$request['category_id'];
        }

        if (isset($request['description'])) {
            $url .= '&description='.$request['description'];
        }

        if (isset($request['model'])) {
            $url .= '&model='.$request['model'];
        }

        if (isset($request['sort'])) {
            $url .= '&sort='.$request['sort'];
        }

        if (isset($request['order'])) {
            $url .= '&order='.$request['order'];
        }

        if (isset($request['page'])) {
            $url .= '&page='.$request['page'];
        }
        if (isset($request['limit'])) {
            $url .= '&limit='.$request['limit'];
        }

        $this->document->addBreadcrumb([
            'href'      => $this->html->getNonSecureURL('product/search', $url),
            'text'      => $this->language->get('heading_title'),
            'separator' => $this->language->get('text_separator'),
        ]);

        $page = isset($request['page']) ? $request['page'] : 1;

        $sorting_href = $request['sort'];
        if (!$sorting_href || !isset($this->data['sorts'][$request['sort']])) {
            $sorting_href = $this->config->get('config_product_default_sort_order');
        }

        list($sort, $order) = explode("-", $sorting_href);

        $this->data['keyword'] = $this->html->buildElement(
            [
                'type'  => 'input',
                'name'  => 'keyword',
                'value' => $request['keyword'],
            ]
        );

        $options = [0 => $this->language->get('text_category')];
        Category::setCurrentLanguageID(Registry::language()->getLanguageID());
        $results = Category::getCategories(0, $this->config->get('store_id'));
        $options = $options + array_column($results, 'name', 'category_id');
        $this->data['category'] = $this->html->buildElement(
            [
                'type'    => 'selectbox',
                'name'    => 'category_id',
                'options' => $options,
                'value'   => $request['category_id'],
            ]);

        $this->data['description'] = $this->html->buildElement([
            'type'       => 'checkbox',
            'id'         => 'description',
            'name'       => 'description',
            'checked'    => (int)$request['description'],
            'value'      => 1,
            'label_text' => $this->language->get('entry_description'),
        ]);

        $this->data['model'] = $this->html->buildElement(
            [
                'type'       => 'checkbox',
                'id'         => 'model',
                'name'       => 'model',
                'checked'    => (bool)$request['model'],
                'value'      => 1,
                'label_text' => $this->language->get('entry_model'),
            ]);

        $this->data['submit'] = $this->html->buildElement([
            'type'  => 'button',
            'name'  => 'search_button',
            'text'  => $this->language->get('button_search'),
            'icon'  => 'fa fa-search',
            'style' => 'btn-default',
        ]);

        if (isset($request['keyword'])) {
            if (isset($request['category_id'])) {
                $category_id = explode(',', $request['category_id']);
                end($category_id);
                $category_id = current($category_id);
            } else {
                $category_id = '';
            }

            $limit = $this->config->get('config_catalog_limit');
            if (isset($request['limit']) && intval($request['limit']) > 0) {
                $limit = intval($request['limit']);
                if ($limit > 50) {
                    $limit = 50;
                }
            }

            /** @see Product::getProducts() $productsList */
            $productsList = Product::search(
                [
                    'with_final_price'    => true,
                    'with_discount_price' => true,
                    'with_special_price'  => true,
                    'with_rating'         => true,
                    'with_stock_info'     => true,
                    'with_option_count'   => true,
                    'filter'              => [
                        'keyword'     => $request['keyword'],
                        'category_id' => $category_id,
                        'model'       => $request['model'],
                        'description' => $request['description'],
                    ],
                    'sort'                => $sort,
                    'order'               => $order,
                    'start'               => ($page - 1) * $limit,
                    'limit'               => $limit,
                ]
            );
            $product_total = $productsList[0]['total_num_rows'];
            if ($product_total) {
                $url = '';
                if (isset($request['category_id'])) {
                    $url .= '&category_id='.$request['category_id'];
                }

                if (isset($request['description'])) {
                    $url .= '&description='.$request['description'];
                }

                if (isset($request['model'])) {
                    $url .= '&model='.$request['model'];
                }

                //if single result, redirect to the product
                if (count($productsList) == 1) {
                    abc_redirect(
                        $this->html->getSEOURL(
                            'product/product',
                            '&product_id='.$productsList->first()->product_id,
                            '&encode')
                    );
                }
                $products = [];
                if ($productsList) {
                    $product_ids = $productsList->pluck('product_id')->toArray();

                    //Format product data specific for confirmation page
                    $resource = new AResource('image');
                    $thumbnails = $resource->getMainThumbList(
                        'products',
                        $product_ids,
                        $this->config->get('config_image_product_width'),
                        $this->config->get('config_image_product_height')
                    );
                    /** @var Collection $listItem */
                    foreach ($productsList as $i => $listItem) {
                        $products[$i] = $listItem->toArray();
                        $thumbnail = $thumbnails[$listItem['product_id']];
                        $rating = $this->config->get('enable_reviews') ? $listItem['rating'] : false;
                        $special = false;
                        $discount = $listItem['discount_price'];

                        if ($discount) {
                            $price = $this->currency->format(
                                $this->tax->calculate(
                                    $discount,
                                    $listItem['tax_class_id'],
                                    $this->config->get('config_tax')
                                )
                            );
                        } else {
                            $price = $this->currency->format(
                                $this->tax->calculate(
                                    $listItem['price'],
                                    $listItem['tax_class_id'],
                                    $this->config->get('config_tax')
                                )
                            );
                            $special = $listItem['special_price'];
                            if ($special) {
                                $special =
                                    $this->currency->format(
                                        $this->tax->calculate(
                                            $special,
                                            $listItem['tax_class_id'],
                                            $this->config->get('config_tax')
                                        )
                                    );
                            }
                        }

                        $hasOptions = $listItem['option_count'];
                        if ($hasOptions) {
                            $addToCartUrl = $this->html->getSEOURL(
                                'product/product',
                                '&product_id='.$listItem['product_id'],
                                '&encode'
                            );
                        } else {
                            if ($this->config->get('config_cart_ajax')) {
                                $addToCartUrl = '#';
                            } else {
                                $addToCartUrl = $this->html->getSecureURL(
                                    $cart_rt,
                                    '&product_id='.$listItem['product_id'],
                                    '&encode'
                                );
                            }
                        }

                        //check for stock status, availability and config
                        $track_stock = false;
                        $in_stock = false;
                        $no_stock_text = $this->language->get('text_out_of_stock');
                        $stock_checkout = $listItem['stock_checkout'] === ''
                            ? $this->config->get('config_stock_checkout')
                            : $listItem['stock_checkout'];
                        $total_quantity = 0;
                        if ($listItem['subtract']) {
                            $track_stock = true;
                            $total_quantity = $listItem['quantity'];
                            //we have stock or out of stock checkout is allowed
                            if ($total_quantity > 0 || $stock_checkout) {
                                $in_stock = true;
                            }
                        }

                        $products[$i]['rating'] = $rating;
                        $products[$i]['stars'] = sprintf($this->language->get('text_stars'), $rating);
                        $products[$i]['thumb'] = $thumbnail;
                        $products[$i]['price'] = $price;
                        $products[$i]['raw_price'] = $listItem['price'];
                        $products[$i]['options'] = $hasOptions;
                        $products[$i]['special'] = $special;
                        $products[$i]['href'] = $this->html->getSEOURL(
                            'product/product',
                            '&keyword='.$request['keyword']
                            .$url
                            .'&product_id='.$listItem['product_id'],
                            '&encode');
                        $products[$i]['add'] = $addToCartUrl;
                        $products[$i]['description'] = html_entity_decode(
                            $listItem['description'],
                            ENT_QUOTES,
                            ABC::env('APP_CHARSET')
                        );
                        $products[$i]['track_stock'] = $track_stock;
                        $products[$i]['in_stock'] = $in_stock;
                        $products[$i]['no_stock_text'] = $no_stock_text;
                        $products[$i]['total_quantity'] = $total_quantity;
                    }
                }

                $this->data['products'] = $products;

                if ($this->config->get('config_customer_price')) {
                    $display_price = true;
                } elseif ($this->customer->isLogged()) {
                    $display_price = true;
                } else {
                    $display_price = false;
                }
                $this->data['display_price'] = $display_price;

                $url = '';
                if (isset($request['keyword'])) {
                    $url .= '&keyword='.$request['keyword'];
                }

                if (isset($request['category_id'])) {
                    $url .= '&category_id='.$request['category_id'];
                }

                if (isset($request['description'])) {
                    $url .= '&description='.$request['description'];
                }

                if (isset($request['model'])) {
                    $url .= '&model='.$request['model'];
                }

                if (isset($request['page'])) {
                    $url .= '&page='.$request['page'];
                }
                if (isset($request['limit'])) {
                    $url .= '&limit='.$request['limit'];
                }

                $sort_options = [];

                foreach ($this->data['sorts'] as $value => &$text) {
                    $sort_options[$value] = $text;
                    list($s, $o) = explode('-', $value);
                    $text = [
                        'text'  => $text,
                        'value' => $value,
                        'href'  => $this->html->getURL('product/search', $url.'&sort='.$s.'&order='.$o, '&encode'),
                    ];
                }

                $sorting = $this->html->buildElement([
                    'type'    => 'selectbox',
                    'name'    => 'sort',
                    'options' => $sort_options,
                    'value'   => $sort.'-'.$order,
                ]);

                $this->data['sorting'] = $sorting;
                $url = '';
                if (isset($request['keyword'])) {
                    $url .= '&keyword='.$request['keyword'];
                }
                if (isset($request['category_id'])) {
                    $url .= '&category_id='.$request['category_id'];
                }

                if (isset($request['description'])) {
                    $url .= '&description='.$request['description'];
                }

                if (isset($request['model'])) {
                    $url .= '&model='.$request['model'];
                }

                if (isset($request['sort'])) {
                    $url .= '&sort='.$request['sort'];
                }

                $url .= '&sort='.$sorting_href;
                $url .= '&limit='.$limit;

                $this->data['pagination_bootstrap'] = $this->html->buildElement([
                    'type'       => 'Pagination',
                    'name'       => 'pagination',
                    'text'       => $this->language->get('text_pagination'),
                    'text_limit' => $this->language->get('text_per_page'),
                    'total'      => $product_total,
                    'page'       => $page,
                    'limit'      => $limit,
                    'url'        => $this->html->getURL('product/search', $url.'&page={page}', '&encode'),
                    'style'      => 'pagination',
                ]);
                $this->data['sort'] = $sort;
                $this->data['order'] = $order;
                $this->data['limit'] = $limit;
            }
        }
        $this->data['review_status'] = $this->config->get('enable_reviews');

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/product/search.tpl');
        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function getCategories($parent_id, $level = 0)
    {
        $level++;
        $data = [];
        $cat_id = explode(',', $parent_id);
        end($cat_id);
        $results = Category::getCategories(current($cat_id));

        foreach ($results as $result) {
            if (in_array($result['category_id'], $this->path)) {
                $this->category = $result['category_id'];
            } else {
                $this->category = 0;
            }

            $data[] = [
                'category_id' => $parent_id.','.$result['category_id'],
                'name'        => str_repeat('&nbsp;&nbsp;&nbsp;', $level).$result['name'],
            ];
            $children = [];
            if ($this->category) {
                $children = $this->getCategories($parent_id.','.$result['category_id'], $level);
            }

            if ($children) {
                $data = array_merge($data, $children);
            }
            unset($children);
        }

        return $data;
    }
}
