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
use abc\core\engine\AHtml;
use abc\core\engine\Extension;
use abc\core\engine\Registry;
use abc\core\lib\ALanguageManager;
use abc\core\lib\ALayoutManager;
use abc\core\lib\ARequest;

/**
 * Class ExtensionFormsManager
 *
 * @property ALanguageManager $language
 * @property AHtml $html
 * @property ARequest $request
 */
class ExtensionFormsManager extends Extension
{

    public $errors = [];
    public $data = [];
    protected $registry;

    public function __construct()
    {
        $this->registry = Registry::getInstance();
    }

    public function __get( $key )
    {
        return $this->registry->get( $key );
    }

    public function onControllerResponsesListingGridBlocksGrid_UpdateData()
    {

        if ( $this->baseObject_method != 'block_info' ) {
            return;
        }

        if ( $this->baseObject->data['block_txt_id'] == 'custom_form_block' ) {
            $this->baseObject->data['block_edit_brn'] = $this->baseObject->html->buildButton(
                [
                    'type'   => 'button',
                    'name'   => 'btn_edit',
                    'id'     => 'btn_edit',
                    'text'   => $this->baseObject->language->get( 'text_edit' ),
                    'href'   => $this->baseObject->html->getSecureURL(
                        'design/blocks/edit',
                        '&custom_block_id='.$this->baseObject->data['custom_block_id']
                    ),
                    'target' => '_new',
                    'style'  => 'button1',
                ]);
            $this->data['allow_edit'] = 'true';
        }
    }

    public function onControllerPagesDesignBlocks_InitData()
    {
        $block_txt_id = '';
        $this->baseObject->loadLanguage( 'forms_manager/forms_manager' );

        if ( $this->baseObject_method == 'edit' ) {
            $lm = new ALayoutManager();
            $blocks = $lm->getAllBlocks();

            foreach ($blocks as $block ) {
                if ( $block['custom_block_id'] == (int)$this->request->get['custom_block_id'] ) {
                    $block_txt_id = $block['block_txt_id'];
                    break;
                }
            }

            if ( $block_txt_id == 'custom_form_block' ) {
                abc_redirect(
                    $this->html->getSecureURL(
                        'tool/forms_manager/edit_block',
                        '&custom_block_id='.(int)$this->request->get['custom_block_id']
                    )
                );
            }
        }
    }

    public function onControllerPagesDesignBlocks_UpdateData()
    {
        $method_name = $this->baseObject_method;
        /** @var ControllerPagesDesignBlocks $that */
        $that = $this->baseObject;
        if ( $method_name != 'main' ) {
            return;
        }
        $lm = new ALayoutManager();
        $block = $lm->getBlockByTxtId( 'custom_form_block' );
        $block_id = $block['block_id'];

        $inserts = $that->view->getData( 'inserts' );
        $inserts[] = [
            'text' => $that->language->get( 'custom_forms_block' ),
            'href' => $that->html->getSecureURL( 'tool/forms_manager/insert_block', '&block_id='.$block_id ),
        ];
        $that->view->assign( 'inserts', $inserts );
    }

    public function onControllerPagesExtensionBannerManager_UpdateData()
    {
        $block_txt_id = '';
        if ( $this->baseObject_method == 'edit' ) {
            $lm = new ALayoutManager();
            $blocks = $lm->getAllBlocks();

            foreach ($blocks as $block ) {
                if ( $block['custom_block_id'] == (int)$this->request->get['custom_block_id'] ) {
                    $block_txt_id = $block['block_txt_id'];
                    break;
                }
            }

            if ( $block_txt_id == 'custom_form_block' ) {
                abc_redirect( $this->html->getSecureURL( 'tool/forms_manager/edit_block', '&custom_block_id='.(int)$this->request->get['custom_block_id'] ) );
            }
        }
    }

    public function onControllerResponsesCommonTabs_InitData()
    {
        /** @var ControllerResponsesCommonTabs $that */
        $that = $this->baseObject;
        if ($that->group == 'block' && !$that->request->get['custom_block_id']) {
            $lm = new ALayoutManager();
            $that->loadLanguage('forms_manager/forms_manager');
            $that->loadLanguage('design/blocks');
            $block = $lm->getBlockByTxtId('custom_form_block');
            $block_id = $block['block_id'];
            $that->data['tabs'][] = [
                'name'       => $block_id,
                'text'       => $that->language->get('custom_forms_block'),
                'href'       => $that->html->getSecureURL('tool/forms_manager/insert_block', '&block_id=' . $block_id),
                'active'     => $block_id == $that->request->get['block_id'],
                'sort_order' => 4,
            ];
        }
    }
}