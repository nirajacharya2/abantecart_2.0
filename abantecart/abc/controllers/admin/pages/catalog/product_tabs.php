<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2018 Belavier Commerce LLC

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

class ControllerPagesCatalogProductTabs extends AController
{
    public $data = [];

    public function main()
    {
        //Load input arguments for gid settings
        $this->data = func_get_arg(0);
        if (!is_array($this->data)) {
            throw new AException (
                'Error: Could not create tabs. Tab definition is not array.',
                AC_ERR_LOAD
            );
        }
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('catalog/product');
        $product_id = $this->request->get['product_id'];
        $product_id = !$product_id && $this->data['product_id'] ? $this->data['product_id'] : $product_id;

        $groups = [
            'general'    => 'catalog/product/update',
            'images'      => 'catalog/product_images',
            'options'     => 'catalog/product_options',
            'files'      => 'catalog/product_files',
            'relations'  => 'catalog/product_relations',
            'promotions' => 'catalog/product_promotions',
           // 'extensions' => 'catalog/product_extensions',
            'layout'     => 'catalog/product_layout'
        ];

        foreach($groups as $group => $group_rt){
            $text_key = 'tab_'.$group;
            $text_key = $group=='images' ? 'tab_media' : $text_key;
            $text_key = $group=='options' ? 'tab_option' : $text_key;
            $this->data['groups'][$group] = [
                                'text' => $this->language->get($text_key),
                                'href' => $this->html->getSecureURL($group_rt, '&product_id='.$product_id)
            ];
        }
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->view->batchAssign($this->data);
        $this->processTemplate('common/tabs.tpl');
    }
}

