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
use abc\core\lib\AFilter;
use abc\core\lib\AJson;
use abc\models\catalog\Category;
use H;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;
use stdClass;

/**
 * Class ControllerResponsesListingGridCategory
 *
 * @package abc\controllers\admin
 */
class ControllerResponsesListingGridCategory extends AController
{
    public function main()
    {
        $page = (int)$this->request->post['page'] ?: 1;
        $limit = $this->request->post['rows'];
        $sort = $this->request->post['sidx'];
        $order = $this->request->post['sord'];

        $this->data['search_parameters'] = [
            'filter'      => [
                'parent_id' => (int)$this->request->get['parent_id']
            ],
            'language_id' => $this->language->getContentLanguageID(),
            'start'       => ($page - 1) * $limit,
            'limit'       => $limit,
            'sort'        => $sort,
            'order'       => $order
        ];

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('catalog/category');
        $this->loadModel('catalog/product');
        $this->loadModel('tool/image');

        //Prepare filter config
        $grid_filter_params = array_merge(['keyword'], (array)$this->data['grid_filter_params']);
        $filter = new AFilter(['method' => 'post', 'grid_filter_params' => $grid_filter_params]);
        if (isset($this->request->post['_search']) && $this->request->post['_search'] == 'true') {
            $searchData = AJson::decode(htmlspecialchars_decode($this->request->post['filters']), true);
            $this->data['search_parameters']['filter']['search_operator'] = 'like';
            $allowedFields = array_merge(['keyword'], (array)$this->data['allowed_fields']);
            foreach ($searchData['rules'] as $rule) {
                if (!in_array($rule['field'], $allowedFields)) {
                    continue;
                }
                $this->data['search_parameters']['filter'][$rule['field']] = $rule['data'];
                unset($this->data['search_parameters']['filter']['parent_id']);
            }
        }

        $new_level = 0;
        //get all leave categories
        $leaf_nodes = Category::getLeafCategories();
        if ($this->request->post['nodeid']) {
            //reset filter to get only parent category
            $this->data['search_parameters'] = [];
            $this->data['search_parameters']['filter']['parent_id'] = (int)$this->request->post['nodeid'];
            $new_level = (int)$this->request->post["n_level"] + 1;
        }

        $results = Category::getCategoriesData($this->data['search_parameters']);
        //push result into public scope to get access from extensions
        $this->data['results'] = $results;

        $total = $results::getFoundRowsCount();
        $results = $results->toArray();

        $response = new stdClass();
        $response->page = $filter->getParam('page');
        $response->total = $filter->calcTotalPages($total);
        $response->records = $total;
        $response->userdata = new stdClass();

        //build thumbnails list
        $category_ids = array_column($results, 'category_id');
        $resource = new AResource('image');

        $thumbnails = $resource->getMainThumbList(
            'categories',
            $category_ids,
            $this->config->get('config_image_grid_width'),
            $this->config->get('config_image_grid_height')
        );

        $i = 0;
        $language_id = $this->language->getContentLanguageID();
        $title = $this->language->get('text_view') . ' ' . $this->language->get('tab_product');
        foreach ($results as $result) {
            $thumbnail = $thumbnails[$result['category_id']];
            $response->rows[$i]['id'] = $result['category_id'];
            if (!$result['total_products_count']) {
                $products_count = 0;
            } else {
                $text = $result['total_products_count'] != $result['active_products_count']
                    ? $result['total_products_count'] . " (" . $result['active_products_count'] . ")"
                    : $result['total_products_count'];

                $products_count = (string)$this->html->buildElement([
                    'type'  => 'button',
                    'name'  => 'view products',
                    'text'  => $text,
                    'href'  => $this->html->getSecureURL('catalog/product', '&category_id=' . $result['category_id']),
                    'title' => $title,
                ]);
            }

            //tree grid structure
            if ($this->config->get('config_show_tree_data')) {
                $name_label = '<label class="grid-parent-category" >' . $result['basename'] . '</label>';
            } else {
                $name_label = '<label class="grid-parent-category">'
                    . (str_replace($result['basename'], '', $result['name']))
                    . '</label>'
                    . $this->html->buildInput(
                        [
                            'name'  => 'category_description[' . $result['category_id'] . '][' . $language_id . '][name]',
                            'value' => $result['basename'],
                            'attr'  => ' maxlength="32" ',
                        ]
                    );
            }

            $response->rows[$i]['cell'] = [
                $thumbnail['thumb_html'],
                $name_label,
                $this->html->buildInput(
                    [
                        'name'  => 'sort_order[' . $result['category_id'] . ']',
                        'value' => $result['sort_order'],
                    ]
                ),
                $this->html->buildCheckbox(
                    [
                        'name'  => 'status[' . $result['category_id'] . ']',
                        'value' => $result['status'],
                        'style' => 'btn_switch',
                    ]
                ),
                $products_count,
                //TODO: need to think how to remove html-code from here
                $result['children_count']
                . ($result['children_count'] ?
                    '&nbsp;<a class="btn_action btn_grid grid_action_expand" href="#" rel="parent_id='
                    . $result['category_id'] . '" title="' . $this->language->get('text_view') . '">' .
                    '<i class="fa fa-folder-open"></i></a>'
                    : ''),
                'action',
                $new_level,
                ($this->data['search_parameters']['filter']['parent_id'] ?: null),
                //tree arrow
                ($result['category_id'] == $leaf_nodes[$result['category_id']]
                    || $this->data['search_parameters']['filter']['parent_id'] === null),
                false,
            ];
            $i++;
        }
        $this->data['response'] = $response;
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data['response']));
    }

    public function update()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadModel('catalog/product');
        $this->loadLanguage('catalog/category');
        if (!$this->user->canModify('listing_grid/category')) {
            $error = new AError('');
            $error->toJSONResponse('NO_PERMISSIONS_403',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'listing_grid/category'),
                    'reset_value' => true,
                ]
            );
            return;
        }

        switch ($this->request->post['oper']) {
            case 'del':
                $ids = explode(',', $this->request->post['id']);
                if (!empty($ids)) {
                    foreach ($ids as $id) {
                        try {
                            Category::deleteCategory($id);
                        } catch (\Exception $e) {
                            $error = new AError('');
                            $error->toJSONResponse('NO_PERMISSIONS_403',
                                [
                                    'error_text'  => $e->getMessage(),
                                    'reset_value' => true,
                                ]
                            );
                            return;
                        }
                    }
                }
                break;
            case 'save':
                $allowedFields = array_merge(['category_description', 'sort_order', 'status'], (array)$this->data['allowed_fields']);

                $ids = explode(',', $this->request->post['id']);
                if (!empty($ids)) {
                    //resort required.
                    if ($this->request->post['resort'] == 'yes') {
                        //get only ids we need
                        $array = [];
                        foreach ($ids as $id) {
                            $array[$id] = $this->request->post['sort_order'][$id];
                        }
                        $new_sort = H::build_sort_order($ids, min($array), max($array), $this->request->post['sort_direction']);
                        $this->request->post['sort_order'] = $new_sort;
                    }
                    foreach ($ids as $id) {
                        foreach ($allowedFields as $field) {
                            Category::editCategory($id, [$field => $this->request->post[$field][$id]]);
                        }
                    }
                }
                break;
            default:

        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    /**
     * update only one field
     *
     * @return void
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws AException
     */
    public function update_field()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('catalog/category');
        if (!$this->user->canModify('listing_grid/category')) {
            $error = new AError('');
            $error->toJSONResponse(
                'NO_PERMISSIONS_403',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'listing_grid/category'),
                    'reset_value' => true,
                ]
            );
            return;
        }

        $category_id = $this->request->get['id'];
        if (isset($category_id)) {
            //request sent from edit form. ID in url
            foreach ($this->request->post as $field => $value) {
                if ($field == 'keyword') {
                    if ($err = $this->html->isSEOkeywordExists('category_id=' . $category_id, $value)) {
                        $error = new AError('');
                        return $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $err]);
                    }
                }

                $err = $this->_validateField($category_id, $field, $value);
                if (!empty($err)) {
                    $error = new AError('');
                    $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $err]);
                    return;
                }

                Category::editCategory($category_id, [$field => $value]);
            }
            return;
        }
        $language_id = $this->language->getContentLanguageID();
        //request sent from jGrid. ID is key of array
        foreach ($this->request->post as $field => $value) {
            foreach ($value as $k => $v) {
                if ($field == 'category_description') {
                    if (mb_strlen($v[$language_id]['name']) < 2 || mb_strlen($v[$language_id]['name']) > 32) {
                        $err = $this->language->get('error_name');
                        $error = new AError('');
                        $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $err]);
                        return;
                    }
                }
                Category::editCategory($k, [$field => $v]);
            }
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function _validateField($category_id, $field, $value)
    {

        $err = '';
        switch ($field) {
            case 'category_description' :
                $language_id = $this->language->getContentLanguageID();

                if (isset($value[$language_id]['name'])
                    && (mb_strlen($value[$language_id]['name']) < 1 || mb_strlen($value[$language_id]['name']) > 255)
                ) {
                    $err = $this->language->get('error_name');
                }
                break;
            case 'model' :
                if (mb_strlen($value) > 64) {
                    $err = $this->language->get('error_model');
                }
                break;
            case 'keyword' :
                $err = $this->html->isSEOkeywordExists('category_id=' . $category_id, $value);
                break;
        }

        return $err;
    }

    public function categories()
    {
        $output = [];
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        if (isset($this->request->post['term'])) {
            $params = [
                'limit'  => 20,
                'filter' => [
                    'search_operator' => 'like',
                    'keyword'         => $this->request->post['term']
                ]
            ];
            $results = Category::getCategoriesData($params);
            //build thumbnails list
            $category_ids = [];
            foreach ($results as $category) {
                $category_ids[] = $category['category_id'];
            }
            $resource = new AResource('image');
            $thumbnails = $resource->getMainThumbList(
                'categories',
                $category_ids,
                $this->config->get('config_image_grid_width'),
                $this->config->get('config_image_grid_height')
            );
            foreach ($results as $item) {
                $thumbnail = $thumbnails[$item['category_id']];
                $output[] = [
                    'image'      => $thumbnail['thumb_html'] ?: '<i class="fa fa-code fa-4x"></i>&nbsp;',
                    'id'         => $item['category_id'],
                    'name'       => $item['name'],
                    'meta'       => '',
                    'sort_order' => (int)$item['sort_order'],
                ];
            }
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode($output));
    }
}