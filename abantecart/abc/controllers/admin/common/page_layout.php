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

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\lib\AException;
use abc\core\lib\ALayoutManager;
use Exception;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

class ControllerCommonPageLayout extends AController
{
    protected $installed_blocks = [];

    /**
     * @param ALayoutManager $layout
     *
     * @return void
     * @throws ReflectionException
     * @throws AException|InvalidArgumentException
     */
    public function main($layout)
    {
        // use to init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (!$this->registry->has('layouts_manager_script')) {
            $this->document->addStyle(
                [
                    'href' => ABC::env('RDIR_ASSETS') . 'css/layouts-manager.css',
                    'rel'  => 'stylesheet',
                ]
            );

            $this->document->addScript(ABC::env('RDIR_ASSETS').'js/jquery/sortable.js');
            $this->document->addScript(ABC::env('RDIR_ASSETS').'js/layouts-manager.js');

            //set flag to not include scripts/css twice
            $this->registry->set('layouts_manager_script', true);
        }

        // build layout data from passed layout object
        $this->installed_blocks = $layout->getInstalledBlocks();

        $layout_main_blocks = $layout->getLayoutBlocks();

        // Build Page Sections and Blocks
        $page_sections = $this->_buildPageSections($layout_main_blocks);

        $this->view->batchAssign($page_sections);
        $this->processTemplate('common/page_layout.tpl');

        // update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    /**
     * @param array $sections
     *
     * @return array
     * @throws Exception|InvalidArgumentException
     */
    private function _buildPageSections($sections)
    {
        $page_sections = [];
        $partialView = $this->view;

        foreach ($sections as $section) {
            $blocks = $this->_buildBlocks($section['block_id'], $section['children']);

            $partialView->batchAssign(
                [
                    'id'          => $section['instance_id'],
                    'blockId'     => $section['block_id'],
                    'name'        => $section['block_txt_id'],
                    'status'      => $section['status'],
                    'controller'  => $section['controller'],
                    'blocks'      => implode('', $blocks),
                    'addBlockUrl' => $this->html->getSecureURL('design/blocks_manager'),
                ]
            );

            // render partial view
            $page_sections[$section['block_txt_id']] = $partialView->fetch('common/section.tpl');
        }

        return $page_sections;
    }

    /**
     * @param array $section_id
     * @param array $section_blocks
     *
     * @return array
     * @throws Exception|InvalidArgumentException
     */
    protected function _buildBlocks($section_id, $section_blocks)
    {
        $blocks = [];
        $partialView = $this->view;

        if (empty($section_blocks)) {
            return $blocks;
        }

        foreach ($section_blocks as $block) {
            $customName = $edit_url = '';
            $this->loadLanguage('design/blocks');

            if ($block['custom_block_id']) {
                $customName = $this->_getCustomBlockName($block['custom_block_id']);
                $edit_url = $this->html->getSecureURL(
                    'design/blocks/edit',
                    '&custom_block_id='.$block['custom_block_id']
                );
            }

            //if template for section/block is not present, block is not allowed here.
            $template_availability = true;
            if (!$block['template']) {
                $template_availability = false;
            }

            $partialView->batchAssign(
                [
                    'id'                    => $block['instance_id'],
                    'blockId'               => (int)$block['block_id'],
                    'customBlockId'         => (int)$block['custom_block_id'],
                    'name'                  => $block['block_txt_id'],
                    'customName'            => $customName,
                    'editUrl'               => $edit_url,
                    'status'                => $block['status'],
                    'parentBlock'           => $section_id,
                    'block_info_url'        => $this->html->getSecureURL('design/blocks_manager/block_info'),
                    'template_availability' => $template_availability,
                    'validate_url'          => $this->html->getSecureURL(
                        'design/blocks_manager/validate_block',
                        '&block_id='.$block['block_id']
                    ),
                ]
            );

            // render partial view
            $blocks[] = $partialView->fetch('common/block.tpl');
        }

        return $blocks;
    }

    /**
     * @param int $custom_block_id
     *
     * @return string
     */
    protected function _getCustomBlockName($custom_block_id)
    {
        foreach ($this->installed_blocks as $block) {
            if ($block['custom_block_id'] == $custom_block_id) {
                return $block['block_name'];
            }
        }
        return '';
    }
}