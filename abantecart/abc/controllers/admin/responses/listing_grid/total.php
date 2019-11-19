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

namespace abc\controllers\admin;

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\lib\AError;
use abc\core\lib\AJson;
use stdClass;


class ControllerResponsesListingGridTotal extends AController
{
    public $data = [];

    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('extension/total');

        $page = $this->request->post['page']; // get the requested page
        if ((int)$page < 0) {
            $page = 0;
        }
        $limit = $this->request->post['rows']; // get how many rows we want to have into the grid
        $sidx = $this->request->post['sidx']; // get index row - i.e. user click to sort
        $sord = $this->request->post['sord']; // get the direction

        $this->loadModel('setting/extension');
        $ext = $this->extensions->getExtensionsList(
            ['filter' => 'total']
        );
        $extensions = [];
        if ($ext->rows) {
            foreach ($ext->rows as $row) {
                $language_rt = $config_controller = '';
                // for total-extensions inside engine
                $filename = ABC::env('DIR_APP')
                    .'controllers'.DS
                    .'pages'.DS
                    .'total'.DS
                    .$row['key'].'.php';
                if (is_file($filename)) {
                    $config_controller = $language_rt = 'total/'.$row['key'];
                } else {
                    // looking for config controller into parent extension.
                    //That Controller must to have filename equal child extension text id
                    $parents = $this->extension_manager->getParentsExtensionTextId($row['key']);

                    if ($parents) {
                        foreach ($parents as $parent) {
                            if (!$parent['status']) {
                                continue;
                            }
                            $filename = ABC::env('DIR_APP_EXTENSIONS')
                                .$parent['key'].DS
                                .'controllers'.DS
                                .'admin'.DS
                                .'pages'.DS
                                .'total'.DS
                                .$row['key'].'.php';
                            if (is_file($filename)) {
                                $config_controller = 'total/'.$row['key'];
                                $language_rt = $parent['key'].'/'.$parent['key'];
                                break;
                            }
                        }
                    }
                }
                if ($config_controller) {
                    $extensions[$row['key']] = [
                        'extension_txt_id'  => $row['key'],
                        'config_controller' => $config_controller,
                        'language_rt'       => $language_rt,
                    ];
                }
            }
        }

        //looking for uninstalled engine's total-extensions
        $files = glob(ABC::env('DIR_APP')
            .'controllers'.DS
            .'admin'.DS
            .'pages'.DS
            .'total'.DS.'*.php');
        if ($files) {
            foreach ($files as $file) {
                $id = basename($file, '.php');
                if (!array_key_exists($id, $extensions)) {
                    $extensions[$id] = [
                        'extension_txt_id'  => $id,
                        'config_controller' => 'total/'.$id,
                        'language_rt'       => 'total/'.$id,
                    ];
                }
            }
        }

        $items = [];
        if ($extensions) {
            foreach ($extensions as $extension) {
                $this->loadLanguage($extension['language_rt']);
                $items[] = [
                    'id'                => $extension['extension_txt_id'],
                    'name'              => $this->language->get('total_name'),
                    'status'            => $this->config->get($extension['extension_txt_id'].'_status'),
                    'sort_order'        => (int)$this->config->get($extension['extension_txt_id'].'_sort_order'),
                    'calculation_order' => (int)$this->config->get($extension['extension_txt_id'].'_calculation_order'),
                    'action'            => $this->html->getSecureURL($extension['config_controller']),
                ];
            }
        }

        //sort
        $allowedSort = ['name', 'status', 'sort_order', 'calculation_order'];
        $allowedDirection = [SORT_ASC => 'asc', SORT_DESC => 'desc'];
        if (!in_array($sidx, $allowedSort)) {
            $sidx = $allowedSort[0];
        }
        if (!in_array($sord, $allowedDirection)) {
            $sord = SORT_ASC;
        } else {
            $sord = array_search($sord, $allowedDirection);
        }

        $sort = [];
        foreach ($items as $item) {
            $sort[] = $item[$sidx];
        }

        array_multisort($sort, $sord, $items);

        $total = count($items);
        if ($total > 0) {
            $total_pages = ceil($total / $limit);
        } else {
            $total_pages = 0;
        }

        $response = new stdClass();
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $total;

        $response->userdata = new stdClass();
        $response->userdata->rt = [];
        $response->userdata->classes = [];

        $results = array_slice($items, ($page - 1) * -$limit, $limit);

        $i = 0;
        foreach ($results as $result) {
            $response->userdata->rt[$result['id']] = $result['action'];
            $status = $this->html->buildCheckbox([
                'name'  => $result['id'].'['.$result['id'].'_status]',
                'value' => $result['status'],
                'style' => 'btn_switch',
            ]);
            $sort = $this->html->buildInput([
                'name'  => $result['id'].'['.$result['id'].'_sort_order]',
                'value' => $result['sort_order'],
            ]);

            $calc = $this->html->buildInput([
                'name'  => $result['id'].'['.$result['id'].'_calculation_order]',
                'value' => $result['calculation_order'],
            ]);

            $response->rows[$i]['id'] = $result['id'];
            $response->rows[$i]['cell'] = [
                $result['name'],
                $status,
                ($result['status'] ? $sort : ''),
                ($result['status'] ? $calc : ''),
            ];
            $i++;
        }
        $this->data['response'] = $response;

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data['response']));
    }

    /**
     * update only one field
     *
     * @return null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public function update_field()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('extension/total');
        $ids = [];
        if (isset($this->request->get['id'])) {
            $ids[] = $this->request->get['id'];
        } else {
            $ids = array_keys($this->request->post);
        }

        if (!$this->user->canModify('listing_grid/total')) {
            $error = new AError('');
            return $error->toJSONResponse('NO_PERMISSIONS_402',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'listing_grid/total'),
                    'reset_value' => true,
                ]);
        }
        foreach ($ids as $id) {
            if (!$this->user->canModify('total/'.$id)) {
                $error = new AError('');
                return $error->toJSONResponse('NO_PERMISSIONS_402',
                    [
                        'error_text'  => sprintf($this->language->get('error_permission_modify'), 'total/'.$id),
                        'reset_value' => true,
                    ]);
            }
        }

        $this->loadModel('setting/setting');

        if (isset($this->request->get['id'])) {
            //request sent from edit form. ID in url
            $this->model_setting_setting->editSetting($this->request->get['id'], $this->request->post);
            return null;
        }

        //request sent from jGrid. ID is key of array
        foreach ($this->request->post as $group => $values) {
            $this->model_setting_setting->editSetting($group, $values);
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        return null;
    }

}