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

namespace abc\modules\traits;

use abc\controllers\admin\ControllerResponsesCommonTabs;
use abc\core\lib\ALayoutManager;
use H;

trait BlockTabsTrait
{
    public $custom_block_types = ['html_block', 'listing_block', 'menu_nav'];

    public function getTabs($tabs = [], $active = '')
    {
        if (!$tabs) {
            $this->load->language('design/blocks');

            $blocks = [];
            $lm = new ALayoutManager();
            $default_block_type = '';
            foreach ($this->custom_block_types as $txt_id) {
                $block = $lm->getBlockByTxtId($txt_id);
                if ($block['block_id']) {
                    $blocks[$block['block_id']] = $this->language->get('text_' . $txt_id);
                }
                if ($txt_id == 'html_block') {
                    $default_block_type = $block['block_id'];
                }
            }

            $this->request->get['block_id'] = (int)$this->request->get['block_id'] ?: $default_block_type;
            $i = 0;
            foreach ($blocks as $block_id => $block_text) {
                $this->data['tabs'][] = [
                    'text'       => $block_text,
                    'href'       => $this->html->getSecureURL('design/blocks/insert', '&block_id=' . $block_id),
                    'active'     => $block_id == $this->request->get['block_id'],
                    'sort_order' => $i,
                ];
                $i++;
            }

        } else {
            $this->data['tabs'] = $tabs;
        }
        $obj = $this->dispatch(
        /** @see ControllerResponsesCommonTabs */
            'responses/common/tabs',
            [
                'block',
                $this->rt(),
                //parent controller. Use customer to use for other extensions that will add tabs via their hooks
                ['tabs' => $this->data['tabs']],
            ]
        );
        $this->data['tabs'] = $obj->dispatchGetOutput();
        return $this->data['tabs'];
    }
}