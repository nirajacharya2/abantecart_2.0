<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2023 Belavier Commerce LLC

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
use abc\models\content\Content;
use abc\models\content\ContentDescription;
use abc\models\system\Store;
use H;
use Illuminate\Validation\ValidationException;

class ControllerPagesDesignContent extends AController
{
    public $error = [];
    public $fields = [];

    public function __construct($registry, $instance_id, $controller, $parent_controller = '')
    {
        parent::__construct($registry, $instance_id, $controller, $parent_controller);
        $c = new Content();
        $cd = new ContentDescription();
        $this->fields = array_unique(array_merge(
            $c->getFillable(),
            $cd->getFillable(),
            ['stores', 'content_id', 'keyword']
        ));
        unset($c, $cd);
    }

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->view->assign('form_language_switch', $this->html->getContentLanguageSwitcher());
        $this->view->assign('form_store_switch', $this->html->getStoreSwitcher());

        $this->view->assign('success', $this->session->data['success']);
        if (isset($this->session->data['success'])) {
            unset($this->session->data['success']);
        }

        $this->document->initBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('index/home'),
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ]
        );
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('design/content'),
                'text'      => $this->language->get('heading_title'),
                'separator' => ' :: ',
                'current'   => true,
            ]
        );

        $grid_settings = [
            'table_id'         => 'content_grid',
            'url'              => $this->html->getSecureURL('listing_grid/content'),
            'editurl'          => $this->html->getSecureURL('listing_grid/content/update'),
            'update_field'     => $this->html->getSecureURL('listing_grid/content/update_field'),
            'sortname'         => 'sort_order',
            'sortorder'        => 'asc',
            'drag_sort_column' => 'sort_order',
            'columns_search'   => true,
            'actions'          => [
                'edit'   => [
                    'text'     => $this->language->get('text_edit'),
                    'href'     => $this->html->getSecureURL('design/content/update', '&content_id=%ID%'),
                    'children' => array_merge(
                        [
                            'general' => [
                                'text' => $this->language->get(
                                    'tab_general'
                                ),
                                'href' => $this->html->getSecureURL(
                                    'design/content/update',
                                    '&content_id=%ID%'
                                ),
                            ],
                            'layout'  => [
                                'text' => $this->language->get(
                                    'tab_layout'
                                ),
                                'href' => $this->html->getSecureURL(
                                    'design/content/edit_layout',
                                    '&content_id=%ID%'
                                ),
                            ],
                        ],
                        (array)$this->data['grid_edit_expand']
                    ),
                ],
                'delete' => [
                    'text' => $this->language->get('button_delete'),
                ],
                'save'   => [
                    'text' => $this->language->get('button_save'),
                ],
            ],
        ];

        $grid_settings['colNames'] = [
            $this->language->get('column_title'),
            $this->language->get('column_parent'),
            $this->language->get('column_status'),
            $this->language->get('column_sort_order'),
        ];
        $grid_settings['colModel'] = [
            [
                'name'  => 'title',
                'index' => 'keyword',
                'width' => 250,
                'align' => 'left',
            ],
            [
                'name'   => 'parent_name',
                'index'  => 'parent_name',
                'width'  => 100,
                'align'  => 'center',
                'search' => false,
            ],
            [
                'name'   => 'status',
                'index'  => 'status',
                'width'  => 100,
                'align'  => 'center',
                'search' => false,
            ],
            [
                'name'   => 'sort_order',
                'index'  => 'sort_order',
                'width'  => 100,
                'align'  => 'center',
                'search' => false,
            ],
        ];
        if ($this->config->get('config_show_tree_data')) {
            $grid_settings['expand_column'] = 'title';
            $grid_settings['multiaction_class'] = 'hidden';
        }
        $grid = $this->dispatch('common/listing_grid', [$grid_settings]);
        $this->view->assign('listing_grid', $grid->dispatchGetOutput());

        $this->document->setTitle($this->language->get('heading_title'));
        $this->view->assign('insert', $this->html->getSecureURL('design/content/insert'));
        $this->view->assign('help_url', $this->gen_help_url('content_listing'));

        $this->processTemplate('pages/design/content_list.tpl');
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function insert()
    {
        $content_id = null;
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->setTitle($this->language->get('heading_title'));
        if ($this->request->is_POST()) {
            $saveData = $this->prepareData($this->request->post);
            if ($this->validateForm($saveData)) {
                $content_id = Content::addContent($saveData);
                if ($content_id) {
                    $this->session->data['success'] = $this->language->get('text_success');
                    $this->extensions->hk_ProcessData($this, __FUNCTION__, ['content_id' => $content_id]);
                    abc_redirect($this->html->getSecureURL('design/content/update', '&content_id=' . $content_id));
                } else {
                    $this->error[] = $this->language->get('error_application_error');
                }
            }
        }

        $this->data['error'] = $this->error;
        $this->data = array_merge($this->data, $this->request->post);

        // content language switcher
        $this->view->assign('form_language_switch', $this->html->getContentLanguageSwitcher());
        $this->_initTabs('form');
        $this->getForm($content_id);

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function update()
    {
        if (!$this->request->get['content_id']) {
            abc_redirect($this->html->getSecureURL('design/content'));
        }
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->document->setTitle($this->language->get('update_title'));
        $content_id = (int)$this->request->get['content_id'];

        if ($this->request->is_POST()) {
            $saveData = $this->prepareData($this->request->post);
            if ($this->validateForm($saveData)) {
                $result = Content::editContent($content_id, $saveData);
                if ($result) {
                    $this->session->data['success'] = $this->language->get('text_success');
                    $this->extensions->hk_ProcessData($this, __FUNCTION__, ['content_id' => $content_id]);
                    abc_redirect($this->html->getSecureURL('design/content/update', '&content_id=' . $content_id));
                } else {
                    $this->error['warning'] = $this->language->get('error_application_error');
                }
            }
        }

        $this->_initTabs('form');
        $this->view->assign('content_id', $content_id);
        $this->view->assign(
            'insert',
            $this->html->getSecureURL('design/content/insert', '&parent_id=' . $content_id)
        );
        $this->getForm($content_id);
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function _initTabs($active = null)
    {
        $content_id = $this->request->get['content_id'];

        if ($content_id) {
            $this->data['tabs']['form'] = [
                'text'       => $this->language->get('tab_form'),
                'href'       => $this->html->getSecureURL('design/content/update', '&content_id=' . $content_id),
                'active'     => $active == 'form',
                'sort_order' => 0,
            ];

            $this->data['tabs']['layout'] = [
                'text'       => $this->language->get('tab_layout'),
                'href'       => $this->html->getSecureURL('design/content/edit_layout', '&content_id=' . $content_id),
                'active'     => $active == 'layout',
                'sort_order' => 1,
            ];
        }

        $obj = $this->dispatch(
        /** @see ControllerResponsesCommonTabs */
            'responses/common/tabs',
            [
                'content',
                $this->rt(),
                //parent controller. Use customer to use for other extensions that will add tabs via their hooks
                ['tabs' => $this->data['tabs']],
            ]
        );
        $this->data['tabs'] = $obj->dispatchGetOutput();
    }

    protected function getForm($content_id)
    {
        if (isset($this->error['warning'])) {
            $this->data['error_warning'] = $this->error['warning'];
        } else {
            $this->data['error_warning'] = '';
        }

        $this->view->assign('success', $this->session->data['success']);
        if (isset($this->session->data['success'])) {
            unset($this->session->data['success']);
        }

        $this->data['error'] = $this->error;
        $this->data['language_id'] = $this->config->get('storefront_language_id');
        $content_info = [];
        if ($content_id) {
            Content::setCurrentLanguageID($this->data['language_id']);
            $content_info = (array)Content::getContent((int)$content_id)?->toArray();
        }

        $this->document->initBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('index/home'),
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ]
        );

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('design/content'),
                'text'      => $this->language->get('heading_title'),
                'separator' => ' :: ',
            ]
        );

        if ($content_id) {
            $this->document->addBreadcrumb(
                [
                    'href'      => $this->html->getSecureURL('design/content/update', '&content_id=' . $content_id),
                    'text'      => $this->language->get('update_title') . ' - ' . $content_info['title'],
                    'separator' => ' :: ',
                    'current'   => true,
                ]
            );
        } else {
            $this->document->addBreadcrumb(
                [
                    'href'      => $this->html->getSecureURL('design/content/insert'),
                    'text'      => $this->language->get('insert_title'),
                    'separator' => ' :: ',
                    'current'   => true,
                ]
            );
        }

        $this->data['cancel'] = $this->html->getSecureURL('design/content');

        foreach ($this->fields as $field) {
            $this->data[$field] = $this->request->post[$field] ?? $content_info[$field] ?? '';
        }

        if (!$content_id) {
            $this->data['action'] = $this->html->getSecureURL('design/content/insert');
            $this->data['form_title'] = $this->language->get('insert_title');
            $this->data['update'] = '';
            $form = new AForm('ST');
        } else {
            $this->data['action'] = $this->html->getSecureURL('design/content/update', '&content_id=' . $content_id);
            $this->data['form_title'] = $this->language->get('update_title');
            $this->data['update'] = $this->html->getSecureURL(
                'listing_grid/content/update_field',
                '&id=' . $content_id
            );
            $form = new AForm('HS');
        }

        $form->setForm(
            [
                'form_name' => 'contentFrm',
                'update'    => $this->data['update'],
            ]
        );

        $this->data['form']['id'] = 'contentFrm';
        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'contentFrm',
                'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
                'action' => $this->data['action'],
            ]
        );
        $this->data['form']['submit'] = $form->getFieldHtml(
            [
                'type'  => 'button',
                'name'  => 'submit',
                'text'  => $this->language->get('button_save'),
                'style' => 'button1',
            ]
        );
        $this->data['form']['cancel'] = $form->getFieldHtml(
            [
                'type'  => 'button',
                'name'  => 'cancel',
                'text'  => $this->language->get('button_cancel'),
                'style' => 'button2',
            ]
        );

        if ($content_id && $this->data['status']) {
            $href = $this->html->getSecureURL('content/content', '&content_id=' . $content_id, '', 'storefront');
            if ($this->config->get('enable_seo_url') && trim($this->data['keyword']) !== '') {
                $href = $this->html->getHomeURL() . $this->data['keyword'];
            }
            $this->data['form']['show_on_storefront'] = $form->getFieldHtml(
                [
                    'type'  => 'button',
                    'name'  => 'show_on_storefront',
                    'text'  => $this->language->get('text_storefront'),
                    'href'  => $href,
                    'style' => 'button2',
                ]
            );
        }

        $this->data['form']['fields']['status'] = $form->getFieldHtml(
            [
                'type'  => 'checkbox',
                'name'  => 'status',
                'value' => $this->data['status'],
                'style' => 'btn_switch',
                'attr'  => 'reload_on_save="true"',
            ]
        );

        $this->data['form']['fields']['name'] = $form->getFieldHtml(
            [
                'type'         => 'input',
                'name'         => 'name',
                'value'        => $this->data['name'],
                'required'     => true,
                'multilingual' => true,
            ]
        );

        $options = ['' => '-------'] + array_column(Content::getTree(), 'name', 'content_id');

        $this->data['form']['fields']['parent_id'] = $form->getFieldHtml(
            [
                'type'    => 'selectbox',
                'name'    => 'parent_id',
                'options' => $options,
                'value'   => $this->data['parent_id'] ?: $this->request->get['parent_id'],
            ]
        );
        $this->data['entry_parent_id'] = $this->language->get('entry_parent');
        $this->data['form']['fields']['title'] = $form->getFieldHtml(
            [
                'type'         => 'input',
                'name'         => 'title',
                'value'        => $this->data['title'],
                'required'     => true,
                'multilingual' => true,
            ]
        );
        $this->data['form']['fields']['hide_title'] = $form->getFieldHtml(
            [
                'type'  => 'checkbox',
                'name'  => 'hide_title',
                'value' => $this->data['hide_title'],
                'style' => 'btn_switch',
            ]
        );
        $this->data['form']['fields']['description'] = $form->getFieldHtml(
            [
                'type'         => 'textarea',
                'name'         => 'description',
                'value'        => $this->data['description'],
                'multilingual' => true,
            ]
        );

        $this->data['form']['fields']['meta_keywords'] = $form->getFieldHtml(
            [
                'type'         => 'textarea',
                'name'         => 'meta_keywords',
                'value'        => $this->data['meta_keywords'],
                'multilingual' => true,
            ]
        );

        $this->data['form']['fields']['meta_description'] = $form->getFieldHtml(
            [
                'type'         => 'textarea',
                'name'         => 'meta_description',
                'value'        => $this->data['meta_description'],
                'multilingual' => true,
            ]
        );

        $this->data['form']['fields']['content'] = $form->getFieldHtml(
            [
                'type'         => 'texteditor',
                'name'         => 'content',
                'value'        => $this->data['content'],
                'required'     => true,
                'multilingual' => true,
            ]
        );
        $this->data['keyword_button'] = $form->getFieldHtml(
            [
                'type'  => 'button',
                'name'  => 'generate_seo_keyword',
                'text'  => $this->language->get('button_generate'),
                'style' => 'btn btn-info',
            ]
        );
        $this->data['generate_seo_url'] = $this->html->getSecureURL(
            'common/common/getseokeyword',
            '&object_key_name=content_id&id=' . $content_id
        );
        $this->data['form']['fields']['keyword'] = $form->getFieldHtml(
            [
                'type'         => 'input',
                'name'         => 'keyword',
                'value'        => $this->data['keyword'],
                'style'        => 'large-field',
                'multilingual' => true,
                'help_url'     => $this->gen_help_url('seo_keyword'),
            ]
        );

        $options = Store::orderBy('name')->get()?->pluck('name', 'store_id')->toArray();

        if (count($options) > 1) {
            $this->data['form']['fields']['store'] = $form->getFieldHtml(
                [
                    'type'      => 'checkboxgroup',
                    'name'      => 'stores[]',
                    'value'     => $this->data['stores'] ?: key($options),
                    'options'   => $options,
                    'scrollbox' => true,
                    'style'     => 'chosen',
                ]
            );
        } else {
            $this->data['form']['fields']['store'] = $form->getFieldHtml(
                [
                    'type'  => 'hidden',
                    'name'  => 'stores[]',
                    'value' => 0
                ]
            );
        }

        $this->data['form']['fields']['sort_order'] = $form->getFieldHtml(
            [
                'type'  => 'number',
                'name'  => 'sort_order',
                'value' => (int)$this->data['sort_order'],
                'style' => 'tiny-field',
                'attr'  => ' min=0 '
            ]
        );

        $resources_scripts = $this->dispatch(
            'responses/common/resource_library/get_resources_scripts',
            [
                'object_name' => 'contents',
                'object_id'   => (int)$this->data['parent_id'],
                'types'       => ['image'],
            ]
        );
        $this->data['resources_scripts'] = $resources_scripts->dispatchGetOutput();
        $this->data['rl'] = $this->html->getSecureURL(
            'common/resource_library',
            '&action=list_library&object_name=&object_id&type=image&mode=single'
        );
        $this->data['form_language_switch'] = $this->html->getContentLanguageSwitcher();
        $this->view->assign('help_url', $this->gen_help_url('content_edit'));
        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/design/content_form.tpl');
    }

    protected function prepareData(array $inData)
    {
        $inData['language_id'] = $this->language->getContentLanguageID();
        $inData['sort_order'] = (int)$inData['sort_order'];
        $inData['parent_id'] = $inData['parent_id'] ?: null;
        $inData['stores'] = !is_array($inData['stores']) ? [0 => 0] : $inData['stores'];
        return $inData;
    }

    protected function validateForm(array $inData)
    {
        if (!$this->user->hasPermission('modify', 'design/content')) {
            $this->error ['warning'] = $this->language->get('error_permission');
        }

        if ($inData['content_id']) {
            $content = Content::find($inData['content_id']);
            $contentDesc = $content?->description()->getModel();
            if (!$content) {
                $this->error['warning'] = 'Content #' . $inData['content_id'] . ' not found.';
                return false;
            }
        } else {
            $content = new Content();
            $contentDesc = new ContentDescription();
        }

        foreach ([$content, $contentDesc] as $mdl) {
            try {
                $mdl->validate($inData);
            } catch (ValidationException $e) {
                H::SimplifyValidationErrors($mdl->errors()['validation'], $this->error);
            }
        }

        $this->extensions->hk_ValidateData($this);
        return (!$this->error);
    }

    public function edit_layout()
    {
        $page_controller = 'pages/content/content';
        $page_key_param = 'content_id';

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('design/layout');
        $this->document->setTitle($this->language->get('update_title'));

        $content_id = (int)$this->request->get['content_id'];
        if (!$content_id) {
            abc_redirect($this->html->getSecureURL('design/content'));
        }

        $page_url = $this->html->getSecureURL('design/content/edit_layout', '&content_id=' . $content_id);

        // Alert messages
        if (isset($this->session->data['warning'])) {
            $this->data['error_warning'] = $this->session->data['warning'];
            unset($this->session->data['warning']);
        }
        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        $this->data['help_url'] = $this->gen_help_url('content_layout');

        $this->document->resetBreadcrumbs();
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('index/home'),
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ]
        );
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('design/content'),
                'text'      => $this->language->get('heading_title'),
                'separator' => ' :: ',
            ]
        );
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('design/content/update', '&content_id=' . $content_id),
                'text'      => $this->language->get('update_title'),
                'separator' => ' :: ',
            ]
        );
        $content_info = Content::getContent($content_id);
        $this->document->addBreadcrumb(
            [
                'href'    => $page_url,
                'text'    => $this->language->get('tab_layout') . ' - ' . $content_info['title'],
                'current' => true,
            ]
        );

        $this->_initTabs('layout');

        $templateTextId = $this->request->get['tmpl_id'] ?? $this->config->get('config_storefront_template');
        $layout = new ALayoutManager($templateTextId);
        //get existing page layout or generic
        $page_layout = $layout->getPageLayoutIDs($page_controller, $page_key_param, $content_id);
        $pageId = (int)$page_layout['page_id'];
        $layoutId = (int)$page_layout['layout_id'];
        $params = [
            'content_id' => $content_id,
            'page_id'    => $pageId,
            'layout_id'  => $layoutId,
            'tmpl_id'    => $templateTextId,
        ];
        $urlParams = '&' . $this->html->buildURI($params);

        // get templates
        $this->data['templates'] = [];
        $directories = glob(ABC::env('DIR_TEMPLATES') . '*' . DS . ABC::env('DIRNAME_STORE'), GLOB_ONLYDIR);
        foreach ($directories as $directory) {
            $this->data['templates'][] = basename(dirname($directory));
        }
        $enabled_templates = $this->extensions->getExtensionsList(
            [
                'filter' => 'template',
                'status' => 1,
            ]
        );
        foreach ($enabled_templates->rows as $template) {
            $this->data['templates'][] = $template['key'];
        }

        $action = $this->html->getSecureURL('design/content/save_layout');
        // Layout form data
        $form = new AForm('HT');
        $form->setForm([
            'form_name' => 'layout_form',
        ]);

        $this->data['form_begin'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'layout_form',
                'attr'   => 'data-confirm-exit="true"',
                'action' => $action,
            ]
        );

        $this->data['hidden_fields'] = '';
        foreach ($params as $name => $value) {
            $this->data[$name] = $value;
            $this->data['hidden_fields'] .= $form->getFieldHtml(
                [
                    'type'  => 'hidden',
                    'name'  => $name,
                    'value' => $value,
                ]
            );
        }

        $this->data['page_url'] = $page_url;
        $this->data['current_url'] = $this->html->getSecureURL('design/content/edit_layout', $urlParams);

        // insert external form of layout
        $layout = new ALayoutManager($templateTextId, $pageId, $layoutId);
        $layoutForm = $this->dispatch('common/page_layout', [$layout]);
        $this->data['layoutform'] = $layoutForm->dispatchGetOutput();

        //build pages and available layouts for cloning
        $this->data['pages'] = $layout->getAllPages();
        $av_layouts = ["0" => $this->language->get('text_select_copy_layout')];
        foreach ($this->data['pages'] as $page) {
            if ($page['layout_id'] != $layoutId) {
                $av_layouts[$page['layout_id']] = $page['layout_name'];
            }
        }

        $form = new AForm('HT');
        $form->setForm([
            'form_name' => 'cp_layout_frm',
        ]);

        $this->data['cp_layout_select'] = $form->getFieldHtml(
            [
                'type'    => 'selectbox',
                'name'    => 'layout_change',
                'value'   => '',
                'options' => $av_layouts,
            ]
        );

        $this->data['cp_layout_frm'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'cp_layout_frm',
                'attr'   => 'class="aform form-inline"',
                'action' => $action,
            ]
        );

        $this->view->assign('heading_title', $this->language->get('heading_title'));

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/design/content_layout.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function save_layout()
    {
        $page_controller = 'pages/content/content';
        $page_key_param = 'content_id';
        $content_id = $this->request->get_or_post('content_id');
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (!$content_id) {
            abc_redirect($this->html->getSecureURL('design/content'));
        }

        if ($this->request->is_POST()) {
            // need to know unique page existing
            $post = $this->request->post;
            $templateTextId = $post['tmpl_id'];
            $layout = new ALayoutManager($templateTextId);
            $pages = $layout->getPages($page_controller, $page_key_param, $content_id, $templateTextId);
            if (count($pages)) {
                $page_id = (int)$pages[0]['page_id'];
                $layout_id = (int)$pages[0]['layout_id'];
            } else {
                // create new page record
                $page_info = [
                    'controller' => $page_controller,
                    'key_param'  => $page_key_param,
                    'key_value'  => $content_id,
                ];

                $default_language_id = $this->language->getDefaultLanguageID();
                Content::setCurrentLanguageID($default_language_id);
                $content_info = Content::getContent((int)$content_id);
                if ($content_info) {
                    $page_info['page_descriptions'][$default_language_id]['name'] = $content_info['name'];
                }
                $page_id = $layout->savePage($page_info);
                $layout_id = null;
                // need to generate layout name
                $post['layout_name'] = 'Content: ' . $content_info['name'];
            }

            //create new instance with specific template/page/layout data
            $layout = new ALayoutManager($templateTextId, $page_id, $layout_id);
            if (H::has_value($post['layout_change'])) {
                //update layout request. Clone source layout
                $layout->clonePageLayout($post['layout_change'], $layout_id, $post['layout_name']);
                $this->session->data['success'] = $this->language->get('text_success_layout');
            } else {
                //save new layout
                $layout_data = $layout->prepareInput($post);
                if ($layout_data) {
                    $layout->savePageLayout($layout_data);
                    $this->session->data['success'] = $this->language->get('text_success_layout');
                }
            }
            abc_redirect($this->html->getSecureURL('design/content/edit_layout', '&content_id=' . $content_id));
        }
        abc_redirect($this->html->getSecureURL('design/content'));
    }
}