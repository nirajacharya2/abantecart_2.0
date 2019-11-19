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

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\engine\AForm;
use abc\core\lib\AConfigManager;
use H;

class ControllerPagesDesignTemplate extends AController
{
    public $data = [];
    public $error = [];
    /**
     * @var AConfigManager
     */
    private $conf_mngr;

    public function main()
    {
        //use to init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->addStyle([
            'href' => ABC::env('RDIR_ASSETS').'css/layouts-manager.css',
            'rel'  => 'stylesheet',
        ]);

        $this->document->setTitle($this->language->get('heading_title'));

        // breadcrumb path
        $this->document->initBreadcrumb([
            'href' => $this->html->getSecureURL('index/home'),
            'text' => $this->language->get('text_home'),
        ]);
        $this->document->addBreadcrumb([
            'href'    => $this->html->getSecureURL('design/template'),
            'text'    => $this->language->get('heading_title'),
            'current' => true,
        ]);

        $this->data['current_url'] = $this->html->getSecureURL('design/template');
        $this->data['form_store_switch'] = $this->html->getStoreSwitcher();
        $this->data['help_url'] = $this->gen_help_url('set_storefront_template');
        $this->loadLanguage('setting/setting');
        $this->data['manage_extensions'] = $this->html->buildElement(
            [
                'type'  => 'button',
                'name'  => 'manage_extensions',
                'href'  => $this->html->getSecureURL('extension/extensions/template'),
                'text'  => $this->language->get('button_manage_extensions'),
                'title' => $this->language->get('button_manage_extensions'),
            ]
        );

        $this->data['store_id'] = 0;
        if ($this->request->get['store_id']) {
            $this->data['store_id'] = $this->request->get['store_id'];
        } else {
            $this->data['store_id'] = $this->config->get('config_store_id');
        }
        //check if store is active
        $store_info = $this->model_setting_store->getStore($this->data['store_id']);
        $this->data['status_off'] = '';
        if (!$store_info['status']) {
            $this->data['status_off'] = 'status_off';
        }

        //check if we have developer tools installed
        $dev_tools = $this->extensions->getExtensionsList(['search' => 'developer_tools'])->row;

        // get templates
        $this->data['templates'] = [];

        $this->load->library('config_manager');
        $conf_mngr = new AConfigManager();

        //get all enabled templates
        $tmpls = $conf_mngr->getTemplates('storefront');
        $settings = $this->model_setting_setting->getSetting('appearance', $this->data['store_id']);
        $this->data['default_template'] = $settings['config_storefront_template'];
        $templates = [];

        foreach ($tmpls as $tmpl) {
            $templates[$tmpl] = [
                'name'          => $tmpl,
                'edit_url'      => $this->html->getSecureURL('design/template/edit', '&tmpl_id='.$tmpl),
                // define template type by directory inside core.
                // If does not exists - it is extension otherwise core-template
                'template_type' => (is_dir(ABC::env('DIR_TEMPLATES').$tmpl.ABC::env('DIRNAME_STORE'))
                                   ? 'core'
                                   : 'extension')
            ];

            //button for template cloning
            $target = '';
            if (is_null($dev_tools['status'])) {
                $href = $this->gen_help_url('template_dev_tools');
                $target = '_blank';
            } elseif ($dev_tools['status'] == 1) {
                $href = $this->html->getSecureURL('tool/developer_tools/create', '&template='.$tmpl);
            } else {
                $href = $this->html->getSecureURL('extension/extensions/edit', '&extension=developer_tools');
            }
            if ($templates[$tmpl]['template_type'] == 'core') {
                $templates[$tmpl]['clone_button'] = $this->html->buildElement(
                    [
                        'type'   => 'button',
                        'name'   => 'clone_button',
                        'href'   => $href,
                        'target' => $target,
                        'text'   => $this->language->get('text_clone_template'),
                    ]
                );
            }

            //button to extension
            if (is_dir(ABC::env('DIR_APP_EXTENSIONS').$tmpl)) {
                $templates[$tmpl]['extn_url'] = $this->html->getSecureURL(
                    'extension/extensions/edit',
                    '&extension='.$tmpl
                );
            }
            //set default
            if ($this->data['default_template'] != $tmpl) {
                $templates[$tmpl]['set_default_url'] = $this->html->getSecureURL(
                    'design/template/set_default',
                    '&tmpl_id='.$tmpl.'&store_id='.$this->data['store_id']
                );
            }

            $templates[$tmpl]['preview'] = $this->getPreviewUrl($tmpl);
        }

        $this->data['templates'] = $templates;

        // Alert messages
        if (isset($this->session->data['warning'])) {
            $this->data['error_warning'] = $this->session->data['warning'];
            unset($this->session->data['warning']);
        }
        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/design/template.tpl');
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function set_default()
    {
        //use to init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (!$this->user->canModify('design/template')) {
            $this->session->data['warning'] = $this->language->get('error_permission');
            abc_redirect($this->html->getSecureURL('design/template'));
        }

        $this->loadModel('setting/setting');

        if ($this->request->get['store_id']) {
            $store_id = (int)$this->request->get['store_id'];
        } else {
            $store_id = (int)$this->config->get('config_store_id');
        }

        if ($this->request->get['tmpl_id']) {
            $this->model_setting_setting->editSetting(
                'appearance',
                ['config_storefront_template' => $this->request->get['tmpl_id']],
                $store_id
            );
            $this->session->data['success'] = $this->language->get('text_success');
        } else {
            $this->session->data['warning'] = $this->language->get('text_error');
        }

        abc_redirect($this->html->getSecureURL('design/template'));

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function edit()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->setTitle($this->language->get('heading_title'));
        $tmpl_id = $this->request->get['tmpl_id'];
        $this->data['tmpl_id'] = $tmpl_id;
        $this->loadModel('setting/setting');
        $this->loadModel('setting/store');

        if (!$tmpl_id) {
            $this->session->data['warning'] = $this->language->get('text_error');
            abc_redirect($this->html->getSecureURL('design/template'));
        }
        $this->data['group'] = $this->data['tmpl_id'] == 'default' ? 'appearance' : $this->data['tmpl_id'];

        if ($this->request->is_POST() && $this->validate('appearance')) {
            $post = $this->request->post;
            if (H::has_value($post['config_logo'])) {
                $post['config_logo'] = html_entity_decode($post['config_logo'], ENT_COMPAT, ABC::env('APP_CHARSET'));
            } else {
                if (!$post['config_logo'] && isset($post['config_logo_resource_id'])) {
                    //we save resource ID vs resource path
                    $post['config_logo'] = $post['config_logo_resource_id'];
                }
            }
            if (H::has_value($post['config_mail_logo'])) {
                $post['config_mail_logo'] =
                    html_entity_decode($post['config_mail_logo'], ENT_COMPAT, ABC::env('APP_CHARSET'));
            } else {
                if (!$post['config_mail_logo'] && isset($post['config_mail_logo_resource_id'])) {
                    //we save resource ID vs resource path
                    $post['config_mail_logo'] = $post['config_mail_logo_resource_id'];
                }
            }
            if (H::has_value($post['config_icon'])) {
                $post['config_icon'] = html_entity_decode($post['config_icon'], ENT_COMPAT, ABC::env('APP_CHARSET'));
            } else {
                if (!$post['config_icon'] && isset($post['config_icon_resource_id'])) {
                    //we save resource ID vs resource path
                    $post['config_icon'] = $post['config_icon_resource_id'];
                }
            }

            $this->model_setting_setting->editSetting($this->data['group'], $post, $this->request->get['store_id']);

            $this->session->data['success'] = $this->language->get('text_success');

            $redirect_url = $this->html->getSecureURL('design/template/edit', '&tmpl_id='.$tmpl_id);
            abc_redirect($redirect_url);
        }

        $this->data['store_id'] = 0;
        if ($this->request->get['store_id']) {
            $this->data['store_id'] = $this->request->get['store_id'];
        } else {
            $this->data['store_id'] = $this->config->get('config_store_id');
        }

        $this->data['error'] = $this->error;

        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('design/template'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
            'current'   => true,
        ]);

        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }
        if (isset($this->session->data['error'])) {
            $this->error['warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        }

        $this->data['cancel'] = $this->html->getSecureURL('design/template/edit', '&tmpl_id='.$tmpl_id);
        $this->data['back'] = $this->html->getSecureURL('design/template');

        $this->load->library('config_manager');
        $this->conf_mngr = new AConfigManager();

        //set control buttons
        $tmpls = $this->conf_mngr->getTemplates('storefront');
        $templates = [];
        foreach ($tmpls as $tmpl) {
            //skip current template
            if ($tmpl != $tmpl_id) {
                $templates[$tmpl] = [
                    'name' => $tmpl,
                    'href' => $this->html->getSecureURL('design/template/edit', '&tmpl_id='.$tmpl),
                ];
            }
        }

        $this->data['templates'] = $templates;
        $this->data['current_template'] = $tmpl_id;
        $this->loadLanguage('setting/setting');
        //button for template cloning
        $dev_tools = $this->extensions->getExtensionsList(['search' => 'developer_tools'])->row;
        $target = '';
        if (is_null($dev_tools['status'])) {
            $href = $this->gen_help_url('template_dev_tools');
            $target = '_blank';
        } elseif ($dev_tools['status'] == 1) {
            $href = $this->html->getSecureURL('tool/developer_tools/create');
        } else {
            $href = $this->html->getSecureURL('extension/extensions/edit', '&extension=developer_tools');
        }
        //NOTE: need to show different icon and message if dev tools extension is not installed
        $this->data['clone_button'] = $this->html->buildElement(
            [
                'type'   => 'button',
                'name'   => 'clone_button',
                'href'   => $href,
                'target' => $target,
                'text'   => $this->language->get('text_clone_template'),
            ]
        );

        $this->data['settings'] = $this->model_setting_setting->getSetting(
            $this->data['group'],
            $this->data['store_id']
        );
        //remove sign of single form field
        unset($this->data['settings']['one_field']);

        foreach ($this->data['settings'] as $key => $value) {
            if (isset($this->request->post[$key])) {
                $this->data['settings'][$key] = $this->request->post[$key];
            }
        }
        //check if store is active
        $store_info = $this->model_setting_store->getStore($this->data['store_id']);
        $this->data['status_off'] = '';
        if (!$store_info['status']) {
            $this->data['status_off'] = 'status_off';
        }
        //check if template is used
        $settings = $this->model_setting_setting->getSetting('appearance', $this->data['store_id']);
        $this->data['default_template'] = $settings['config_storefront_template'];
        if ($this->data['default_template'] != $tmpl_id) {
            $this->data['status_off'] = 'status_off';
        }

        $this->getForm();



        $this->data['preview_img'] = $this->getPreviewUrl($tmpl_id);

        $this->view->assign('form_store_switch', $this->html->getStoreSwitcher());
        $this->view->assign('help_url', $this->gen_help_url('edit_storefront_template'));
        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/design/template_edit.tpl');

        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function getPreviewUrl($template)
    {
        $file1 = ABC::env('DIRNAME_TEMPLATES')
                .$template.DS
                .ABC::env('DIRNAME_STORE')
                .ABC::env('DIRNAME_ASSETS')
                .ABC::env('DIRNAME_IMAGES')
                .'preview.jpg';

        $file2 = $template.DS
                .ABC::env('DIRNAME_TEMPLATES')
                .$template.DS
                .ABC::env('DIRNAME_STORE')
                .ABC::env('DIRNAME_ASSETS')
                .ABC::env('DIRNAME_IMAGES')
                .'preview.jpg';

        if (is_file(ABC::env('DIR_PUBLIC').$file1)) {
            $preview_img = ABC::env('HTTPS_SERVER').$file1;
        } elseif (is_file(ABC::env('DIR_ASSETS_EXT') . $file2)) {
            $preview_img = ABC::env('AUTO_SERVER').ABC::env('DIRNAME_EXTENSIONS').$file2;
        } else {
            $preview_img = ABC::env('HTTPS_IMAGE').'no_image.jpg';
        }

        return $preview_img;
    }

    protected function getForm()
    {
        $this->data['action'] = $this->html->getSecureURL(
            'design/template/edit',
            '&tmpl_id='.$this->data['tmpl_id'].'&store_id='.$this->data['store_id']
        );
        $this->data['form_title'] = $this->language->get('text_edit').' '.$this->language->get('heading_title');
        $this->data['update'] = $this->html->getSecureURL('listing_grid/setting/update_field',
            '&group='.$this->data['group'].'&store_id='.$this->data['store_id'].'&tmpl_id='.$this->data['tmpl_id']);

        $form = new AForm('HS');
        $form->setForm([
            'form_name' => 'templateFrm',
            'update'    => $this->data['update'],
        ]);

        $this->data['form']['id'] = 'templateFrm';
        $this->data['form']['form_open'] = $form->getFieldHtml([
            'type'   => 'form',
            'name'   => 'templateFrm',
            'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
            'action' => $this->data['action'],
        ]);
        $this->data['form']['submit'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'submit',
            'text'  => $this->language->get('button_save'),
            'style' => 'button1',
        ]);
        $this->data['form']['cancel'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'cancel',
            'text'  => $this->language->get('button_cancel'),
            'style' => 'button2',
        ]);

        $this->data['settings']['tmpl_id'] = $this->data['tmpl_id'];
        if ($this->data['settings']['tmpl_id'] == 'default') {
            //get default setting fields for template
            $this->data['settings']['tmpl_id'] = 'appearance';
        }
        $this->data['form']['fields'] = $this->conf_mngr->getFormFields('appearance', $form, $this->data['settings']);

        $resources_scripts = $this->dispatch(
            'responses/common/resource_library/get_resources_scripts',
            [
                'object_name' => 'store',
                'object_id'   => (int)$this->data['store_id'],
                'types'       => ['image'],
                'onload'      => true,
                'mode'        => 'single',
            ]
        );
        $this->data['resources_scripts'] = $resources_scripts->dispatchGetOutput();
    }

    /**
     * @param string $group
     *
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    protected function validate($group)
    {
        if (!$this->user->canModify('design/template')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        $this->load->library('config_manager');
        $config_mngr = new AConfigManager();
        $result = $config_mngr->validate($group, $this->request->post);
        $this->error = $result['error'];
        $this->request->post = $result['validated']; // for changed data saving

        $this->extensions->hk_ValidateData($this);

        if (!$this->error) {
            return true;
        } else {
            if (!isset($this->error['warning'])) {
                $this->error['warning'] = $this->language->get('error_required_data');
            }
            return false;
        }
    }
}