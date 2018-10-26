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

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\lib\ADebug;

class ControllerCommonHead extends AController
{
    public $data = [];

    public function main()
    {

        //use to init controller data
        $this->extensions->hk_InitData( $this, __FUNCTION__ );

        $this->load->helper( 'html' );
        $this->loadLanguage( 'common/header' );

        /**
         * @var \DebugBar\StandardDebugBar $debug_bar
         */
        $debug_bar = ADebug::$debug_bar;
        if ( $debug_bar ) {
            $debugbar_assets = ADebug::getDebugBarAssets();
            $dbg_js_set = $debugbar_assets['js'];
            $dbg_css_set = $debugbar_assets['css'];
            foreach ( $dbg_css_set as $src ) {
                $this->document->addStyle(
                    [
                        // remove forward slash
                        'href'  => substr( $src, 1 ),
                        'rel'   => 'stylesheet',
                        'media' => 'screen',
                    ]
                );
            }
            foreach ( $dbg_js_set as $src ) {
                $this->document->addScript( substr( $src, 1 ) );
            }
        }

        $message_link = $this->html->getSecureURL( 'tool/message_manager' );

        $this->data['title'] = $this->document->getTitle();
        $this->data['base'] = ( ABC::env( 'HTTPS_SERVER' ) ? ABC::env( 'HTTPS_SERVER' ) : ABC::env( 'HTTP_SERVER' ) );
        $this->data['links'] = $this->document->getLinks();
        $this->data['styles'] = $this->document->getStyles();
        $this->data['scripts'] = $this->document->getScripts();
        if ($this->user->getUserGroupId() == 1) {
            $this->data['notifier_updater_url'] = $this->html->getSecureURL( 'listing_grid/message_grid/getnotifies' );
            $this->data['system_checker_url'] = $this->html->getSecureURL('common/common/checksystem');
            if ( $this->session->data['checkupdates'] ) {
                $this->data['check_updates_url'] = $this->html->getSecureURL( 'r/common/common/checkUpdates' );
            }
        }
        $this->data['language_code'] = $this->session->data['language'];
        $this->data['language_details'] = $this->language->getCurrentLanguage();
        $locale = explode( '.', $this->data['language_details']['locale'] );
        $this->data['language_locale'] = $locale[0];

        $retina = $this->config->get( 'config_retina_enable' );
        $this->data['retina'] = $retina;
        //remove cookie for retina
        if ( ! $retina ) {
            $this->request->deleteCookie( 'HTTP_IS_RETINA' );
        }

        $this->data['message_manager_url'] = $message_link;

        $this->data['icon'] = $this->config->get( 'config_icon' );

        if ( ABC::env( 'HTTPS' ) ) {
            $this->data['ssl'] = 1;
        }

        $this->view->batchAssign( $this->data );
        $this->processTemplate( 'common/head.tpl' );

        //update controller data
        $this->extensions->hk_UpdateData( $this, __FUNCTION__ );
    }
}