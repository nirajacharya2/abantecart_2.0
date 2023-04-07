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

namespace abc\controllers\storefront;

use abc\core\engine\AController;
use abc\core\engine\AResource;
use abc\extensions\banner_manager\models\Banner;
use abc\models\layout\CustomList;
use H;

/**
 * Class ControllerBlocksBannerBlock
 *
 */
class ControllerBlocksBannerBlock extends AController
{

    public function __construct($registry, $instance_id, $controller, $parent_controller = '')
    {
        parent::__construct($registry, $instance_id, $controller, $parent_controller);
        $this->data['search_parameters'] = [
            'store_id' => $this->config->get('config_store_id'),
        ];
    }

    public function main($instance_id = 0, $custom_block_id = 0)
    {
        //load JS to register clicks
        if (!$this->config->get('banner_manager_disable_statistic')) {
            $this->document->addScriptBottom($this->view->templateResource('assets/js/banner_manager.js'));
        }

        $this->data['block_data'] = $this->getBlockContent($instance_id, $custom_block_id);

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->view->assign('block_framed', $this->data['block_data']['block_framed']);

        $this->view->assign('content', $this->data['block_data']['content']);
        $this->view->assign('heading_title', $this->data['block_data']['title']);
        $this->view->assign('stat_url', $this->html->getURL('r/extension/banner_manager'));

        if ($this->data['block_data']['content']) {
            // need to set wrapper for non products listing blocks
            if ($this->view->isTemplateExists($this->data['block_data']['block_wrapper'])) {
                $this->view->setTemplate($this->data['block_data']['block_wrapper']);
            }
            $this->processTemplate();
        }
        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function getBlockContent($instance_id, $custom_block_id = 0)
    {
        if ($instance_id) {
            $block_info = $this->layout->getBlockDetails($instance_id);
            $custom_block_id = $block_info['custom_block_id'];
        }

        $blockDesc = $this->layout->getBlockDescriptions($custom_block_id);

        $languageId = $this->config->get('storefront_language_id');
        $languageId = !isset($blockDesc[$languageId]) ? key($blockDesc) : $languageId;

        $this->data['search_parameters']['language_id'] = $languageId;


        $content = $blockDesc[$languageId]['content'];
        $content = H::is_serialized($content) ? unserialize($content) : (array)$content;

        if ($content) {
            $this->data['search_parameters']['filter']['banner_group_name'] = $content['banner_group_name'];
        }

        $this->data['search_parameters']['filter']['include'] = CustomList::where('data_type', '=', 'banner_id')
            ->where('custom_block_id', '=', $custom_block_id)
            ->get()?->pluck('id')->toArray();

        $results = Banner::getBanners($this->data['search_parameters'])?->toArray();
        $banners = [];
        if ($results) {
            $rl = new AResource('image');
            foreach ($results as $k => $row) {
                $banners[$k] = $row;
                if ($row['banner_type'] == 1) { // if graphic type
                    $banners[$k]['images'] = $rl->getResourceAllObjects('banners', $row['banner_id']);
                    //add click registration wrapper to each URL
                    //NOTE: You can remove below line to use tracking javascript instead. Javascript tracks HTML banner clicks
                    $banners[$k]['original_url'] = $row['target_url'];
                    $banners[$k]['target_url'] = $this->html->getURL(
                        'r/extension/banner_manager/click',
                        '&banner_id=' . $row['banner_id'],
                        true
                    );
                } else {
                    $banners[$k]['description'] = html_entity_decode($row['description']);
                }
            }
        }


        return [
            'title'         => $blockDesc[$languageId]['title'] ?: '',
            'content'       => $banners,
            'block_wrapper' => $blockDesc[$languageId]['block_wrapper'] ?: 0,
            'block_framed'  => (int)$blockDesc[$languageId]['block_framed']
        ];
    }
}