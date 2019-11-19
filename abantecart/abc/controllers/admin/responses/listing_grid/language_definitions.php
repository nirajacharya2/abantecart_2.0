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

use abc\core\engine\AController;
use abc\core\lib\AError;
use abc\core\lib\AFilter;
use abc\core\lib\AJson;
use H;
use stdClass;

class ControllerResponsesListingGridLanguageDefinitions extends AController
{
    public $data = [];

    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('localisation/language_definitions');
        $this->loadModel('localisation/language_definitions');

        //Prepare filter config
        $filter_params = array_merge(['language_key', 'section'], (array)$this->data['filter_params']);
        $grid_filter_params =
            array_merge(['name', 'language_key', 'language_value', 'block'], (array)$this->data['grid_filter_params']);

        $filter_form = new AFilter(['method' => 'get', 'filter_params' => $filter_params]);
        $filter_grid = new AFilter(['method' => 'post', 'grid_filter_params' => $grid_filter_params]);

        $filter_data = array_merge($filter_form->getFilterData(),
            $filter_grid->getFilterData());

        $total = $this->model_localisation_language_definitions->getTotalDefinitions($filter_data);
        $response = new stdClass();
        $response->page = $filter_grid->getParam('page');
        $response->total = $filter_grid->calcTotalPages($total);
        $response->records = $total;
        $response->userdata = new stdClass();

        $results = $this->model_localisation_language_definitions->getLanguageDefinitions($filter_data);

        $i = 0;
        foreach ($results as $result) {
            if ($result['error']) {
                $response->userdata->classes[$result['language_definition_id']] = 'warning';
            }
            $response->userdata->section[$result['language_definition_id']] = $result['section'];

            $response->rows[$i]['id'] = $result['language_definition_id'];
            $response->rows[$i]['cell'] = [
                $result['block'],
                $result['language_key'],
                $this->html->buildInput([
                    'name'  => 'language_value['.$result['language_definition_id'].']',
                    'value' => $result['language_value'],
                ]),
                H::dateISO2Display($result['date_modified'], $this->language->get('date_format_short')),
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

        $this->loadModel('localisation/language_definitions');
        $this->loadLanguage('localisation/language_definitions');

        if (!$this->user->canModify('listing_grid/language_definitions')) {
            $error = new AError('');
            return $error->toJSONResponse('NO_PERMISSIONS_402',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'),
                        'listing_grid/language_definitions'),
                    'reset_value' => true,
                ]);
        }

        switch ($this->request->post['oper']) {
            case 'del':
                $ids = explode(',', $this->request->post['id']);
                if (!empty($ids)) {
                    foreach ($ids as $id) {
                        $this->model_localisation_language_definitions->deleteLanguageDefinition($id);
                    }
                }
                break;
            case 'save':
                $ids = explode(',', $this->request->post['id']);
                if (!empty($ids)) {
                    foreach ($ids as $id) {
                        $data = [
                            'block'          => $this->request->post['block'][$id],
                            'language_key'   => $this->request->post['language_key'][$id],
                            'language_value' => $this->request->post['language_value'][$id],
                        ];
                        $this->model_localisation_language_definitions->editLanguageDefinition($id, $data);
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

        $this->loadLanguage('localisation/language_definitions');
        if (!$this->user->canModify('listing_grid/language_definitions')) {
            $error = new AError('');
            return $error->toJSONResponse('NO_PERMISSIONS_402',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'),
                        'listing_grid/language_definitions'),
                    'reset_value' => true,
                ]);
        }

        $this->loadModel('localisation/language_definitions');
        $allowedFields =
            array_merge(['block', 'language_key', 'language_value', 'section'], (array)$this->data['allowed_fields']);

        $save_id = $this->request->get['id'];
        if (isset($save_id)) {
            //request sent from edit form. ID in url
            foreach ($this->request->post as $key => $value) {
                if (!in_array($key, $allowedFields)) {
                    continue;
                }
                if ($key == 'language_value') {
                    //load definition values.
                    $def = $this->model_localisation_language_definitions->getLanguageDefinition($save_id);
                    //if definition does not match language this means we create new one
                    if ($def['language_id'] != key($value)) {
                        //save new
                        $def['language_id'] = key($value);
                        $def['language_definition_id'] = '';
                        $def['language_value'] = current($value);
                        $this->model_localisation_language_definitions->addLanguageDefinition($def);
                    } else {
                        //edit
                        $def['language_value'] = current($value);
                        $this->model_localisation_language_definitions->editLanguageDefinition($save_id, $def);
                    }
                }
                if (in_array($key, ['block', 'section', 'language_key'])) {
                    $def = $this->model_localisation_language_definitions->getLanguageDefinition($save_id);
                    $def_ids = $this->model_localisation_language_definitions->getAllLanguageDefinitionsIdByKey(
                        $def['language_key'],
                        $def['block'],
                        $def['section']);
                    if ($def_ids) {
                        foreach ($def_ids as $item) {
                            $this->model_localisation_language_definitions->editLanguageDefinition($item['language_definition_id'],
                                [$key => $value]);
                        }
                    }
                }

            }
            return null;
        }

        //request sent from jGrid. ID is key of array
        foreach ($this->request->post as $key => $value) {
            if (!in_array($key, ['language_value'])) {
                continue;
            }
            foreach ($value as $k => $v) {
                $data = [$key => $v];
                $this->model_localisation_language_definitions->editLanguageDefinition($k, $data);
            }
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function checkDefinition()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('localisation/language_definitions');

        $this->loadModel('localisation/language_definitions');
        $def_id = $this->model_localisation_language_definitions->getAllLanguageDefinitionsIdByKey(
            $this->request->post['language_key'],
            $this->request->post['block'],
            $this->request->post['section']);

        if ($def_id) {
            $error_text = $this->language->get('error_already exists');
            $error_text = sprintf($error_text,
                $this->request->post['language_key'],
                $this->request->post['block'],
                ($this->request->post['section'] ? 'Admin' : 'Storefront'),
                $this->html->getSecureURL('localisation/language_definitions/update',
                    '&language_definition_id='.$def_id[0]['language_definition_id']));

            $this->load->library('json');
            $this->response->setOutput(AJson::encode(['error' => $error_text]));
        }
    }

}
