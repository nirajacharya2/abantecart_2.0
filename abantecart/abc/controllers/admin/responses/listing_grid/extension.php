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

namespace abc\controllers\admin;

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\lib\AError;
use abc\core\lib\AJson;
use H;
use stdClass;

/**
 * Class ControllerResponsesListingGridExtension
 */
class ControllerResponsesListingGridExtension extends AController
{

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('extension/extensions');

        $page = $this->request->post['page']; // get the requested page
        if ((int) $page < 0) {
            $page = 0;
        }
        $limit = $this->request->post['rows']; // get how many rows we want to have into the grid
        $sidx = $this->request->post['sidx']; // get index row - i.e. user click to sort
        $sord = $this->request->post['sord']; // get the direction

        $search_str = '';
        if (isset($this->request->post['_search']) && $this->request->post['_search'] == 'true') {
            $searchData = json_decode(htmlspecialchars_decode($this->request->post['filters']), true);
            $search_str = $searchData['rules'][0]['data'];
        }

        $store_id = (int) $this->config->get('config_store_id');
        if ($this->request->get_or_post('store_id')) {
            $store_id = $this->request->get_or_post('store_id');
        }

        //sort
        $allowedSort = array_merge(
            [1 => 'key', 'name', 'category', 'date_modified', 'status', 'store_name'],
            (array) $this->data['allowed_sort']
        );
        if (!in_array($sidx, $allowedSort)) {
            $sidx = 'date_modified';
        }

        $allowedDirection = [SORT_ASC => 'asc', SORT_DESC => 'desc'];
        if (!in_array($sord, $allowedDirection)) {
            $sord = 'asc';
        }

        //extensions that have record in DB but missing files
        $missing_extensions = $this->extensions->getMissingExtensions();

        $data = [
            'store_id'   => $store_id,
            'search'     => $search_str,
            'filter'     => $this->session->data['extension_filter'],
            'sort_order' => [$sidx, $sord],
        ];
        if ($this->config->get('config_store_id')) {
            $data['store_id'] = (int) $this->config->get('config_store_id');
        }
        //extensions list. NOTE: set "force" mode to get data from db

        $extensions = $this->extension_manager->getExtensionsList($data, 'force');

        $total = $extensions->total;
        if ($total > 0) {
            $total_pages = ceil($total / $limit);
        } else {
            $total_pages = 0;
        }

        $response = new stdClass();
        $response->rows = [];
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $total;
        $response->userdata = new stdClass();

        $i = 0;
        $for_push = $push = [];

        // get extensions for install
        $ready_to_install = $this->session->data['ready_to_install'];
        $to_install = $to_inst_keys = [];

        if (!H::has_value($this->session->data['extension_filter'])
            || $this->session->data['extension_filter'] == 'extensions'
        ) {
            if ($ready_to_install && is_array($ready_to_install)) {
                foreach ($ready_to_install as $pack) {
                    $to_install[$pack['extension_name']] =
                        [
                            'remote_install'    => true,
                            'key'               => $pack['extension_name'],
                            'name'              => $pack['download_name'],
                            'extension_version' => $pack['extension_version'],
                            'installation_key'  => $pack['installation_key'],
                            'date_modified'     => $pack['date_modified'],
                        ];
                    $to_inst_keys[] = $pack['extension_name'];
                }
            }
        }

        //filter already installed from remote list (ignores new versions too)
        foreach ($extensions->rows as $row) {
            if (in_array($row['key'], $to_inst_keys)) {
                unset($to_install[$row['key']]);
            }
        }

        $rows = array_merge($to_install, $extensions->rows);
        $updates = $this->cache->get('extensions.updates');

        foreach ($rows as $row) {
            $extension = $row['key'];
            $response->rows[$i]['id'] = str_replace(' ', '-', $row['key']).'_'.(int) $row['store_id'];
            $id = $response->rows[$i]['id'];

            $response->userdata->extension_id[$id] = $extension;
            $response->userdata->store_id[$id] = $row['store_id'];

            $name = $row['name'] ? : trim($this->extensions->getExtensionName($extension));

            //for new extensions
            if ($row['remote_install']) {
                $response->userdata->installation_key[$id] = $row['installation_key'];
                $icon = '<img src="'.ABC::env('RDIR_ASSETS').'images/default_extension.png'.'" alt="" border="0" />';
                $category = '';
                $status = $this->language->get('text_ready_to_install');
                $response->userdata->classes[$id] =
                    'success disable-edit disable-delete disable-uninstall disable-install';
            } elseif (in_array($extension, $missing_extensions)) {
                $response->userdata->classes[$id] =
                    'warning disable-edit disable-install disable-uninstall disable-remote-install';

                $icon = '<img src="'.ABC::env('RDIR_ASSETS').'images/default_extension.png'.'" alt="" border="0" />';
                $name = sprintf($this->language->get('text_missing_extension'), $extension);
                $category = $status = '';
                // change it for show it in list first by default sorting
                $row['date_modified'] = date('Y-m-d H:i:s', time());
            } elseif (!file_exists(ABC::env('DIR_APP_EXTENSIONS').$extension.'/main.php')
                || !file_exists(ABC::env('DIR_APP_EXTENSIONS').$extension.'/config.xml')) {
                $response->userdata->classes[$id] =
                    'warning disable-edit disable-install disable-uninstall disable-remote-install';
                $icon = '<img src="'.ABC::env('RDIR_ASSETS').'images/default_extension.png'.'" alt="" border="0" />';
                $name = sprintf($this->language->get('text_broken_extension'), $extension);
                $category = $status = '';
                // change it for show it in list first by default sorting
                $row['date_modified'] = date('Y-m-d H:i:s', time());
            } else {
                if (!$this->config->has($extension.'_status')) {
                    $response->userdata->classes[$id] =
                        'disable-edit disable-install disable-uninstall disable-remote-install';
                    $status = $this->language->get('text_not_installed');
                } else {
                    $response->userdata->classes[$id] =
                        'disable-delete disable-uninstall disable-install disable-remote-install';
                    $status = $this->html->buildCheckbox([
                                                             'name'  => $extension.'['.$extension.'_status]',
                                                             'value' => $row['status'],
                                                             'style' => 'btn_switch',
                                                         ]);
                }

                $icon_relative_url = ABC::env('DIRNAME_EXTENSIONS')
                    .$extension
                    .'/'.ABC::env('RDIR_ASSETS')
                    .'images/icon.png';
                $icon_filename = ABC::env('DIR_PUBLIC')
                    .ABC::env('DIRNAME_EXTENSIONS')
                    .$extension
                    .DS
                    .ABC::env('RDIR_ASSETS')
                    .'images'.DS.'icon.png';
                $icon = (is_file($icon_filename)
                    ? $icon_relative_url : ABC::env('RDIR_ASSETS').'images/default_extension.png');
                if (!$this->config->has($extension.'_status')) {
                    $icon = '<img src="'.$icon.'" alt="'.$extension.'" border="0" />';
                } else {
                    $icon = '<a href="'.$this->html->getSecureURL(
                            'extension/extensions/edit',
                            $this->data['url'].'&extension='.$extension
                        ).'"><img src="'.$icon.'" alt="'.$extension.'" style="border: 0" /></a>';
                }

                $category = $row['category'];
                // if update available
                if (is_array($updates) && in_array($extension, array_keys($updates))) {
                    if ($updates[$extension]['installation_key']) {
                        $update_now_url = $this->html->getSecureURL(
                            'tool/package_installer',
                            '&extension_key='.$updates[$extension]['installation_key']
                        );
                    } else {
                        $update_now_url = $updates[$extension]['url'];
                    }
                    $name = '<p class="alert-info">'.$name.'<br>'.sprintf(
                            $this->language->get('text_update_available'),
                            $updates[$extension]['version'],
                            $update_now_url
                        ).'</p>';
                    $push[] = $i;
                }
            }

            $response->rows[$i]['cell'] = [
                $icon,
                $extension,
                $name,
                $category,
                H::dateISO2Display($row['date_modified'], $this->language->get('date_format_short')),
            ];
            if (!$this->config->get('config_store_id')) {
                $response->rows[$i]['cell'][] = $row['store_name'] ? : $this->language->get('text_default');
            }
            $response->rows[$i]['cell'][] = $status;
            if ($push) {
                if (in_array($i, $push)) {
                    $for_push[] = $response->rows[$i];
                    unset($response->rows[$i]);
                }
            }

            $i++;
        }

        if ($push) {
            foreach ($for_push as $ext) {
                array_unshift($response->rows, $ext);
            }
        }

        $response->rows = array_slice($response->rows, (int) ($page - 1) * $limit, $limit);
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

        if (!$this->user->canModify('listing_grid/extension')) {
            $error = new AError('');
            $error->toJSONResponse(
                'NO_PERMISSIONS_403',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'listing_grid/extension'),
                    'reset_value' => true,
                ]
            );
            return;
        }

        $this->loadLanguage('extension/extensions');
        $store_id = $this->request->post_or_get('store_id');
        if ($this->session->data['current_store_id']) {
            $store_id = $this->session->data['current_store_id'];
        }

        if (empty($this->request->get['id'])) {
            foreach ($this->request->post as $ext => $val) {
                $val['store_id'] = $store_id;
                $this->extension_manager->editSetting($ext, $val);
            }
        } else {
            $val = $this->request->post;
            $val['store_id'] = $store_id;
            $val['one_field'] = true; // sign that we change only one setting
            $this->extension_manager->editSetting($this->request->get['id'], $val);
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        $this->load->library('json');
        if ($this->extension_manager->errors) {
            $error = new AError('');
            $error->toJSONResponse(
                'VALIDATION_ERROR_406',
                [
                    'error_text'  => '<br>'.implode('<br>', $this->extension_manager->errors),
                    'reset_value' => true,
                ]
            );
        }
    }

    public function license()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('extension/extensions');

        // check dependencies
        $config = H::getExtensionConfigXml($this->request->get['extension']);
        $result = $this->extension_manager->validateDependencies($this->request->get['extension'], $config);
        $this->data = ['license_text' => '', 'error_text' => ''];
        if ($result) {
            // if all fine show license agreement
            if (file_exists(ABC::env('DIR_EXT').$this->request->get['extension']."/license.txt")) {
                $this->data['license_text'] = file_get_contents(
                    ABC::env('DIR_EXT').$this->request->get['extension']."/license.txt"
                );
                $this->data['license_text'] = htmlentities(
                    $this->data['license_text'],
                    ENT_QUOTES,
                    ABC::env('APP_CHARSET')
                );
                $this->data['license_text'] = nl2br($this->data['license_text']);
            }
        } else {
            $error_text = $this->extension_manager->errors;
            end($error_text);
            $this->data['error_text'] = current($error_text);
        }
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data));
    }

    /**
     * method check is enabled dependants of extension presents
     */
    public function dependants()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('extension/extensions');

        $result = $this->extension_manager->getChildrenExtensions($this->request->get['extension']);
        if ($result) {
            $this->data['result'] = $result;
            $this->view->batchAssign($this->language->getASet('extension/extensions'));

            //update controller data
            $this->extensions->hk_UpdateData($this, __FUNCTION__);

            $this->view->batchAssign($this->data);
            $this->processTemplate('responses/extension/extensions_dependants_dialog.tpl');
        }
    }
}
