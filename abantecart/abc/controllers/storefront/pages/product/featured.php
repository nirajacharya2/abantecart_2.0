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

namespace abc\controllers\storefront;

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\engine\Registry;
use abc\core\lib\APromotion;
use abc\core\engine\AResource;
use abc\models\catalog\Product;
use abc\modules\traits\ProductListingTrait;

/**
 * Class ControllerPagesProductSpecial
 *
 * @package abc\controllers\storefront
 * @property \abc\models\storefront\ModelCatalogReview $model_catalog_review
 */
class ControllerPagesProductFeatured extends AController
{
    use ProductListingTrait;

    public function __construct(Registry $registry, $instance_id, $controller, $parent_controller = '')
    {
        parent::__construct($registry, $instance_id, $controller, $parent_controller);
        $this->fillSortsList();
    }

    public function main()
    {
        $request = $this->request->get;
        $page = $request['page'] ?? 1;
        $sorting_href = $request['sort'];
        if (!$sorting_href || !isset($this->data['sorts'][$request['sort']])) {
            $sorting_href = $this->config->get('config_product_default_sort_order');
        }
        list($sort, $order) = explode("-", $sorting_href);

        if (isset($request['limit'])) {
            $limit = (int)$request['limit'];
            $limit = min($limit, 50);
        } else {
            $limit = $this->config->get('config_catalog_limit');
        }

        $this->data['search_parameters'] = [
            'start'       => ($page - 1) * $limit,
            'limit'       => $limit,
            'language_id' => $this->language->getLanguageID(),
            'sort'        => $sort,
            'order'       => $order
        ];

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->data['page'] = $page;
        $this->data['limit'] = $limit;
        $this->data['sort'] = $sort;
        $this->data['order'] = $order;

        $this->loadLanguage('product/featured');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->resetBreadcrumbs();
        $this->document->addBreadcrumb([
            'href'      => $this->html->getHomeURL(),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);


        $url = '';
        if (isset($request['page'])) {
            $url .= '&page=' . $request['page'];
        }

        $this->document->addBreadcrumb([
            'href'      => $this->html->getNonSecureURL('product/featured', $url),
            'text'      => $this->language->get('heading_title'),
            'separator' => $this->language->get('text_separator'),
        ]);

        $results = Product::getFeaturedProducts($this->data['search_parameters']);
        $productTotal = $results->total;

        if ($productTotal) {
            $this->data['button_add_to_cart'] = $this->language->get('button_add_to_cart');
            $this->processList($results);

            if ($this->config->get('config_customer_price')) {
                $display_price = true;
            } elseif ($this->customer->isLogged()) {
                $display_price = true;
            } else {
                $display_price = false;
            }
            $this->data['display_price'] = $display_price;

            $sorting = $this->html->buildElement(
                [
                    'type'    => 'selectbox',
                    'name'    => 'sort',
                    'options' => $this->data['sorts'],
                    'value'   => $this->data['sort'] . '-' . $this->data['order'],
                ]
            );

            $this->view->assign('sorting', $sorting);
            $this->view->assign('url', $this->html->getURL('product/featured'));

            $pagination_url = $this->html->getURL(
                'product/featured',
                '&sort='.$sorting_href.'&page={page}'.'&limit='.$limit,
                '&encode'
            );

            $this->data['pagination_bootstrap'] = $this->html->buildElement(
                [
                    'type'       => 'Pagination',
                    'name'       => 'pagination',
                    'text'       => $this->language->get('text_pagination'),
                    'text_limit' => $this->language->get('text_per_page'),
                    'total'      => $productTotal,
                    'page'       => $this->data['page'],
                    'limit'      => $this->data['limit'],
                    'url'        => $pagination_url,
                    'style'      => 'pagination',
                ]
            );

            $this->data['review_status'] = $this->config->get('enable_reviews');
            $this->view->batchAssign($this->data);
            $this->view->setTemplate('pages/product/featured.tpl');
        } else {
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
        $this->processTemplate();

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}
