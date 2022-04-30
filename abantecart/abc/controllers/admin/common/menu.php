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
use abc\core\helper\AHelperHtml;
use abc\core\lib\ADataset;
use abc\core\lib\AResourceManager;

class ControllerCommonMenu extends AController
{
    protected $permissions = [];
    protected $groupID;
    const ROOT = 0;
    const TOP_ADMIN_GROUP = 1;

    public function main()
    {
        $this->loadLanguage('common/header');
        $menu = new ADataset ('menu', 'admin');
        $menu_items = $menu->getRows();

        $this->loadModel('user/user_group');
        $this->groupID = (int) $this->user->getUserGroupId();
        if ($this->groupID !== self::TOP_ADMIN_GROUP) {
            $user_group = $this->model_user_user_group->getUserGroup($this->groupID);
            $this->permissions = $user_group['permission'];
        }

        foreach ($menu_items as $item) {
            $this->data['menu_items'][] = $item;
        }

        //use to update data before render
        $this->extensions->hk_ProcessData($this);

        // need to resort by sort_order property and exclude disabled extension items
        $enabled_extension = $this->extensions->getEnabledExtensions();

        $tmp = [];
        foreach ($this->data['menu_items'] as $item) {
            $offset = 0;
            while ($offset < 20 || isset ($tmp [$item ['parent_id']] [$item ['sort_order'] + $offset])) {
                $offset++;
            }

            //checks for disabled extension
            if ($item ['item_type'] == 'extension') {
                // looks for this name in enabled extensions list. if is not there - skip it
                if (!$this->findItemIdInExtensions($item ['item_id'], $enabled_extension)) {
                    continue;
                } else { // if all fine - loads language of extension for menu item text show
                    if (!str_contains($item ['item_url'], 'http')) {
                        $this->loadLanguage($item ['item_id'].'/'.$item ['item_id'], 'silent');
                        $item['language'] = $item ['item_id'].'/'.$item ['item_id'];
                    }
                }
            }

            $tmp [$item ['parent_id']] [$item ['sort_order'] + $offset] = $item;
        }

        $this->data['menu_items'] = [];
        foreach ($tmp as $item) {
            ksort($item);
            $this->data['menu_items'] = array_merge($this->data['menu_items'], $item);
        }
        unset ($tmp);

        $this->view->assign(
            'menu_html',
            AHelperHtml::renderAdminMenu(
                $this->buildMenuArray($this->data['menu_items']),
                self::ROOT,
                $this->request->get_or_post('rt')
            )
        );
        $this->processTemplate('common/menu.tpl');
        //use to update data before render
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    private function findItemIdInExtensions($item_id, $extension_list)
    {
        if (in_array($item_id, $extension_list)) {
            return true;
        }
        foreach ($extension_list as $ext_id) {
            $pos = strpos($item_id, $ext_id);
            if ($pos === 0 && substr($item_id, strlen($ext_id), 1) == '_') {
                return true;
            }
        }
        return false;
    }

    private function buildMenuArray($menu_items = [])
    {
        $dashboard = [
            'dashboard' => [
                'icon' => '<i class="fa fa-home"></i>',
                'id'   => 'dashboard',
                'rt'   => 'index/home',
                'href' => $this->html->getSecureURL('index/home'),
                'text' => $this->language->get('text_dashboard'),
            ],
        ];

        return array_merge($dashboard, $this->getChildItems('', $menu_items));
    }

    private function getChildItems($item_id, $menu_items)
    {
        $rm = new AResourceManager();
        $rm->setType('image');
        $result = [];
        foreach ($menu_items as $item) {
            if ($item['parent_id'] == $item_id && isset($item['item_id'])) {
                if (isset($item ['language'])) {
                    $this->loadLanguage($item ['language'], 'silent');
                }
                $children = $this->getChildItems($item['item_id'], $menu_items);
                $rt = '';
                $menu_link = '';
                if (preg_match("/(http|https):/", $item['item_url'])) {
                    $menu_link = $item['item_url'];
                } else {
                    if ($item['item_url']) {
                        //rt based link, need to save rt
                        $menu_link = $this->html->getSecureURL($item['item_url'], '', true);
                        $rt = $item['item_url'];
                    }
                }

                $link_keyname = strpos($item ['item_url'], "http") ? "onclick" : "href";

                $icon = $rm->getResource($item ['item_icon_rl_id']);
                $icon = $icon['resource_code'] ? : '';

                $temp = [
                    'id'          => $item ['item_id'],
                    $link_keyname => $menu_link,
                    'text'        => $this->language->get($item ['item_text']),
                    'icon'        => $icon,
                ];

                if ($rt) {
                    $temp['rt'] = $rt;
                }

                if ($children) {
                    $temp['children'] = $children;
                } elseif ($this->groupID !== self::TOP_ADMIN_GROUP && !$this->permissions['access'][$rt]) {
                    //skip top menus with no access permission
                    continue;
                }

                $result[$item['item_id']] = $temp;
            }
        }
        return $result;
    }

    /**
     * @param string $rt
     *
     * @return false|string
     */
    protected function getControllerRt($rt)
    {
        if (!$rt || preg_match("/(http|https):/", $rt)) {
            return false;
        }
        $split = explode('/', $rt);

        return $split[0].'/'.$split[1];
    }
}
