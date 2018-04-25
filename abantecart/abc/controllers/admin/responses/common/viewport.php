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
use abc\core\engine\ADispatcher;
use abc\core\lib\AError;

if (!class_exists('abc\core\ABC') || !\abc\core\ABC::env('IS_ADMIN')) {
	header('Location: static_pages/?forbidden='.basename(__FILE__));
}
class ControllerResponsesCommonViewPort extends AController {

	public function main(){
		$this->modal();
	}

  	public function modal() {
        //init controller data
        $this->extensions->hk_InitData($this,__FUNCTION__);

	    $page_rt = $this->request->get['viewport_rt'];

	    $output = '';
	    if( $page_rt ){
	    	//make sure we have page controller explicitly, we have passed router already
		    $page_rt = preg_replace('/^p\//', '', $page_rt);
		    //send viewport mode via arguments to use it for template selection
		    $dd = new ADispatcher('pages/'.$page_rt, array(array('viewport_mode' => 'modal')) );
			//return output to view port
			$this->response->setOutput($dd->dispatchGetOutput($page_rt));
	    }else{
	    	//Missing RT
	    	$error = 'Viewport Router Error! Request Params are: '.var_export($this->request->get, true);
		    $err = new AError($error);
		    $err->toLog()->toDebug();
		    //show error in modal
	    	$this->view->assign('title',"Error!");
			$message_link = $this->html->getSecureURL('tool/message_manager');
			$logs_link = $this->html->getSecureURL('tool/error_log');    	
	    	$this->view->assign('content', sprintf($this->language->get('text_system_error'), $message_link, $logs_link));
	    	$this->processTemplate('responses/common/viewport_modal.tpl');
	    }
  	}
}