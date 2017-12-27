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
use abc\ABC;
use abc\core\engine\AController;

if (!class_exists('abc\ABC') || !\abc\ABC::env('IS_ADMIN')) {
	header('Location: static_pages/?forbidden='.basename(__FILE__));
}
class ControllerResponsesSettingTemplateImage extends AController {
	public function main() {
        //init controller data
        $this->extensions->hk_InitData($this,__FUNCTION__);
		$template = basename($this->request->get['template']);
		$extensions = $this->extensions->getEnabledExtensions();

        $file = $template . '/images/preview.jpg';
		if ( in_array( $template, $extensions ) && is_file( ABC::env('DIR_APP_EXTENSIONS') . $file) ) {
            $img = ABC::env('HTTPS_EXT') . $file;
        } else if (is_file( 'storefront/view/' . $template . '/images/preview.jpg')) {
			$img = ABC::env('HTTPS_SERVER') . 'storefront/view/' . $template . '/images/preview.jpg';
		} else {
			$img = ABC::env('HTTPS_IMAGE') . 'no_image.jpg';
		}

		$edit = $this->html->getSecureURL('design/template/edit', '&tmpl_id='.$template);
		$html = '<img src="' . $img . '" alt="" title="" />';
		$html .= '<a class="btn btn-default" href='.$edit.'><i class="template_edit fa fa-gear fa-fw fa-lg"></i> '.$this->language->get('text_edit').'</a>';  		

        //update controller data
        $this->extensions->hk_UpdateData($this,__FUNCTION__);
		$this->response->setOutput($html);

	}
}