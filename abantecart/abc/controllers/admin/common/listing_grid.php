<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2021 Belavier Commerce LLC

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
use abc\core\lib\AException;

class ControllerCommonListingGrid extends AController
{
    public function main($data = [])
    {
        //Load input arguments for gid settings
        $this->data = $data;
        if (!is_array($this->data)) {
            throw new AException(
                'Error: Could not create grid. Grid definition is not array.',
                AC_ERR_LOAD
            );
        }
        //use to init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        //Do not load scripts multiple times

        $this->data['locale'] = $this->session->data['language'];

        $this->data['update_field'] = $this->data['update_field'] ?? '';
        $this->data['editurl'] = $this->data['editurl'] ?? '';
        $this->data['rowNum'] = $this->data['rowNum'] ? : (int) $this->config->get('config_admin_limit');
        $this->data['rowNum'] = $this->data['rowNum'] ? : 10;
        if (empty($this->data['rowList'])) {
            $this->data['rowList'] = [
                $this->data['rowNum'],
                $this->data['rowNum'] + 20,
                $this->data['rowNum'] + 40,
            ];
        }

        $this->data['multiselect'] = $this->data['multiselect'] ? : "true";
        $this->data['multiaction'] = $this->data['multiaction'] ? : "true";
        $this->data['hoverrows'] = $this->data['hoverrows'] ? : "true";
        $this->data['altRows'] = $this->data['altRows'] ? : "true";
        $this->data["sortorder"] = $this->data["sortorder"] ? : "desc";
        $this->data["columns_search"] = $this->data["columns_search"] ?? true;
        $this->data["search_form"] = $this->data["search_form"] ?? false;
        // add custom buttons to jqgrid "pager" area
        if ($this->data['custom_buttons']) {
            $custom_buttons = [];
            $i = 0;
            foreach ($this->data['custom_buttons'] as $button) {
                if (!$button['caption']) {
                    continue;
                }
                $button['buttonicon'] = $button['buttonicon'] ? : 'ui-icon-newwin';
                $button['onClickButton'] = $button['onClickButton'] ? : 'null';
                $button['position'] = $button['position'] ? : 'last';
                $button['cursor'] = $button['cursor'] ? : 'pointer';
                $custom_buttons[$i] = $button;
                $i++;
            }
            $this->view->assign('custom_buttons', $custom_buttons);
        }
        // add action columns in case actions are defined
        if (!empty($this->data['actions'])) {
            $this->data['colNames'][] = $this->language->get('column_action');
            $this->data['colModel'][] = [
                'name'     => 'action',
                'index'    => 'action',
                'align'    => 'center',
                'sortable' => false,
                'search'   => false,
            ];
        }

        //check for reserved column "name"
        // name "parent" broke expanding of grid tree
        foreach ($this->data['colModel'] as $col) {
            if ($col['name'] == 'parent') {
                throw new AException (
                    AC_ERR_LOAD,
                    'Error: Could not create grid. Grid column model '
                    .'contains reserved column name ("'.$col['index'].'").'
                );
            }
        }

        $this->view->assign('data', $this->data);

        $this->view->assign('text_delete_confirm', $this->language->get('text_delete_confirm'));
        $this->view->assign('text_choose_action', $this->language->get('text_choose_action'));
        if (!$this->data['multiaction_options']) {
            $multiaction_options['delete'] = $this->language->get('text_delete_selected');
            $multiaction_options['save'] = $this->language->get('text_save_selected');
        } else {
            $multiaction_options = $this->data['multiaction_options'];
        }

        $this->view->assign('text_go', $this->language->get('button_go'));
        $this->view->assign('multiaction_options', $multiaction_options);
        $this->view->assign('history_mode', $this->data['history_mode'] ?? true);
        $this->view->assign('init_onload', $this->data['init_onload'] ?? true);
        $this->view->assign('text_save_all', $this->language->get('text_save_all'));
        $this->view->assign('text_select_items', $this->language->get('text_select_items'));
        $this->view->assign('text_no_results', $this->language->get('text_no_results'));
        $this->view->assign('text_all', $this->language->get('text_all'));
        $this->view->assign('text_select_from_list', $this->language->get('text_select_from_list'));
        $this->processTemplate('common/listing_grid.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}