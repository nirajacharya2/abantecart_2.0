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

namespace install\controllers;

use abc\core\ABC;
use abc\core\engine\AController;

/**
 * Class ControllerPagesLicense
 *
 * @package install\controllers
 */
class ControllerPagesLicense extends AController
{
    public $error = [];

    public function main()
    {
        //check is cart already installed
        if(is_file(ABC::env('DIR_CONFIG').'enabled.config.php')){
            abc_redirect(ABC::env('HTTPS_SERVER').'index.php?rt=finish');
        }

        $this->session->clear();
        $error = false;
        if ($this->request->is_POST() && ($this->validate())) {
            abc_redirect(ABC::env('HTTPS_SERVER').'index.php?rt=settings');
        }

        if (isset($this->error['warning'])) {
            $template_data['error_warning'] = $this->error['warning'];
        } else {
            $template_data['error_warning'] = '';
        }
        $this->view->assign('error_warning', $template_data['error_warning']);
        $this->view->assign('action', ABC::env('HTTPS_SERVER').'index.php?rt=license');
        if(is_file(ABC::env('DIR_VENDOR').'autoload.php')) {
            $text = nl2br(file_get_contents('../license.txt'));
            $this->view->assign('text', $text);
        }else{
            $error = true;
            $this->view->assign('error','not-initiated');
        }

        $this->view->assign('checkbox_agree', $this->html->buildCheckbox(
                array(
                    'name'     => 'agree',
                    'value'    => '',
                    'attr'     => '',
                    'required' => '',
                    'form'     => 'form',
                )
            )
        );

        $this->addChild('common/header', 'header', 'common/header.tpl');
        $this->addChild('common/footer', 'footer', 'common/footer.tpl');

        if($error) {
            //show message about composer
            $tpl = 'pages/initiate.tpl';
        }else{
            $tpl = 'pages/license.tpl';
        }
        $this->processTemplate($tpl);
    }

    private function validate()
    {
        if ( ! isset($this->request->post['agree'])) {
            $this->error['warning'] = 'You must agree to the license before you can install AbanteCart!';
        }

        if ( ! $this->error) {
            return true;
        } else {
            return false;
        }
    }
}
