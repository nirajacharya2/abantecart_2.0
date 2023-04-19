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
use abc\core\lib\AException;
use abc\models\AbcCollection;
use abc\models\catalog\Category;
use abc\models\catalog\CategoryDescription;
use abc\models\catalog\Product;
use abc\modules\traits\ProductListingTrait;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

/**
 * Class ControllerPagesProductCategory
 *
 * @package abc\controllers\storefront
 */
class ControllerPagesProductCategory extends AController
{
    public $data = ['sorts' => []];

    use ProductListingTrait;

    public function main()
    {
        $request =& $this->request->get;

        if ($this->config->get('config_require_customer_login') && !$this->customer->isLogged()) {
            abc_redirect($this->html->getSecureURL('account/login'));
        }

        if (!$this->data['sorts']) {
            $this->fillSortsList();
        }

        $this->loadLanguage('product/category');
        $this->document->resetBreadcrumbs();
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getHomeURL(),
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ]
        );

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
                ->get()
                ?->toArray();
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
        } elseif ($this->config->get('embed_mode') || isset($request['path'])) {
            //Display Top category when embed mode or have PATH parameter
            $category_info['name'] = $this->language->get('text_top_category');
        }

        $this->data['page'] = $page = $request['page'] ?? 1;

        if (isset($request['limit'])) {
            $limit = (int)$request['limit'];
            $limit = min($limit, 50);
        } else {
            $limit = $this->config->get('config_catalog_limit');
        }
        $this->data['limit'] = $limit;


        $this->data['sorting_pair'] = $request['sort'];
        if (!$this->data['sorting_pair'] || !isset($this->data['sorts'][$request['sort']])) {
            $this->data['sorting_pair'] = $this->config->get('config_product_default_sort_order');
        }
        list($sort, $order) = explode("-", $this->data['sorting_pair']);
        $this->data['sort'] = $sort;
        $this->data['order'] = $order;

        $this->data['products_search_parameters'] = [
            'filter'              => [
                'category_id' => $category_id,
                'only_enabled' => true
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
        ];

        if (!$category_info) {
            $this->processNotFound();
            return;
        }

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->data['category_info'] = $category_info;
        $this->document->setTitle($category_info['name']);
        $this->document->setKeywords($category_info['meta_keywords']);
        $this->document->setDescription($category_info['meta_description']);

        $this->data['heading_title'] = $category_info['name'];
        $this->data['description'] = html_entity_decode(
            $category_info['description'],
            ENT_QUOTES,
            ABC::env('APP_CHARSET')
        );

        $this->data['text_sort'] = $this->language->get('text_sort');

        $urlParams = '&sort=' . $this->data['sorting_pair'];
        if (isset($request['order'])) {
            $urlParams .= '&order=' . $request['order'];
        }

        $categories = Category::getCategories($category_id);
        $productsList = Product::search($this->data['products_search_parameters']);

        if ($categories || $productsList) {
            if ($categories) {
                $this->prepareCategoryList($categories, $urlParams);
            }
            if ($productsList) {
                $this->prepareProductList($productsList);
            }
        } else {
            $this->document->setTitle($category_info['name']);
            $this->document->setDescription($category_info['meta_description']);
            $this->data['heading_title'] = $category_info['name'];
            $this->data['button_continue'] = $this->language->get('button_continue');
            $this->data['continue'] = $this->html->getHomeURL();
            $this->data['categories'] = $this->data['products'] = [];
        }

        $this->data['text_error'] = $this->language->get('text_empty');
        $this->data['review_status'] = $this->config->get('enable_reviews');
        $this->view->batchAssign($this->data);

        $this->view->setTemplate('pages/product/category.tpl');
        $this->processTemplate();

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function prepareCategoryList($categories, $urlParams)
    {
        $category_ids = array_map('intval', array_column($categories, 'category_id'));

        //get thumbnails by one pass
        $resource = new AResource('image');
        $thumbnails = $resource->getMainThumbList(
            'categories',
            $category_ids,
            $this->config->get('config_image_category_width'),
            $this->config->get('config_image_category_height')
        );
        $this->data['categories'] = [];
        foreach ($categories as $result) {
            $thumbnail = $thumbnails[$result['category_id']];
            $this->data['categories'][] = [
                'name'  => $result['name'],
                'href'  => $this->html->getSEOURL(
                    'product/category',
                    '&path=' . $this->request->get['path'] . '_' . $result['category_id'] . $urlParams,
                    '&encode'
                ),
                'thumb' => $thumbnail,
            ];
        }
    }

    /**
     * @param AbcCollection $productsList
     * @return void
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws AException
     */
    protected function prepareProductList(AbcCollection $productsList)
    {
        $this->data['button_add_to_cart'] = $this->language->get('button_add_to_cart');
        $this->processList($productsList);

        if ($this->config->get('config_customer_price') || $this->customer->isLogged()) {
            $this->data['display_price'] = true;
        } else {
            $this->data['display_price'] = false;
        }

        $this->data['sorting'] = $this->html->buildSelectbox(
            [
                'name'    => 'sort',
                'options' => $this->data['sorts'],
                'value'   => $this->data['sort'] . '-' . $this->data['order'],
            ]
        );
        $this->data['url'] = $this->html->getSEOURL('product/category', '&path=' . $this->request->get['path']);

        $this->data['pagination_bootstrap'] = $this->html->buildElement(
            [
                'type'       => 'Pagination',
                'name'       => 'pagination',
                'text'       => $this->language->get('text_pagination'),
                'text_limit' => $this->language->get('text_per_page'),
                'total'      => $productsList::getFoundRowsCount(),
                'page'       => $this->data['page'],
                'limit'      => $this->data['limit'],
                'url'        => $this->html->getSEOURL(
                    'product/category',
                    '&path=' . $this->request->get['path']
                    . '&sort=' . $this->data['sorting_pair']
                    . '&page={page}'
                    . '&limit=' . $this->data['limit'],
                    '&encode'
                ),
                'style'      => 'pagination',
            ]
        );
    }

    protected function processNotFound()
    {
        $urlParams = '';
        $request = $this->request->get;
        if (isset($request['sort'])) {
            $urlParams .= '&sort=' . $request['sort'];
        }

        if (isset($request['order'])) {
            $urlParams .= '&order=' . $request['order'];
        }

        if (isset($request['page'])) {
            $urlParams .= '&page=' . $request['page'];
        }

        if (isset($request['path'])) {
            $this->document->addBreadcrumb(
                [
                    'href'      => $this->html->getSEOURL(
                        'product/category',
                        '&path=' . $request['path'] . $urlParams,
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
        $this->processTemplate();
    }
}
