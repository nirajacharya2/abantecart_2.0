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

/**
 * Class ControllerResponsesToolPackageDownload
 * @package abc\controllers\admin
 */
class ControllerResponsesToolPackageDownload extends AController {
    private $error = array();

    public function main() {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        ini_set('max_execution_time', 200);
        $this->loadModel('tool/package_installer');
        $result = $this->model_tool_package_installer->downloadPackage();
        if ($result === false) {
            $message = $this->model_tool_package_installer->error;
            $this->session->data['error'] = $message;
            $this->log->write($message);
        }
        $this->response->setOutput($result);

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}