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
use abc\core\engine\AForm;
use abc\core\lib\AError;
use abc\core\lib\AJson;
use abc\core\engine\AResource;
use abc\core\view\AView;
use abc\extensions\banner_manager\models\Banner;
use H;
use stdClass;

class ControllerResponsesListingGridBannerManager extends AController
{
    public function main()
    {
        $this->loadLanguage('banner_manager/banner_manager');
        $page = (int)$this->request->post['page'] ?: 1;
        $limit = $this->request->post['rows'];
        $sort = $this->request->post['sidx'];
        $order = $this->request->post['sord'];

        $this->data['banner_search_parameters'] = [
            'language_id' => $this->language->getContentLanguageID(),
            'start'       => ($page - 1) * $limit,
            'limit'       => $limit,
            'sort'        => $sort,
            'order'       => $order
        ];

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $results = (array)Banner::getBanners($this->data['banner_search_parameters'])?->toArray();

        $total = (int)$results[0]['total_num_rows'];
        $total_pages = $total > 0 ? ceil($total / $limit) : 0;

        $response = new stdClass();
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $total;

        $ids = array_map('intval', array_column($results, 'banner_id'));

        $resource = new AResource('image');
        $thumbnails = $resource->getMainThumbList(
            'banners',
            $ids,
            $this->config->get('config_image_grid_width'),
            $this->config->get('config_image_grid_height')
        );

        foreach ($results as $i => $result) {
            $response->rows[$i]['id'] = $result['banner_id'];
            $thumbnail = $thumbnails[$result['banner_id']]['thumb_html'];
            //check if banner is active based on dates and update status
            $now = time();
            if (H::dateISO2Int($result['start_date']) > $now) {
                $result['status'] = 0;
            }
            $stop = H::dateISO2Int($result['end_date']);
            if ($stop > 0 && $stop < $now) {
                $result['status'] = 0;
            }

            $response->rows[$i]['cell'] = [
                $result['banner_id'],
                $thumbnail,
                $result['name'],
                $result['banner_group_name'],
                (
                $result['banner_type'] == 1
                    ? $this->language->get('text_graphic_banner')
                    : $this->language->get('text_text_banner')
                ),
                $this->html->buildCheckbox(
                    [
                        'name'  => 'status[' . $result['banner_id'] . ']',
                        'value' => $result['status'],
                        'style' => 'btn_switch',
                    ]
                ),
                $result['date_modified'],
            ];
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($response));
    }

    public function update_field()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if ($this->request->is_POST()) {
            if (isset($this->request->post['start_date'])) {
                $this->request->post['start_date'] = $this->request->post['start_date']
                    ? H::dateDisplay2ISO($this->request->post['start_date'])
                    : null;
            }
            if (isset($this->request->post['end_date'])) {
                $this->request->post['end_date'] = $this->request->post['end_date']
                    ? H::dateDisplay2ISO($this->request->post['end_date'])
                    : null;
            }

            //request sent from edit form. ID in url
            foreach ($this->request->post as $field => $value) {
                if ($field == 'banner_group_name') {
                    $tmp = [];
                    if (isset($value[0]) && !in_array($value[0], ['0', 'new'])) {
                        $tmp = ['banner_group_name' => trim($value[0])];
                    }
                    if (isset($value[1])) {
                        $tmp = ['banner_group_name' => trim($value[1])];
                    }
                    $id = (int)$this->request->get['banner_id'];
                    Banner::editBanner($id, $tmp);
                } elseif (is_array($value)) {
                    foreach ($value as $id => $val) {
                        $tmp[$field] = (int)$val;
                        Banner::editBanner($id, $tmp);
                    }
                } else {
                    if ((int)$this->request->get['banner_id']) {
                        Banner::editBanner($this->request->get['banner_id'], [$field => $value]);
                    }
                }
            }
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function edit()
    {
        $this->data['allowed_fields'] = ['status'];
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('banner_manager/banner_manager');
        if (!$this->user->canModify('extension/banner_manager')) {

            $errorText = sprintf(
                $this->language->get('error_permission_modify'),
                'extension/banner_manager'
            );

            $err = new AError('');
            $err->toJSONResponse(
                'VALIDATION_ERROR_406',
                [
                    'error_text'  => $errorText,
                    'reset_value' => true,
                ]
            );
            return;
        }

        switch ($this->request->post['oper']) {
            case 'del':
                $ids = explode(',', $this->request->post['id']);
                if ($ids) {
                    Banner::find($ids)?->delete();
                }
                break;
            case 'save':
                $ids = explode(',', $this->request->post['id']);
                if (!empty($ids)) {
                    foreach ($ids as $id) {
                        if (!isset($this->request->post['status'][$id])) {
                            $this->request->post['status'][$id] = 0;
                        }
                        foreach ($this->data['allowed_fields'] as $field) {
                            Banner::editBanner($id, [$field => $this->request->post[$field][$id]]);
                        }
                    }
                }
                break;
            default:
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }


    public function getBannerListData()
    {
        $this->load->library('json');
        $this->loadLanguage('banner_manager/banner_manager');
        $page = (int)$this->request->post['page'] ?: 1;
        $limit = $this->request->post['rows'];
        $sort = $this->request->post['sidx'];
        $order = $this->request->post['sord'];

        $this->data['banner_search_parameters'] = [
            'language_id' => $this->language->getContentLanguageID(),
            'start'       => ($page - 1) * $limit,
            'limit'       => $limit,
            'sort'        => $sort,
            'order'       => $order
        ];

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $results = (array)Banner::getBanners($this->data['banner_search_parameters'])?->toArray();
        $total = (int)$results[0]['total_num_rows'];
        $total_pages = $total > 0 ? ceil($total / $limit) : 0;


        $list = $this->session->data['listing_selected'];

        $id_list = [];
        foreach ($list as $id => $row) {
            if ($row['status']) {
                $id_list[] = $id;
            }
        }

        $response = new stdClass();
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $total;

        $ids = [];
        foreach ($results as $result) {
            if ($result['banner_type'] == 1) {
                $ids[] = (int)$result['banner_id'];
            }
        }
        $resource = new AResource('image');
        $thumbnails = $resource->getMainThumbList(
            'banners',
            $ids,
            27,
            27
        );

        foreach ($results as $i => $result) {
            if (in_array($result['banner_id'], $id_list)) {
                $response->userdata->selId[] = $result['banner_id'];
            }

            $action = '<a class="btn_action" href="JavaScript:void(0);"
                        onclick="showPopup(\'' . $this->html->getSecureURL('extension/banner_manager/edit', '&banner_id=' . $result['banner_id']) . '\')" 
                        title="' . $this->language->get('text_view') . '">'
                . '<img height="27" 
                        src="' . $this->view->templateResource('assets/images/icons/icon_grid_view.png') . '" 
                        alt="' . $this->language->get('text_edit') . '" /></a>';

            $response->rows[$i]['id'] = $result['banner_id'];
            $thumbnail = $result['banner_type'] == 1
                ? $thumbnails[$result['banner_id']]['thumb_html']
                : '';

            $response->rows[$i]['cell'] = [
                $thumbnail,
                $result['name'],
                $result['banner_group_name'],
                ($result['banner_type'] == 1
                    ? $this->language->get('text_graphic_banner')
                    : $this->language->get(
                        'text_text_banner'
                    )),
                $action,
            ];
        }

        $this->data['response'] = $response;

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode($this->data['response']));
    }

    public function banners()
    {
        $this->load->library('json');
        $this->response->addJSONHeader();

        $this->data['output'] = [];

        $this->data['banner_search_parameters'] = [
            'language_id' => $this->language->getContentLanguageID(),
            'limit'       => 20,
            'filter'      => [
                'keyword' => $this->request->post['term'],
                'exclude' => (array)$this->request->post['exclude']
            ]
        ];

        if (!$this->request->post['term']) {
            $this->response->setOutput(AJson::encode([]));
            return;
        }

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $banners = (array)Banner::getBanners($this->data['banner_search_parameters'])?->toArray();
        $ids = array_map('intval', array_column($banners, 'banner_id'));

        $resource = new AResource('image');
        $thumbnails = $resource->getMainThumbList(
            'banners',
            $ids,
            $this->config->get('config_image_grid_width'),
            $this->config->get('config_image_grid_height'),
            false
        );

        foreach ($banners as $banner) {
            $thumbnail = $thumbnails[$banner['banner_id']];
            $icon = $thumbnail['thumb_html'] ?: '<i class="fa fa-quote-right fa-4x"></i>&nbsp;';
            $status = $banner['status'];
            //check if banner is active based on dates and update status
            $now = time();
            if (H::dateISO2Int($banner['start_date']) > $now) {
                $status = 0;
            }
            $stop = H::dateISO2Int($banner['end_date']);
            if ($stop > 0 && $stop < $now) {
                $status = 0;
            }

            $this->data['output'][] = [
                'image'      => $icon,
                'id'         => $banner['banner_id'],
                'name'       => $banner['name'] . ' ' . (!$status ? '(inactive)' : ''),
                'sort_order' => (int)$banner['sort_order'],
            ];
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->response->setOutput(AJson::encode($this->data['output']));
    }
}