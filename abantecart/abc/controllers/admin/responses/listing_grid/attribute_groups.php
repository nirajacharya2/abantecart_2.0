<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2017 Belavier Commerce LLC

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

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\lib\AError;
use abc\core\lib\AJson;
use abc\core\lib\contracts\AttributeManagerInterface;
use stdClass;

class ControllerResponsesListingGridAttributeGroups extends AController
{
    /**
     * @var AttributeManagerInterface
     */
    private $attribute_manager;
    public $data = [];

    public function __construct($registry, $instance_id, $controller, $parent_controller = '')
    {
        parent::__construct($registry, $instance_id, $controller, $parent_controller);
        $this->attribute_manager = ABC::getObjectByAlias('AttributeManager');
        $this->loadLanguage('catalog/attribute_groups');
    }

    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $page = $this->request->post['page']; // get the requested page
        $limit = $this->request->post['rows']; // get how many rows we want to have into the grid
        $sidx = $this->request->post['sidx']; // get index row - i.e. user click to sort
        $sord = $this->request->post['sord']; // get the direction

        $data = [
            'sort'        => $sidx,
            'order'       => $sord,
            'start'       => ($page - 1) * $limit,
            'limit'       => $limit,
            'language_id' => $this->session->data['content_language_id'],
        ];

        $total = $this->attribute_manager->getTotalAttributeGroups();
        if ($total > 0) {
            $total_pages = ceil($total / $limit);
        } else {
            $total_pages = 0;
        }

        $response = new stdClass();
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $total;

        $results = $this->attribute_manager->getAttributeGroups($data);
        $i = 0;
        foreach ($results as $result) {
            $response->rows[$i]['id'] = $result['attribute_group_id'];
            $response->rows[$i]['cell'] = [
                $this->html->buildInput([
                    'name'  => 'name['.$result['attribute_group_id'].']',
                    'value' => $result['name'],
                ]),
                $this->html->buildInput([
                    'name'  => 'sort_order['.$result['attribute_group_id'].']',
                    'value' => $result['sort_order'],
                ]),
                $this->html->buildCheckbox([
                    'name'  => 'status['.$result['attribute_group_id'].']',
                    'value' => $result['status'],
                    'style' => 'btn_switch',
                ]),
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
        if (!$this->user->canModify('catalog/attribute_groups')) {
            $error = new AError('');
            return $error->toJSONResponse('NO_PERMISSIONS_402',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'),
                        'catalog/attribute_groups'),
                    'reset_value' => true,
                ]);
        }

        switch ($this->request->post['oper']) {
            case 'del':
                $ids = explode(',', $this->request->post['id']);
                if (!empty($ids)) {
                    foreach ($ids as $id) {
                        $err = $this->validateDelete($id);
                        if (!empty($err)) {
                            $error = new AError('');
                            return $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $err]);
                        }
                        $this->attribute_manager->deleteAttributeGroup($id);
                    }
                }
                break;
            case 'save':
                $fields = ['name', 'sort_order', 'status'];
                $ids = explode(',', $this->request->post['id']);
                if (!empty($ids)) {
                    foreach ($ids as $id) {
                        foreach ($fields as $f) {
                            if (isset($this->request->post[$f][$id])) {
                                $err = $this->validateField($f, $this->request->post[$f][$id]);
                                if (!empty($err)) {
                                    $error = new AError('');
                                    return $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $err]);
                                }
                                $this->attribute_manager->updateAttributeGroup($id,
                                    [$f => $this->request->post[$f][$id]]);
                            }
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
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public function update_field()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (!$this->user->canModify('catalog/attribute_groups')) {
            $error = new AError('');
            return $error->toJSONResponse('NO_PERMISSIONS_402',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'),
                        'catalog/attribute_groups'),
                    'reset_value' => true,
                ]);
        }

        if (isset($this->request->get['id'])) {
            //request sent from edit form. ID in url
            foreach ($this->request->post as $key => $value) {
                $err = $this->validateField($key, $value);
                if (!empty($err)) {
                    $error = new AError('');
                    return $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $err]);
                }
                $data = [$key => $value];
                $this->attribute_manager->updateAttributeGroup($this->request->get['id'], $data);
            }
            return null;
        }

        //request sent from jGrid. ID is key of array
        $fields = ['name', 'sort_order', 'status'];
        foreach ($fields as $f) {
            if (isset($this->request->post[$f])) {
                foreach ($this->request->post[$f] as $k => $v) {
                    $err = $this->validateField($f, $v);
                    if (!empty($err)) {
                        $error = new AError('');
                        return $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $err]);
                    }
                    $this->attribute_manager->updateAttributeGroup($k, [$f => $v]);
                }
            }
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function validateField($field, $value)
    {
        $err = '';
        switch ($field) {
            case 'name' :
                if (mb_strlen($value) < 2 || mb_strlen($value) > 32) {
                    $err = $this->language->get('error_name');
                }
                break;
        }

        return $err;
    }

    protected function validateDelete($attribute_groups_id)
    {
        return null;
    }

}