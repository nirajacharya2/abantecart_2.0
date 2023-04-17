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

namespace abc\core\extension;

use abc\controllers\admin\ControllerPagesDesignBlocks;
use abc\controllers\admin\ControllerResponsesCommonTabs;
use abc\core\engine\Extension;
use abc\core\engine\Registry;
use abc\core\lib\ALayoutManager;
use abc\models\catalog\ResourceMap;

class ExtensionBannerManager extends Extension
{

    public function onControllerResponsesCommonResourceLibrary_InitData()
    {
        $this->baseObject->loadLanguage('banner_manager/banner_manager');
    }

    public function onControllerResponsesCommonResourceLibrary_UpdateData()
    {
        if ($this->baseObject_method == 'main') {
            $resource = &$this->baseObject->data['resource'];
            $result = $this->_getResourceBanners($resource['resource_id'], $resource['language_id']);
            if ($result) {
                $key = Registry::language()->get('text_banners');
                $key = $key ?: 'banners';
                $resource['resource_objects'][$key] = $result;
            }
        }
    }

    protected function _getResourceBanners(int $resource_id, ?int $language_id = 0)
    {

        if (!$language_id) {
            $language_id = Registry::language()->getContentLanguageID();
        }

        $resourceObjects = ResourceMap::where('resource_map.resource_id', '=', $resource_id)
            ->where('resource_map.object_name', '=', 'banners')
            ->select(['resource_map.object_id', 'banner_descriptions.name'])
            ->selectRaw("'banners' AS object_name")
            ->leftJoin(
                'banner_descriptions',
                function ($join) use ($language_id) {
                    $join->on(
                        'resource_map.object_id',
                        '=',
                        'banner_descriptions.banner_id'
                    )->where(
                        'banner_descriptions.language_id',
                        '=',
                        $language_id
                    );
                }
            )
            ->useCache('banner')
            ->get()?->toArray();

        $result = [];
        foreach ($resourceObjects as $row) {
            $result[] = [
                'object_id'   => $row['object_id'],
                'object_name' => $row['object_name'],
                'name'        => $row['name'],
                'url'         => Registry::html()->getSecureURL(
                    'extension/banner_manager/edit',
                    '&banner_id=' . $row['object_id']
                ),
            ];
        }

        return $result;
    }

    public function onControllerPagesDesignBlocks_InitData()
    {
        /** @var ControllerPagesDesignBlocks $that */
        $that = $this->baseObject;
        if ($this->baseObject_method !== 'edit') {
            return;
        }
        $that->loadLanguage('banner_manager/banner_manager');
        $lm = new ALayoutManager();
        $blocks = $lm->getAllBlocks();
        $block_txt_id = '';
        foreach ($blocks as $block) {
            if ($block['custom_block_id'] == (int)$that->request->get['custom_block_id']) {
                $block_txt_id = $block['block_txt_id'];
                break;
            }
        }

        if ($block_txt_id == 'banner_block') {
            abc_redirect(
                $that->html->getSecureURL(
                    'extension/banner_manager/edit_block',
                    '&custom_block_id=' . (int)$that->request->get['custom_block_id']
                )
            );
        }
    }

    public function onControllerPagesDesignBlocks_UpdateData()
    {
        $method_name = $this->baseObject_method;
        $that = $this->baseObject;
        if ($method_name != 'main') {
            return;
        }
        $lm = new ALayoutManager();
        $block = $lm->getBlockByTxtId('banner_block');
        $block_id = $block['block_id'];

        $inserts = $that->view->getData('inserts');
        $inserts[] = [
            'text' => $that->language->get('text_banner_block', 'banner_manager/banner_manager'),
            'href' => $that->html->getSecureURL('extension/banner_manager/insert_block', '&block_id=' . $block_id),
        ];
        $that->view->assign('inserts', $inserts);
    }

    public function onControllerResponsesCommonTabs_InitData()
    {
        /** @var ControllerResponsesCommonTabs $that */
        $that = $this->baseObject;

        if ($that->group == 'block' && !$that->request->get['custom_block_id']) {
            $lm = new ALayoutManager();
            $that->loadLanguage('banner_manager/banner_manager');
            $that->loadLanguage('design/blocks');
            $block = $lm->getBlockByTxtId('banner_block');
            $block_id = $block['block_id'];
            $that->data['tabs'][] = [
                'name'       => $block_id,
                'text'       => $that->language->get('text_banner_block'),
                'href'       => $that->html->getSecureURL(
                    'extension/banner_manager/insert_block',
                    '&block_id=' . $block_id
                ),
                'active'     => $block_id == $that->request->get['block_id'],
                'sort_order' => 3,
            ];
        }
    }
}