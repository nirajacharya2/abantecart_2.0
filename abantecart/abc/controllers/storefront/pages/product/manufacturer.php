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

use abc\core\engine\AController;
use abc\core\engine\AResource;
use abc\core\engine\Registry;
use abc\models\catalog\Manufacturer;
use abc\models\catalog\Product;
use abc\modules\traits\ProductListingTrait;
use Illuminate\Support\Collection;
use stdClass;

/**
 * Class ControllerPagesProductManufacturer
 *
 * @package abc\controllers\storefront
 *
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

        $manufacturerId = $request['manufacturer_id'] ?? 0;

        $manufacturerInfo = (new Manufacturer())->getManufacturer($manufacturerId);
        if ($manufacturerInfo) {
            $this->document->addBreadcrumb(
                [
                    'href'      => $this->html->getSEOURL(
                        'product/manufacturer',
                        '&manufacturer_id=' . $request['manufacturer_id'],
                        true
                    ),
                    'text'      => $manufacturerInfo['name'],
                    'separator' => $this->language->get('text_separator'),
                ]
            );

            $this->document->setTitle($manufacturerInfo['name']);
            $this->data['heading_title'] = $manufacturerInfo['name'];
            $this->data['text_sort'] = $this->language->get('text_sort');

            $resource = new AResource('image');
            $thumbnail = $resource->getMainThumb(
                'manufacturers',
                $manufacturerInfo['manufacturer_id'],
                $this->config->get('config_image_grid_width'),
                $this->config->get('config_image_grid_height')
            );
            if (!str_contains($thumbnail['thumb_url'], 'no_image')) {
                $this->data['manufacturer_icon'] = $thumbnail['thumb_url'];
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
                    $sort = 'pd.' . $sort;
                } elseif (in_array($sort, ['sort_order', 'price'])) {
                    $sort = 'p.' . $sort;
                }

                $this->data['button_add_to_cart'] = $this->language->get('button_add_to_cart');

                $results = Product::search(
                    [
                        'filter'              => [
                            'manufacturer_id' => $manufacturerId
                        ],
                        'with_final_price'    => true,
                        'with_discount_price' => true,
                        'with_special_price'  => true,
                        'with_rating'         => true,
                        'with_stock_info'     => true,
                        'with_option_count'   => true,
                        'start'               => ($page - 1) * $limit,
                        'limit'               => $limit,
                        'sort'                => $sort,
                        'order'               => $order,
                    ]
                );
                $productIds = $results->pluck('product_id')->toArray();

                //get thumbnails by one pass
                $resource = new AResource('image');
                $thumbnails = $resource->getMainThumbList(
                    'products',
                    $productIds,
                    $this->config->get('config_image_product_width'),
                    $this->config->get('config_image_product_height')
                );

                if ($this->customer->isLogged() || $this->customer->isUnauthCustomer()) {
                    $this->data['is_customer'] = true;
                    $wishlist = $this->customer->getWishList();
                } else {
                    $wishlist = [];
                }

                /** @var stdClass|Collection|Product $result */
                foreach ($results as $i => $result) {
                    $thumbnail = $thumbnails[$result->product_id];
                    $rating = $result->rating;
                    $special = false;
                    $discount = $result->discount_price;

                    if ($discount) {
                        $price = $this->currency->format(
                            $this->tax->calculate(
                                $discount,
                                $result->tax_class_id,
                                $this->config->get('config_tax')
                            )
                        );
                    } else {
                        $price = $this->currency->format(
                            $this->tax->calculate(
                                $result['price'],
                                $result->tax_class_id,
                                $this->config->get('config_tax')
                            )
                        );
                        $special = $result['special_price'];
                        if ($special) {
                            $special = $this->currency->format(
                                $this->tax->calculate(
                                    $special,
                                    $result->tax_class_id,
                                    $this->config->get('config_tax')
                                )
                            );
                        }
                    }

                    $hasOptions = $result->option_count;

                    if ($hasOptions) {
                        $add = $this->html->getSEOURL(
                            'product/product',
                            '&product_id=' . $result['product_id'],
                            '&encode'
                        );
                    } else {
                        if ($this->config->get('config_cart_ajax')) {
                            $add = '#';
                        } else {
                            $add = $this->html->getSecureURL(
                                $cart_rt,
                                '&product_id=' . $result['product_id'],
                                '&encode'
                            );
                        }
                    }
                    //check for stock status, availability and config
                    $track_stock = false;
                    $in_stock = false;
                    $no_stock_text = $this->language->get('text_out_of_stock');
                    $total_quantity = 0;
                    $stock_checkout = $result->stock_checkout === ''
                        ? $this->config->get('config_stock_checkout')
                        : $result->stock_checkout;
                    if ($result->subtract) {
                        $track_stock = true;
                        $total_quantity = $result->quantity;
                        //we have stock or out of stock checkout is allowed
                        if ($total_quantity > 0 || $stock_checkout) {
                            $in_stock = true;
                        }
                    }

                    $in_wishlist = false;
                    if ($wishlist && $wishlist[$result->product_id]) {
                        $in_wishlist = true;
                    }

                    $catalog_mode = false;
                    if ($result->product_type_id) {
                        $prodTypeSettings = Product::getProductTypeSettings((int)$result->product_id);

                        if ($prodTypeSettings
                            && is_array($prodTypeSettings)
                            && isset($prodTypeSettings['catalog_mode'])
                        ) {
                            $catalog_mode = (bool)$prodTypeSettings['catalog_mode'];
                        }
                    }


                    $this->data['products'][$i] = $result->toArray();
                    $this->data['products'][$i]['stars'] = sprintf($this->language->get('text_stars'), $rating);
                    $this->data['products'][$i]['price'] = $price;
                    $this->data['products'][$i]['options'] = $hasOptions;
                    $this->data['products'][$i]['special'] = $special;
                    $this->data['products'][$i]['thumb'] = $thumbnail;
                    $this->data['products'][$i]['href'] = $this->html->getSEOURL(
                        'product/product',
                        '&product_id=' . $result->product_id,
                        '&encode'
                    );
                    $this->data['products'][$i]['add'] = $add;
                    $this->data['products'][$i]['track_stock'] = $track_stock;
                    $this->data['products'][$i]['in_stock'] = $in_stock;
                    $this->data['products'][$i]['no_stock_text'] = $no_stock_text;
                    $this->data['products'][$i]['total_quantity'] = $total_quantity;
                    $this->data['products'][$i]['in_wishlist'] = $in_wishlist;
                    $this->data['products'][$i]['product_wishlist_add_url'] = $this->html->getURL(
                        'product/wishlist/add',
                        '&product_id=' . $result->product_id
                    );
                    $this->data['products'][$i]['product_wishlist_remove_url'] = $this->html->getURL(
                        'product/wishlist/remove',
                        '&product_id=' . $result->product_id
                    );
                    $this->data['products'][$i]['catalog_mode'] = $catalog_mode;
                }

                if ($this->config->get('config_customer_price')) {
                    $display_price = true;
                } elseif ($this->customer->isLogged()) {
                    $display_price = true;
                } else {
                    $display_price = false;
                }
                $this->data['display_price'] = $display_price;

                $sorting = $this->html->buildSelectbox(
                    [
                        'name'    => 'sort',
                        'options' => $this->data['sorts'],
                        'value'   => $sorting_href,
                    ]
                );

                $this->data['sorting'] = $sorting;
                $this->data['url'] = $this->html->getSEOURL(
                    'product/manufacturer',
                    '&manufacturer_id=' . $request['manufacturer_id']
                );

                $pagination_url = $this->html->getSEOURL(
                    'product/manufacturer',
                    '&manufacturer_id=' . $request['manufacturer_id']
                    . '&sort=' . $sorting_href
                    . '&page={page}'
                    . '&limit=' . $limit,
                    '&encode'
                );

                $this->data['pagination_bootstrap'] = $this->html->buildElement(
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
                    ]
                );
                $this->data['sort'] = $sort;
                $this->data['order'] = $order;
                $this->view->setTemplate('pages/product/manufacturer.tpl');
            } else {
                $this->document->setTitle($manufacturerInfo['name']);
                $this->data['heading_title'] = $manufacturerInfo['name'];
                $this->data['text_error'] = $this->language->get('text_empty');
                $continue = $this->html->buildElement(
                    [
                        'type'  => 'button',
                        'name'  => 'continue_button',
                        'text'  => $this->language->get('button_continue'),
                        'style' => 'button',
                    ]
                );
                $this->data['button_continue'] = $continue;
                $this->data['continue'] = $this->html->getHomeURL();
                $this->view->setTemplate('pages/error/not_found.tpl');
            }
        } else {

            $url = $request['sort'] ? '&sort=' . $request['sort'] : '';
            $url .= $request['order'] ? '&order=' . $request['order'] : '';
            $url .= $request['page'] ? '&page=' . $request['page'] : '';

            $this->document->addBreadcrumb([
                'href'      => $this->html->getSEOURL(
                    'product/manufacturer',
                    '&manufacturer_id=' . $manufacturerId . $url,
                    '&encode'
                ),
                'text'      => $this->language->get('text_error'),
                'separator' => $this->language->get('text_separator'),
            ]);

            $this->document->setTitle($this->language->get('text_error'));

            $this->data['heading_title'] = $this->language->get('text_error');
            $this->data['text_error'] = $this->language->get('text_error');
            $continue = $this->html->buildElement(
                [
                    'type'  => 'button',
                    'name'  => 'continue_button',
                    'text'  => $this->language->get('button_continue'),
                    'style' => 'button',
                ]
            );
            $this->data['button_continue'] = $continue;
            $this->data['continue'] = $this->html->getHomeURL();

            $this->view->setTemplate('pages/error/not_found.tpl');
        }

        $this->data['review_status'] = $this->config->get('enable_reviews');

        $this->view->batchAssign($this->data);
        $this->processTemplate();

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}