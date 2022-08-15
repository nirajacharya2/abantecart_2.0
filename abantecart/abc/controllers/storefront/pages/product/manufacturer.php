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
use abc\models\catalog\Manufacturer;
use abc\models\storefront\ModelCatalogManufacturer;
use abc\modules\traits\ProductListingTrait;

/**
 * Class ControllerPagesProductManufacturer
 *
 * @package abc\controllers\storefront
 *
 * @property ModelCatalogManufacturer $model_catalog_manufacturer
 */
class ControllerPagesProductManufacturer extends AController
{
    use ProductListingTrait;

    public function __construct(Registry $registry, $instance_id, $controller, $parent_controller = '')
    {
        parent::__construct($registry, $instance_id, $controller, $parent_controller);
        $this->fillSortsList();
    }

    public function main()
    {

        $this->loadModel('catalog/manufacturer');
        $this->loadModel('catalog/product');
        $this->loadModel('tool/seo_url');
        $this->loadModel('tool/image');

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $request = $this->request->get;
        if ($this->config->get('embed_mode')) {
            $cart_rt = 'r/checkout/cart/embed';

            //load special headers
            $this->addChild('responses/embed/head', 'head');
            $this->addChild('responses/embed/footer', 'footer');
        } else {
            $cart_rt = 'checkout/cart';
        }

        $this->loadLanguage('product/manufacturer');

        if ($this->config->get('config_require_customer_login') && !$this->customer->isLogged()) {
            abc_redirect($this->html->getSecureURL('account/login'));
        }

        $this->document->resetBreadcrumbs();
        $this->document->addBreadcrumb([
            'href'      => $this->html->getHomeURL(),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);

        $manufacturer_id = $request['manufacturer_id'] ?? 0;

        $manufacturerInfo = (new Manufacturer())->getManufacturer($manufacturer_id);
        if ($manufacturerInfo) {
            $this->document->addBreadcrumb([
                'href'      => $this->html->getSEOURL(
                    'product/manufacturer',
                    '&manufacturer_id=' . $request['manufacturer_id'],
                    true
                ),
                'text'      => $manufacturerInfo['name'],
                'separator' => $this->language->get('text_separator'),
            ]);

            $this->document->setTitle($manufacturerInfo['name']);
            $this->data['heading_title'] = $manufacturerInfo['name'];
            $this->data['text_sort'] = $this->language->get('text_sort');

            $resource = new AResource('image');
            $thumbnail = $resource->getMainThumb('manufacturers',
                $manufacturerInfo['manufacturer_id'],
                $this->config->get('config_image_grid_width'),
                $this->config->get('config_image_grid_height')
            );
            if (!str_contains($thumbnail['thumb_url'], 'no_image')) {
                $this->view->assign('manufacturer_icon', $thumbnail['thumb_url']);
            }

            if ($manufacturerInfo['product_count']) {
                $page = $request['page'] ?? 1;
                if (isset($request['limit'])) {
                    $limit = (int)$request['limit'];
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

                $this->loadModel('catalog/review');
                $this->view->assign('button_add_to_cart', $this->language->get('button_add_to_cart'));
                $product_ids = $products = [];

                $products_result = $this->model_catalog_product->getProductsByManufacturerId($request['manufacturer_id'],
                    $sort,
                    $order,
                    ($page - 1) * $limit,
                    $limit);
                foreach ($products_result as $result) {
                    $product_ids[] = (int)$result['product_id'];
                }
                $products_info = $this->model_catalog_product->getProductsAllInfo($product_ids);
                $thumbnails = $resource->getMainThumbList('products',
                    $product_ids,
                    (int)$this->config->get('config_image_product_width'),
                    (int)$this->config->get('config_image_product_height')
                );
                $stock_info = $this->model_catalog_product->getProductsStockInfo($product_ids);

                foreach ($products_result as $result) {
                    $thumbnail = $thumbnails[$result['product_id']];
                    $rating = $products_info[$result['product_id']]['rating'];
                    $special = false;
                    $discount = $products_info[$result['product_id']]['discount'];
                    if ($discount) {
                        $price = $this->currency->format($this->tax->calculate($discount, $result['tax_class_id'],
                            $this->config->get('config_tax')));
                    } else {
                        $price =
                            $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'],
                                $this->config->get('config_tax')));
                        $special = $products_info[$result['product_id']]['special'];
                        if ($special) {
                            $special = $this->currency->format($this->tax->calculate($special, $result['tax_class_id'],
                                $this->config->get('config_tax')));
                        }
                    }

                    if ($products_info[$result['product_id']]['options']) {
                        $add = $this->html->getSEOURL(
                            'product/product',
                            '&product_id=' . $result['product_id'],
                            true
                        );
                    } else {
                        if ($this->config->get('config_cart_ajax')) {
                            $add = '#';
                        } else {
                            $add = $this->html->getSecureURL($cart_rt, '&product_id=' . $result['product_id'], '&encode');
                        }
                    }

                    //check for stock status, availability and config
                    $track_stock = false;
                    $in_stock = false;
                    $no_stock_text = $this->language->get('text_out_of_stock');
                    $stock_checkout = $result['stock_checkout'] === ''
                        ? $this->config->get('config_stock_checkout')
                        : $result['stock_checkout'];
                    $total_quantity = 0;
                    if ($stock_info[$result['product_id']]['subtract']) {
                        $track_stock = true;
                        $total_quantity = $stock_info[$result['product_id']]['quantity'];
                        //we have stock or out of stock checkout is allowed
                        if ($total_quantity > 0 || $stock_checkout) {
                            $in_stock = true;
                        }
                    }

                    $products[] = [
                        'product_id'     => $result['product_id'],
                        'name'           => $result['name'],
                        'blurb'          => $result['blurb'],
                        'model'          => $result['model'],
                        'rating'         => $rating,
                        'stars'          => sprintf($this->language->get('text_stars'), $rating),
                        'thumb'          => $thumbnail,
                        'price'          => $price,
                        'raw_price'      => $result['price'],
                        'call_to_order'  => $result['call_to_order'],
                        'options'        => $products_info[$result['product_id']]['options'],
                        'special'        => $special,
                        'href'           => $this->html->getSEOURL('product/product',
                            '&manufacturer_id='.$request['manufacturer_id'].'&product_id='.$result['product_id'],
                            '&encode'),
                        'add'            => $add,
                        'description'    => html_entity_decode($result['description'], ENT_QUOTES,
                            ABC::env('APP_CHARSET')),
                        'track_stock'    => $track_stock,
                        'in_stock'       => $in_stock,
                        'no_stock_text'  => $no_stock_text,
                        'total_quantity' => $total_quantity,
                        'tax_class_id'   => $result['tax_class_id'],
                    ];
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
                        'value'   => $sorting_href,
                    ]);
                $this->view->assign('sorting', $sorting);
                $this->view->assign('url',
                    $this->html->getSEOURL('product/manufacturer', '&manufacturer_id='.$request['manufacturer_id']));

                $pagination_url = $this->html->getSEOURL('product/manufacturer',
                    '&manufacturer_id='.$request['manufacturer_id'].'&sort='.$sorting_href.'&page={page}'.'&limit='
                    .$limit, '&encode');

                $this->view->assign('pagination_bootstrap', $this->html->buildElement(
                    [
                        'type'       => 'Pagination',
                        'name'       => 'pagination',
                        'text'       => $this->language->get('text_pagination'),
                        'text_limit' => $this->language->get('text_per_page'),
                        'total'      => $manufacturerInfo['product_count'],
                        'page'       => $page,
                        'limit'      => $limit,
                        'url'        => $pagination_url,
                        'style'      => 'pagination',
                    ])
                );
                $this->view->assign('sort', $sort);
                $this->view->assign('order', $order);
                $this->view->setTemplate('pages/product/manufacturer.tpl');
            } else {
                $this->document->setTitle($manufacturerInfo['name']);
                $this->view->assign('heading_title', $manufacturerInfo['name']);
                $this->view->assign('text_error', $this->language->get('text_empty'));
                $continue = $this->html->buildElement(
                    [
                        'type'  => 'button',
                        'name'  => 'continue_button',
                        'text'  => $this->language->get('button_continue'),
                        'style' => 'button',
                    ]);
                $this->view->assign('button_continue', $continue);
                $this->view->assign('continue', $this->html->getHomeURL());
                $this->view->setTemplate('pages/error/not_found.tpl');
            }
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

            $this->document->addBreadcrumb([
                'href'      => $this->html->getSEOURL('product/manufacturer', '&manufacturer_id='.$manufacturer_id.$url,
                    '&encode'),
                'text'      => $this->language->get('text_error'),
                'separator' => $this->language->get('text_separator'),
            ]);

            $this->document->setTitle($this->language->get('text_error'));

            $this->view->assign('heading_title', $this->language->get('text_error'));
            $this->view->assign('text_error', $this->language->get('text_error'));
            $continue = $this->html->buildElement([
                'type'  => 'button',
                'name'  => 'continue_button',
                'text'  => $this->language->get('button_continue'),
                'style' => 'button',
            ]);
            $this->view->assign('button_continue', $continue);
            $this->view->assign('continue', $this->html->getHomeURL());

            $this->view->setTemplate('pages/error/not_found.tpl');
        }

        $this->data['review_status'] = $this->config->get('enable_reviews');

        $this->view->batchAssign($this->data);
        $this->processTemplate();

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}
