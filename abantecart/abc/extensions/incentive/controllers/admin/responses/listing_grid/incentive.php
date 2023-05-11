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

namespace abc\controllers\admin;

use abc\core\engine\AController;
use abc\core\engine\AResource;
use abc\core\lib\AError;
use abc\core\lib\AException;
use abc\core\lib\AJson;
use abc\extensions\incentive\models\Incentive;
use abc\extensions\incentive\models\IncentiveDescription;
use abc\models\catalog\Product;
use H;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;
use stdClass;

class ControllerResponsesListingGridIncentive extends AController
{
    public function main()
    {
        $this->loadLanguage('incentive/incentive');
        $page = (int)$this->request->post['page'] ?: 1;
        $limit = $this->request->post['rows'];
        $sort = $this->request->post['sidx'];
        $order = $this->request->post['sord'];

        if (isset($this->request->post['_search']) && $this->request->post['_search'] == 'true') {
            $searchData = AJson::decode(htmlspecialchars_decode($this->request->post['filters']), true);
            $allowedFields = array_merge(['name'], (array)$this->data['allowed_fields']);
            foreach ($searchData['rules'] as $rule) {
                if (!in_array($rule['field'], $allowedFields)) {
                    continue;
                }
                $filter_data[$rule['field']] = $rule['data'];
            }
        }

        $this->data['incentive_search_parameters'] = [
            'filter'      => [
                'keyword' => $filter_data['name'],
            ],
            'language_id' => $this->language->getContentLanguageID(),
            'start'       => ($page - 1) * $limit,
            'limit'       => $limit,
            'sort'        => $sort,
            'order'       => $order
        ];
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $results = Incentive::getIncentives($this->data['incentive_search_parameters']);
        $total = $results::getFoundRowsCount();
        $results = $results->toArray();
        $total_pages = $total > 0 ? ceil($total / $limit) : 0;

        $response = new stdClass();
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $total;
        $response->userdata = (object)[''];

        $results = $results ?: [];

        foreach ($results as $result) {
            $dateRange = H::dateISO2Display($result['start_date'], $this->language->get('date_format_short'))
                . ' - '
                . ($result['end_date']
                    ? H::dateISO2Display($result['end_date'], $this->language->get('date_format_short'))
                    : $this->language->get('incentive_no_expiration')
                );


            $response->rows[] = [
                'id'   => $result['incentive_id'],
                'cell' => [
                    $result['incentive_id'],
                    $result['name'],
                    $this->html->buildInput(
                        [
                            'name'  => 'priority[' . $result['incentive_id'] . ']',
                            'value' => $result['priority'],
                        ]
                    ),
                    $dateRange,
                    $result['conditions']['condition_type'],
                    $this->html->buildCheckbox(
                        [
                            'name'  => 'status[' . $result['incentive_id'] . ']',
                            'value' => $result['status'],
                            'style' => 'btn_switch',
                        ]
                    ),
                ]
            ];
        }
        $this->data['output'] = $response;
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->response->setOutput(AJson::encode($this->data['output']));
    }

    /**
     * Lookup result function for discount products list
     * @return void
     * @throws AException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function discount_products()
    {
        $products_data = [];

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (isset($this->request->post['term'])) {
            $products = Product::getProducts(
                [
                    'filter' => [
                        'only_enabled'              => true,
                        'keyword'                   => $this->request->post['term'],
                        'keyword_search_parameters' => [
                            'match'     => 'all',
                            'search_by' => [
                                'name',
                                'sku',
                                'model'
                            ]
                        ]
                    ],
                    'limit'  => 40
                ]
            );

            $resource = new AResource('image');
            $thumbnails = $resource->getMainThumbList(
                'products',
                $products?->pluck('product_id')->toArray(),
                (int)$this->config->get('config_image_grid_width'),
                (int)$this->config->get('config_image_grid_height'),
                true,
                array_column($products->toArray(), 'name', 'product_id')
            );

            foreach ($products as $pData) {
                $id = $pData['product_id'];
                if ($this->request->get['currency_code']) {
                    $price = round(
                        $this->currency->convert(
                            $pData['price'],
                            $this->config->get('config_currency'),
                            $this->request->get['currency_code']
                        ), 2
                    );
                } else {
                    $price = $pData['price'];
                }

                $textPrice = $this->currency->format(
                    $price,
                    ($this->request->get['currency_code'] ?: $this->config->get('config_currency'))
                );

                $products_data[] = [
                    'image'      => $thumbnails[$id]['thumb_html']
                        . trim(
                            $this->html->buildInput(
                                [
                                    'name'        => 'bonuses[' . $this->request->get['idx'] . '][products][quantity][' . $id . ']',
                                    'value'       => $pData['minimum'] ?: 1,
                                    'style'       => 'small-field',
                                    'placeholder' => 'Quantity',
                                ]
                            )
                        ),
                    'id'         => $id,
                    'name'       => $pData['name'] . ' - ' . $textPrice,
                    'price'      => $price,
                    'meta'       => $pData['model'],
                    'sort_order' => (int)$pData['sort_order'],
                ];
            }
        }

        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode($products_data));
    }

    /**
     * update only one field
     *
     * @return void
     * @throws AException|ReflectionException|InvalidArgumentException
     */
    public function update_field()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('incentive/incentive');
        if (!$this->user->canModify('listing_grid/incentive')) {
            $error = new AError('');
            return $error->toJSONResponse(
                'NO_PERMISSIONS_403',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'incentive/incentive'),
                    'reset_value' => true,
                ]
            );
        }

        if (isset($this->request->get['incentive_id'])) {
            //request sent from edit form. ID in url
            Incentive::editIncentive($this->request->get['incentive_id'], $this->request->post);
        } else {
            //request sent from jGrid. ID is key of array
            foreach ($this->request->post as $field => $value) {
                foreach ($value as $k => $v) {
                    Incentive::editIncentive($k, [$field => $v]);
                }
            }
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function update()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('incentive/incentive');
        if (!$this->user->canModify('listing_grid/incentive')) {
            $error = new AError('');
            $error->toJSONResponse(
                'NO_PERMISSIONS_403',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'listing_grid/incentive'),
                    'reset_value' => true,
                ]
            );
            return;
        }

        $ids = array_map('intval', array_unique(explode(',', $this->request->post['id'])));
        switch ($this->request->post['oper']) {
            case 'del':
                if ($ids) {
                    Incentive::whereIn('incentive_id', $ids)->delete();
                    IncentiveDescription::whereIn('incentive_id', $ids)->delete();
                }
                break;
            case 'save':
                if ($ids) {
                    $fields = array_keys($this->request->post);
                    foreach ($ids as $id) {
                        $upd = [];
                        foreach ($fields as $key) {
                            if (isset($this->request->post[$key][$id])) {
                                $upd[$key] = $this->request->post[$key][$id];
                            }
                        }
                        Incentive::editIncentive(
                            $id,
                            $upd
                        );
                    }
                }
                break;

            default:
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}