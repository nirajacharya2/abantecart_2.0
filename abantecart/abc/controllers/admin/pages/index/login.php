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
use abc\core\engine\AForm;
use abc\core\helper\AHelperSystemCheck;
use H;

class ControllerPagesIndexLogin extends AController
{
    private $error = [];
    public $data = [];

    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('common/login');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->document->addBreadcrumb(
            [
                'href'      => '',
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ]
        );
        $this->document->addBreadcrumb(
            [
                'href'     => $this->html->getSecureURL('index/login'),
                'text'     => $this->language->get('heading_title'),
                'current'  => true,
                'sub_text' => '',
                'icon'     => '' //need getMenuIconByRT method
            ]
        );

        if ($this->request->is_POST() && $this->validate()) {
            $this->session->data['token'] = H::genToken(32);
            // sign to run ajax-request to check for updates. see common/head for details
            $this->session->data['checkupdates'] = true;
            //login is successful redirect to originally requested page
            if (isset($this->request->post['redirect'])
                && !preg_match("/rt=index\/login/i", $this->request->post['redirect'])
            ) {
                $redirect = $this->html->filterQueryParams($this->request->post['redirect'], ['token']);
                $redirect .= "&token=".$this->session->data['token'];
                abc_redirect($redirect);
            } else {
                abc_redirect($this->html->getSecureURL('index/home'));
            }
        }

        if ( (isset($this->session->data['token']) && !isset($this->request->get['token']))
             || ((isset($this->request->get['token'])
                  && (isset($this->session->data['token'])
                    && ($this->request->get['token'] != $this->session->data['token'])
                     )
                ))
        ) {
            $this->error['warning'] = $this->language->get('error_token');
        }

        $this->data['action'] = $this->html->getSecureURL('index/login');
        $this->data['update'] = '';
        $form = new AForm('ST');

        $form->setForm(
            [
                'form_name' => 'loginFrm',
                'update'    => $this->data['update'],
            ]
        );

        $this->data['form']['id'] = 'loginFrm';
        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'loginFrm',
                'action' => $this->data['action'],
            ]
        );
        $this->data['form']['submit'] = $form->getFieldHtml(
            [
                'type'  => 'button',
                'name'  => 'submit',
                'text'  => $this->language->get('button_login'),
                'style' => 'button3',
            ]
        );

        $fields = ['admin_username', 'admin_password'];
        foreach ($fields as $f) {
            $this->data['form']['fields'][$f] = $form->getFieldHtml(
                [
                    'type'        => ($f == 'admin_password' ? 'password' : 'input'),
                    'name'        => $f,
                    'value'       => $this->data[$f],
                    'placeholder' => $this->language->get('entry_'.$f),
                ]
            );
        }

        //run critical system check
        $check_result = AHelperSystemCheck::run_critical_system_check($this->registry);

        if ($check_result) {
            $this->error['warning'] = '';
            foreach ($check_result as $log) {
                $this->error['warning'] .= $log['body']."\n";
            }
        }

        //non-secure check
        if (ABC::env('HTTPS') !== true
            && $this->config->get('config_ssl_url')
            && is_int(strpos($this->config->get('config_ssl_url'),'https://'))
        ) {
            $this->error['warning'] .= sprintf(
                $this->language->get('error_login_secure'),
                'https://'.ABC::env('REAL_HOST').ABC::env('HTTP_DIR_NAME').'/?s='.ABC::env('ADMIN_SECRET'));
        }

        $this->view->assign('error_warning', $this->error['warning']);
        $this->view->assign('forgot_password', $this->html->getSecureURL('index/forgot_password'));

        if (isset($this->request->get['rt'])) {
            $route = $this->request->get['rt'];
            unset($this->request->get['rt']);
            if (isset($this->request->get['token'])) {
                unset($this->request->get['token']);
            }
            $url = '';
            if ($this->request->get) {
                $url = '&'.http_build_query($this->request->get);
            }
            if ($this->request->is_POST()) {
                $this->view->assign('redirect',
                    $this->request->post['redirect']); // if login attempt failed - save path for redirect
            } else {
                $this->view->assign('redirect', $this->html->getSecureURL($route, $url));
            }
        } else {
            $this->view->assign('redirect', '');
        }

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/index/login.tpl');
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    private function validate()
    {
        if (isset($this->request->post['admin_username']) && isset($this->request->post['admin_password'])
            && !$this->user->login($this->request->post['admin_username'], $this->request->post['admin_password'])
        ) {
            $this->error['warning'] = $this->language->get('error_login');
        }
        if (!$this->error) {
            return true;
        } else {
            $this->messages->saveNotice(
                $this->language->get('error_login_message').$this->request->getRemoteIP(),
                $this->language->get('error_login_message_text').$this->request->post['admin_username']);
            return false;
        }
    }
}
