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
use abc\core\lib\ALayoutManager;

class ControllerPagesDesignLayout extends AController
{

    public function main()
    {
        $layout_data = [];
        //use to init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->session->data['content_language_id'] = $this->config->get('storefront_language_id');

        $this->document->setTitle($this->language->get('heading_title'));

        $templateTextId = $this->request->get['tmpl_id'];
        if (!$templateTextId) {
            //get current storefront template
            $this->loadModel('setting/setting');
            $store_id = $this->request->get['store_id'] ?? $this->config->get('config_store_id');
            $settings = $this->model_setting_setting->getSetting('appearance', $store_id);
            $templateTextId = $settings['config_storefront_template'];
        }
        $page_id = (int)$this->request->get['page_id'];
        $layout_id = (int)$this->request->get['layout_id'];

        //Note yet implemented
        if (isset($this->request->get['preview_id'])) {
            $preview_id = $this->request->get['preview_id'];
            $layout_data['preview_id'] = $preview_id;
            $layout_data['preview_url'] = ABC::env('HTTP_CATALOG')
                . '?preview=' . $preview_id
                . '&layout_id=' . $preview_id
                . '&page_id=' . $page_id;
        }

        $layout = new ALayoutManager($templateTextId, $page_id, $layout_id);
        $layout_data['pages'] = $layout->getAllPages();
        $layout_data['current_page'] = $layout->getPageData();

        $params = [
            'page_id'   => $layout_data['current_page']['page_id'],
            'layout_id' => $layout->getLayoutId(),
            'tmpl_id'   => $layout->getTemplateId(),
        ];

        $url = '&'.$this->html->buildURI($params);

        // get templates
        $layout_data['templates'] = [];
        $directories = glob(ABC::env('DIR_TEMPLATES') . '*' . DS . ABC::env('DIRNAME_STORE'), GLOB_ONLYDIR);
        foreach ($directories as $directory) {
            $layout_data['templates'][] = basename(dirname($directory));
        }
        $enabled_templates = $this->extensions->getExtensionsList(
            [
                'filter' => 'template',
                'status' => 1,
            ]
        );
        foreach ($enabled_templates->rows as $template) {
            $layout_data['templates'][] = $template['key'];
        }

        // breadcrumb path
        $this->document->initBreadcrumb(
            [
                'href' => $this->html->getSecureURL('index/home'),
                'text' => $this->language->get('text_home'),
            ]
        );
        $this->document->addBreadcrumb(
            [
                'href'    => $this->html->getSecureURL('design/layout'),
                'text'    => $this->language->get('heading_title').' - '.$params['tmpl_id'],
                'current' => true,
            ]
        );

        // Layout form data
        $form = new AForm('HT');
        $form->setForm(
            [
                'form_name' => 'layout_form',
            ]
        );

        $layout_data['form_begin'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'layout_form',
                'attr'   => 'data-confirm-exit="true"',
                'action' => $this->html->getSecureURL('design/layout/save'),
            ]
        );

        $layout_data['hidden_fields'] = '';
        foreach ($params as $name => $value) {
            $layout_data[$name] = $value;
            $layout_data['hidden_fields'] .= $form->getFieldHtml(
                [
                    'type'  => 'hidden',
                    'name'  => $name,
                    'value' => $value,
                ]
            );
        }

        $layout_data['page_url'] = $this->html->getSecureURL('design/layout');
        $layout_data['page_builder_url'] = $this->html->getSecureURL('r/design/page_builder');
        $layout_data['generate_preview_url'] = $this->html->getSecureURL('design/layout/preview');
        $layout_data['current_url'] = $this->html->getSecureURL('design/layout', $url);
        $layout_data['page_delete_url'] = $this->html->getSecureURL('design/layout/delete');
        $layout_data['insert_url'] = $this->html->getSecureURL('design/layout/insert', $url);
        $layout_data['help_url'] = $this->gen_help_url('layout');

        // Alert messages
        if (isset($this->session->data['warning'])) {
            $layout_data['error_warning'] = $this->session->data['warning'];
            unset($this->session->data['warning']);
        }
        if (isset($this->session->data['success'])) {
            $layout_data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        $layoutForm = $this->dispatch('common/page_layout', ['layout' => $layout]);
        $layout_data['layoutform'] = $layoutForm->dispatchGetOutput('common/page_layout');

        $this->view->batchAssign($layout_data);
        $this->processTemplate('pages/design/layout.tpl');
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function save()
    {
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $url = '';

        if ($this->request->is_POST()) {
            $templateTextId = $this->request->post['tmpl_id'];
            $pageId = (int)$this->request->post['page_id'];
            $layoutId = (int)$this->request->post['layout_id'];

            $url = '&' . $this->html->buildURI(
                    [
                        'tmpl_id'   => $templateTextId,
                        'page_id'   => $pageId,
                        'layout_id' => $layoutId,
                    ]
                );

            $layout = new ALayoutManager($templateTextId, $pageId, $layoutId);
            $layout_data = $layout->prepareInput($this->request->post);
            if ($layout_data) {
                $layout->savePageLayout($layout_data);
                $this->session->data['success'] = $this->language->get('text_success');
            }
        }

        abc_redirect($this->html->getSecureURL('design/layout', $url));
    }

    public function preview()
    {
        //NOTE: Layout preview feature is not finished. Not supported yet
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $url = '';

        if ($this->request->is_POST()) {
            $tmpl_id = $this->request->post['tmpl_id'];
            $page_id = (int)$this->request->post['page_id'];
            $layout_id = (int)$this->request->post['layout_id'];
            $section = $this->request->post['section'];
            $block = $this->request->post['block'];
            $parentBlock = $this->request->post['parentBlock'];
            $blockStatus = $this->request->post['blockStatus'];

            foreach ($section as $k => $item) {
                $section[$k]['children'] = [];
            }

            foreach ($block as $k => $block_id) {
                $parent = $parentBlock[$k];
                $status = $blockStatus[$k];

                $section[$parent]['children'][] = [
                    'block_id' => $block_id,
                    'status'   => $status,
                ];
            }

            $layout_data['blocks'] = $section;

            $layout = new ALayoutManager($tmpl_id, $page_id, $layout_id);
            $draft_layout_id = $layout->savePageLayoutAsDraft($layout_data);

            $url = '&'.$this->html->buildURI(
                    [
                        'tmpl_id'    => $tmpl_id,
                        'page_id'    => $page_id,
                        'layout_id'  => $layout_id,
                        'preview_id' => $draft_layout_id,
                    ]
                );
        }

        abc_redirect($this->html->getSecureURL('design/layout', $url));
    }

    public function delete()
    {
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $templateTextId = $this->request->get['tmpl_id'] ?? null;
        $pageId = (int)$this->request->get['page_id'];
        $layoutId = (int)$this->request->get['layout_id'];

        $success = false;
        if ($this->request->is_GET() && $this->request->get['confirmed_delete'] == 'yes') {
            $layout = new ALayoutManager($templateTextId, $pageId, $layoutId);
            //do delete this page/layout validate that it is allowed to delete
            $page = $layout->getPageData();
            if ($page['restricted']) {
                $this->session->data['warning'] = $this->language->get('text_delete_restricted');
            } else {
                if ($layout->deletePageLayoutByID($pageId, $layoutId)) {
                    $this->session->data['success'] = $this->language->get('text_delete_success');
                    $success = true;
                } else {
                    $this->session->data['warning'] = 'Error! Try again.';
                }
            }
        }

        $url = '';
        if (!$success) {
            if ($templateTextId) {
                $url .= '&tmpl_id=' . $templateTextId;
            }
            if ($layoutId) {
                $url .= '&layout_id=' . $layoutId;
            }
            if ($pageId) {
                $url .= '&page_id=' . $pageId;
            }
        }

        abc_redirect($this->html->getSecureURL('design/layout', $url));
    }
}