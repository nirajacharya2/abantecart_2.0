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

class ControllerPagesCatalogCategoryTabs extends AController
{

    public function main($data = [])
    {
        //Load input arguments for gid settings
        $this->data = $data;
        if (!is_array($this->data)) {
            throw new AException (
                'Error: Could not create grid. Grid definition is not array.',
                AC_ERR_LOAD
            );
        }
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('catalog/category');
        $category_id = $this->data['category_id'];

        $groups = [
            'general' => 'catalog/category/update',
            'data'    => 'catalog/category/update',
            'layout'  => 'catalog/category/edit_layout',
        ];

        foreach ($groups as $group => $group_rt) {
            $text_key = 'tab_'.$group;
            $this->data['groups'][$group] = [
                'text' => $this->language->get($text_key),
                'href' => $this->html->getSecureURL(
                        $group_rt,
                        '&category_id='.$category_id
                    ).'#'.$group,
            ];
        }

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->view->batchAssign($this->data);
        $this->processTemplate('common/tabs.tpl');
    }
}

