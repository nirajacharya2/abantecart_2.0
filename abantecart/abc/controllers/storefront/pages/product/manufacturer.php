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
            //load special headers
            $this->addChild('responses/embed/head', 'head');
            $this->addChild('responses/embed/footer', 'footer');
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

                $this->processList($results);


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
                        'total'      => $results::getFoundRowsCount(),
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