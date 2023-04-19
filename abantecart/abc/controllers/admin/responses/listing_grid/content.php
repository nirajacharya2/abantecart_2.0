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
use abc\core\lib\AError;
use abc\core\lib\AException;
use abc\core\lib\AJson;
use abc\core\lib\ALayoutManager;
use abc\models\content\Content;
use abc\models\content\ContentDescription;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;
use stdClass;

class ControllerResponsesListingGridContent extends AController
{
    public function main()
    {
        $page = (int)$this->request->post['page'] ?: 1;
        $limit = $this->request->post['rows'];
        $sort = $this->request->post['sidx'];
        $order = $this->request->post['sord'];

        $this->data['search_parameters'] = [
            'filter'      => [
                'parent_id' => 0
            ],
            'language_id' => $this->language->getContentLanguageID(),
            'start'       => ($page - 1) * $limit,
            'limit'       => $limit,
            'sort'        => $sort,
            'order'       => $order
        ];

        if (isset($this->request->post['_search']) && $this->request->post['_search'] == 'true') {
            $searchData = AJson::decode(htmlspecialchars_decode($this->request->post['filters']), true);
            $allowedFields = array_merge(['keyword'], (array)$this->data['allowed_fields']);
            foreach ($searchData['rules'] as $rule) {
                if (!in_array($rule['field'], $allowedFields)) {
                    continue;
                }
                $this->data['search_parameters']['filter'][$rule['field']] = $rule['data'];
                unset($this->data['search_parameters']['filter']['parent_id']);
            }
        }

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('design/content');

        $new_level = 0;

        $leaf_nodes = Content::getLeafContents();
        if ($this->request->post['nodeid']) {
            //reset filter to get only parent category
            $this->data['search_parameters'] = [];
            $this->data['search_parameters']['filter']['parent_id'] = (int)$this->request->post['nodeid'];
            $new_level = (int)$this->request->post["n_level"] + 1;
        }

        $results = Content::getContents($this->data['search_parameters']);
        $total = $results::getFoundRowsCount();
        $results = $results?->toArray();


        $total_pages = $total > 0 ? ceil($total / $limit) : 0;

        $response = new stdClass();
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $total;
        $response->userdata = (object)[''];

        $i = 0;

        foreach ($results as $result) {
            $id = $result['content_id'];
            if ($this->config->get('config_show_tree_data')) {
                $title_label = '<label style="white-space: nowrap;">' . $result['name'] . '</label>';
            } else {
                $title_label = $result['name'];
            }
            $response->rows[$i]['id'] = $id;
            $response->rows[$i]['cell'] = [

                $title_label,
                $result['parent_name'],
                $this->html->buildCheckbox([
                    'name'  => 'status[' . $id . ']',
                    'value' => $result['status'],
                    'style' => 'btn_switch',
                ]),
                $this->html->buildInput([
                    'name'  => 'sort_order[' . $id . ']',
                    'value' => $result['sort_order'],
                ]),
                'action',
                $new_level,
                $this->data['search_parameters']['filter']['parent_id'],
                ($result['content_id'] == $leaf_nodes[$id] || $this->data['search_parameters']['filter']['parent_id'] === null),
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
        $this->loadLanguage('design/content');
        if (!$this->user->canModify('listing_grid/content')) {
            $error = new AError('');
            $error->toJSONResponse('NO_PERMISSIONS_403',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'listing_grid/content'),
                    'reset_value' => true,
                ]
            );
            return;
        }
        $ids = explode(',', $this->request->post['id']);
        switch ($this->request->post['oper']) {
            case 'del':
                if (!empty($ids)) {
                    foreach ($ids as $id) {
                        $content_id = $id;
                        if ($this->config->get('config_account_id') == $content_id) {
                            $this->response->setOutput($this->language->get('error_account'));
                            return;
                        }

                        if ($this->config->get('config_checkout_id') == $content_id) {
                            $this->response->setOutput($this->language->get('error_checkout'));
                            return;
                        }
                        Content::find($content_id)?->delete();
                    }
                }
                break;
            case 'save':
                $ids = array_map('intval', array_unique(explode(',', $this->request->post['id'])));
                if (!empty($ids)) {
                    $fields = array_keys($this->request->post);
                    foreach ($ids as $id) {
                        $upd = [
                            'language_id' => $this->language->getContentLanguageID()
                        ];
                        foreach ($fields as $key) {
                            if (isset($this->request->post[$key][$id])) {
                                $upd[$key] = $this->request->post[$key][$id];
                            }
                        }
                        Content::editContent($id, $upd);
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
        $this->loadLanguage('design/content');
        if (!$this->user->canModify('listing_grid/content')) {
            $error = new AError('');
            $error->toJSONResponse('NO_PERMISSIONS_403',
                [
                    'error_text'  => sprintf(
                        $this->language->get('error_permission_modify'),
                        'listing_grid/content'
                    ),
                    'reset_value' => true,
                ]
            );
            return;
        }
        if (isset($this->request->get['id'])) {
            if (isset($this->request->post['keyword']) == 'keyword') {
                $value = $this->request->post['keyword'];
                if ($err = $this->html->isSEOkeywordExists('content_id=' . $this->request->get['id'], $value)) {
                    $error = new AError('');
                    $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $err]);
                    return;
                }
            }
            Content::editContent($this->request->get['id'], $this->request->post);
        } else {
            //request sent from jGrid. ID is key of array
            foreach ($this->request->post as $field => $value) {
                foreach ($value as $k => $v) {
                    Content::editContent($k, [$field => $v]);
                }
            }
        }
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}