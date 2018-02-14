<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2017 Belavier Commerce LLC

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

if (!class_exists('abc\core\ABC') || !\abc\core\ABC::env('IS_ADMIN')) {
	header('Location: static_pages/?forbidden='.basename(__FILE__));
}
class ControllerResponsesCommonFormCollector extends AController
{
    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $form_id = func_get_arg(0);
        $target = func_get_arg(1);
		if(func_num_args()>2){
        	$success_script = func_get_arg(2);
		}else{
			$success_script = '';
		}

        $this->view->assign('form_id', $form_id);
        $this->view->assign('target', $target);
        $this->view->assign('success_script', $success_script);
        $this->processTemplate('responses/common/form_collector.tpl');
        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}