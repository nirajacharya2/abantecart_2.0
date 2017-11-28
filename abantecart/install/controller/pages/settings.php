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
use abc\ABC;
use abc\core\engine\AController;

/**
 * Class ControllerPagesSettings
 * @property ModelInstall $model_install
 */
class ControllerPagesSettings extends AController{
	private $error = array ();

	public function main(){
		$template_data = array ();
		if ($this->request->is_POST() && ($this->validate())){
			$this->redirect(ABC::env('HTTP_SERVER') . 'index.php?rt=install');
		}

		if (isset($this->error['warning'])){
			$template_data['error_warning'] = $this->error['warning'];
		} else{
			$template_data['error_warning'] = '';
		}

		//show warning about opcache and apc but do not block installation
		if (ini_get('opcache.enable')){
			if ($template_data['error_warning']){
				$template_data['error_warning'] .= '<br>';
			}
			$template_data['error_warning'] .= 'Warning: Your server have opcache php module enabled. Please disable it before installation!';
		}
		if (ini_get('apc.enabled')){
			if ($template_data['error_warning']){
				$template_data['error_warning'] .= '<br>';
			}
			$template_data['error_warning'] .= 'Warning: Your server have APC (Alternative PHP Cache) php module enabled. Please disable it before installation!';
		}

		$template_data['action'] = ABC::env('HTTP_SERVER') . 'index.php?rt=settings';
		$template_data['config_catalog'] = ABC::env('DIR_CONFIG') . 'config.php';
		$template_data['system'] = ABC::env('DIR_SYSTEM');
		$template_data['cache'] = ABC::env('DIR_SYSTEM') . 'cache';
		$template_data['logs'] = ABC::env('DIR_SYSTEM') . 'logs';
		$template_data['image'] = ABC::env('DIR_ABANTECART') . 'image';
		$template_data['image_thumbnails'] = ABC::env('DIR_ABANTECART') . 'images/thumbnails';
		$template_data['download'] = ABC::env('DIR_ABANTECART') . 'download';
		$template_data['extensions'] = ABC::env('DIR_ABANTECART') . 'extensions';
		$template_data['resources'] = ABC::env('DIR_ABANTECART') . 'resources';
		$template_data['admin_system'] = ABC::env('DIR_ABANTECART') . 'admin/system';

		$this->addChild('common/header', 'header', 'common/header.tpl');
		$this->addChild('common/footer', 'footer', 'common/footer.tpl');

		$this->view->assign('back', ABC::env('HTTP_SERVER') . 'index.php?rt=license');
		$this->view->batchAssign($template_data);
		$this->processTemplate('pages/settings.tpl');
	}

	/**
	 * @return bool
	 */
	public function validate(){
		$this->load->model('install');
		$result = $this->model_install->validateRequirements();
		if(!$result){
			$this->error = $this->model_install->errors;
		}
		return $result;
	}
}
