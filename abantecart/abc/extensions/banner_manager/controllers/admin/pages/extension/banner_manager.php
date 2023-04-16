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
use abc\extensions\banner_manager\models\Banner;
use abc\extensions\banner_manager\models\BannerDescription;
use abc\modules\traits\BlockTabsTrait;
use Carbon\Carbon;
use H;
use abc\core\engine\AResource;
use abc\core\lib\ALayoutManager;
use abc\core\lib\AListingManager;
use Illuminate\Validation\ValidationException;


class ControllerPagesExtensionBannerManager extends AController
{
    use BlockTabsTrait;

    public $error = [];

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('banner_manager/banner_manager');

        $this->document->setTitle($this->language->get('banner_manager_name'));
        $this->data['heading_title'] = $this->language->get('banner_manager_list');

        $this->document->initBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('index/home'),
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ]
        );
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('extension/banner_manager'),
                'text'      => $this->language->get('banner_manager_name'),
                'separator' => ' :: ',
                'current'   => true,
            ]
        );

        $grid_settings = [
            'table_id'       => 'banner_grid',
            'url'            => $this->html->getSecureURL('listing_grid/banner_manager'),
            'editurl'        => $this->html->getSecureURL('listing_grid/banner_manager/edit'),
            'update_field'   => $this->html->getSecureURL('listing_grid/banner_manager/update_field'),
            'sortname'       => 'date_modified',
            'sortorder'      => 'desc',
            'columns_search' => true,
            'actions'        => [
                'edit'   => [
                    'text' => $this->language->get('text_edit'),
                    'href' => $this->html->getSecureURL('extension/banner_manager/edit', '&banner_id=%ID%'),
                ],
                'delete' => [
                    'text' => $this->language->get('button_delete'),
                    'href' => $this->html->getSecureURL('extension/banner_manager/delete', '&banner_id=%ID%'),
                ],
            ],
        ];

        $form = new AForm ();
        $form->setForm(['form_name' => 'banner_grid_search']);

        $grid_settings['colNames'] = [
            $this->language->get('column_banner_id'),
            '', //icons
            $this->language->get('column_banner_name'),
            $this->language->get('column_banner_group'),
            $this->language->get('column_banner_type'),
            $this->language->get('column_status'),
            $this->language->get('column_update_date'),
        ];

        $grid_settings['colModel'] = [
            [
                'name'   => 'banner_id',
                'index'  => 'banner_id',
                'width'  => 20,
                'align'  => 'center',
                'search' => false,
            ],
            [
                'name'   => 'banner_icon',
                'index'  => 'icon',
                'width'  => 50,
                'align'  => 'center',
                'search' => false,
            ],
            [
                'name'  => 'banner_name',
                'index' => 'keyword',
                'width' => 110,
                'align' => 'left',
            ],
            [
                'name'  => 'banner_group',
                'index' => 'banner_group_name',
                'width' => 110,
                'align' => 'left',
            ],
            [
                'name'   => 'banner_type',
                'index'  => 'banner_type',
                'width'  => 70,
                'align'  => 'center',
                'search' => false,
            ],
            [
                'name'   => 'status',
                'index'  => 'status',
                'align'  => 'center',
                'width'  => 60,
                'search' => false,
            ],
            [
                'name'   => 'date_modified',
                'index'  => 'date_modified',
                'width'  => 80,
                'align'  => 'center',
                'search' => false,
            ],
        ];

        $grid = $this->dispatch('common/listing_grid', [$grid_settings]);
        $this->data['listing_grid'] = $grid->dispatchGetOutput();

        if (isset ($this->session->data['warning'])) {
            $this->data['error_warning'] = $this->session->data['warning'];
            $this->session->data['warning'] = '';
        } else {
            $this->data ['error_warning'] = '';
        }
        if (isset ($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            $this->session->data['success'] = '';
        } else {
            $this->data ['success'] = '';
        }

        $this->data['banner_types'] = [
            [
                'href' => $this->html->getSecureURL('extension/banner_manager/insert', '&banner_type=1'),
                'text' => $this->language->get('text_graphic_banner'),
                'icon' => '<i class="fa fa-file-image-o fa-fw"></i>',
            ],
            [
                'href' => $this->html->getSecureURL('extension/banner_manager/insert', '&banner_type=2'),
                'text' => $this->language->get('text_text_banner'),
                'icon' => '<i class="fa fa-file-text-o fa-fw"></i>',
            ],
        ];
        $this->data['text_type'] = $this->language->get('column_banner_type');
        $this->data['insert'] = $this->html->getSecureURL('extension/banner_manager/insert');

        $this->data['form_language_switch'] = $this->html->getContentLanguageSwitcher();

        $this->view->batchAssign($this->language->getASet());
        $this->view->batchAssign($this->data);
        $this->view->assign('help_url', $this->gen_help_url('banner_manager'));

        $this->processTemplate('pages/extension/banner_manager.tpl');
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function insert()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('banner_manager/banner_manager');

        $this->document->setTitle($this->language->get('banner_manager_name'));
        $this->data['heading_title'] = $this->language->get('banner_manager_name');

        if ($this->request->is_POST()) {
            $inData = $this->prepareBannerData($this->request->post);
            if ($this->_validateForm($inData)) {
                $banner_id = Banner::addBanner($inData);
                if (!$banner_id) {
                    $this->session->data ['warning'] = 'Oops. Unexpected error occurred. Please see error log for details.';
                } else {
                    $this->session->data ['success'] = $this->language->get('text_banner_success');
                    $this->extensions->hk_ProcessData($this, __FUNCTION__, $inData);
                    abc_redirect($this->html->getSecureURL('extension/banner_manager/edit', '&banner_id=' . $banner_id));
                }
            }
        }

        $this->data['error'] = $this->error;
        $this->data = array_merge($this->data, $this->request->post);

        $this->_getForm();

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function edit()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('banner_manager/banner_manager');

        $this->document->setTitle($this->language->get('banner_manager_name'));
        $this->data['heading_title'] = $this->language->get('banner_manager_name');
        $banner_id = (int)$this->request->get['banner_id'];

        // saving
        if ($this->request->is_POST()) {
            $inData = $this->prepareBannerData($this->request->post);

            if ($this->_validateForm($inData) && $banner_id) {
                $result = Banner::editBanner($banner_id, $inData);
                if ($result) {
                    $this->session->data ['success'] = $this->language->get('text_banner_success');
                    $this->extensions->hk_ProcessData($this, __FUNCTION__, $inData);
                    abc_redirect($this->html->getSecureURL('extension/banner_manager/edit', '&banner_id=' . $banner_id));
                }
                $this->session->data ['warning'] = 'Oops. Unexpected error occurred. Please see error log for details';
            }
        }

        $this->data['error'] = $this->error;
        $this->data = array_merge($this->data, $this->request->post);

        $bannerInfo = (array)Banner::getBanner($banner_id)?->toArray();
        $this->data = array_merge($this->data, $bannerInfo);

        $this->data['banner_group_name'] = [$this->data['banner_group_name'], $this->data['banner_group_name']];

        $this->_getForm();
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function delete()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $banner_id = (int)$this->request->get['banner_id'];
        Banner::find($banner_id)?->delete();
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        abc_redirect($this->html->getSecureURL('extension/banner_manager'));
    }

    protected function _getForm()
    {
        if (isset ($this->session->data['warning'])) {
            $this->data ['error_warning'] = $this->session->data['warning'];
            $this->session->data['warning'] = '';
        } else {
            $this->data ['error_warning'] = '';
        }

        $this->view->assign('success', $this->session->data['success']);
        if (isset($this->session->data['success'])) {
            unset($this->session->data['success']);
        }

        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('extension/banner_manager'),
            'text'      => $this->language->get('banner_manager_name'),
            'separator' => ' :: ',
        ]);

        $this->data ['cancel'] = $this->html->getSecureURL('extension/banner_manager');

        $banner_type = 1;
        if (!isset($this->request->get['banner_id'])) {
            if ($this->request->get['banner_type']) {
                $banner_type = $this->request->get['banner_type'];
            } elseif ($this->request->post['banner_type']) {
                $banner_type = $this->request->post['banner_type'];
            }

            $this->data ['action'] = $this->html->getSecureURL('extension/banner_manager/insert');
            $this->data ['form_title'] = $this->language->get('text_create');
            $this->data ['update'] = '';
            $form = new AForm ('ST');
        } else {
            $this->data ['action'] = $this->html->getSecureURL(
                'extension/banner_manager/edit',
                '&banner_id=' . $this->request->get ['banner_id']
            );
            $this->data ['form_title'] = $this->language->get('text_edit') . ' ' . $this->data['name'];
            $this->data ['update'] = $this->html->getSecureURL(
                'listing_grid/banner_manager/update_field',
                '&banner_id=' . $this->request->get ['banner_id']
            );
            $form = new AForm ('HS');

            $this->data['button_details'] = $this->html->buildElement(
                [
                    'type' => 'button',
                    'name' => 'btn_details',
                    'href' => $this->html->getSecureUrl(
                        'extension/banner_manager_stat/details',
                        '&banner_id=' . $this->request->get ['banner_id']
                    ),
                    'text' => $this->language->get('text_view_stat'),
                ]
            );

            $banner_type = $this->data['banner_type'];
        }

        if ($banner_type == 1) {
            $this->data['banner_types'] = [
                'text' => $this->language->get('text_graphic_banner'),
                'icon' => '<i class="fa fa-file-image-o fa-fw"></i>',
            ];
        } else {
            $this->data['banner_types'] = [
                'text' => $this->language->get('text_text_banner'),
                'icon' => '<i class="fa fa-file-text-o fa-fw"></i>',
            ];
        }

        $this->document->addBreadcrumb(
            [
                'href'      => $this->data['action'],
                'text'      => $this->data ['form_title'],
                'separator' => ' :: ',
                'current'   => true,
            ]
        );

        $form->setForm(
            [
                'form_name' => 'BannerFrm', 'update' => $this->data ['update']
            ]
        );

        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'BannerFrm',
                'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
                'action' => $this->data ['action'],
            ]
        );

        $this->data['form']['hidden_fields']['banner_type'] = $form->getFieldHtml(
            [
                'type'  => 'hidden',
                'name'  => 'banner_type',
                'value' => $banner_type,
            ]
        );

        $this->data['form']['submit'] = $form->getFieldHtml(
            [
                'type' => 'button',
                'name' => 'submit',
                'text' => $this->language->get('button_save'),
            ]
        );
        $this->data['form']['cancel'] = $form->getFieldHtml(
            [
                'type' => 'button',
                'name' => 'cancel',
                'text' => $this->language->get('button_cancel'),
            ]
        );

        //check if banner is active based on dates and update status
        $now = time();

        $start = $this->data['start_date']
            ? H::dateISO2Int($this->data['start_date'])
            : ($now - 86400);

        $stop = $this->data['end_date']
            ? H::dateISO2Int($this->data['end_date'])
            : ($now + 86400);

        if ($start > $now || $stop < $now) {
            $this->data['status'] = 0;
        }

        $this->data['form']['fields']['status'] = $form->getFieldHtml(
            [
                'type'  => 'checkbox',
                'name'  => 'status',
                'value' => $this->data['status'],
                'style' => 'btn_switch',
            ]
        );
        $this->data['form']['text']['status'] = $this->language->get('entry_banner_status');

        $this->data['form']['fields']['name'] = $form->getFieldHtml(
            [
                'type'         => 'input',
                'name'         => 'name',
                'value'        => $this->data['name'],
                'multilingual' => true,
                'required'     => true,
            ]
        );
        $this->data['form']['text']['name'] = $this->language->get('entry_banner_name');

        $groups = [
                ''    => $this->language->get('text_select'),
                'new' => $this->language->get('text_add_new_group')
            ] +
            Banner::select('banner_group_name')
                ->orderBy('banner_group_name')
                ->distinct()
                ->get()
                ?->pluck('banner_group_name', 'banner_group_name')
                ->toArray();

        $value = $this->data['banner_group_name'][0];
        if (!$value && sizeof($groups) == 2) {
            $value = 'new';
        }

        $this->data['form']['fields']['banner_group_name'] = $form->getFieldHtml(
            [
                'type'     => 'selectbox',
                'name'     => 'banner_group_name[0]',
                'options'  => $groups,
                'value'    => $value,
                'required' => true,
            ]
        );

        $this->data['form']['text']['banner_group_name'] = $this->language->get('entry_banner_group_name');
        $this->data['form']['fields']['new_banner_group'] = $form->getFieldHtml(
            [
                'type'        => 'input',
                'name'        => 'banner_group_name[1]',
                'value' => (!in_array($this->data['banner_group_name'][1], array_keys($groups))
                    ? $this->data['banner_group_name'][1] : ''),
                'placeholder' => $this->language->get('text_put_new_group'),
            ]
        );
        $this->data['new_group_hint'] = $this->language->get('text_put_new_group');
        $this->data['form']['fields']['sort_order'] = $form->getFieldHtml(
            [
                'type'  => 'number',
                'name'  => 'sort_order',
                'value' => (int)$this->data['sort_order'],
                'style' => 'small-field',
            ]
        );
        $this->data['form']['text']['sort_order'] = $this->language->get('entry_banner_sort_order');

        if ($banner_type == 1) {

            $this->data['form']['fields']['target_url'] = $form->getFieldHtml(
                [
                    'type'     => 'input',
                    'name'     => 'target_url',
                    'value'    => $this->data['target_url'],
                    'required' => true,
                ]
            );
            $this->data['form']['text']['target_url'] = $this->language->get('entry_banner_url');

            $this->data['form']['fields']['blank'] = $form->getFieldHtml(
                [
                    'type'  => 'checkbox',
                    'name'  => 'blank',
                    'value' => $this->data['blank'],
                    'style' => 'btn_switch',
                ]
            );
            $this->data['form']['text']['blank'] = $this->language->get('entry_banner_blank');
        }
        $this->data['form']['fields']['date_start'] = $form->getFieldHtml(
            [
                'type'       => 'date',
                'name'       => 'start_date',
                'value'      => H::dateISO2Display($this->data['start_date']),
                'default'    => H::dateNowDisplay(),
                'dateformat' => H::format4Datepicker($this->language->get('date_format_short')),
                'highlight'  => 'future',
                'style'      => 'small-field',
            ]
        );
        $this->data['form']['text']['date_start'] = $this->language->get('entry_banner_date_start');

        $this->data['form']['fields']['date_end'] = $form->getFieldHtml(
            [
                'type'       => 'date',
                'name'       => 'end_date',
                'value'      => H::dateISO2Display($this->data['end_date']),
                'default'    => '',
                'dateformat' => H::format4Datepicker(
                    $this->language->get('date_format_short')
                ),
                'highlight'  => 'past',
                'style'      => 'small-field',
            ]
        );
        $this->data['form']['text']['date_end'] = $this->language->get('entry_banner_date_end');

        $this->data['banner_id'] = $this->request->get['banner_id'] ?: '-1';

        if ($banner_type == 1) {
            $this->data['form']['fields']['meta'] = $form->getFieldHtml(
                [
                    'type'  => 'textarea',
                    'name'  => 'meta',
                    'value' => $this->data ['meta'],
                    'attr'  => ' style="height: 50px;"',
                ]
            );
            $this->data['form']['text']['meta'] = $this->language->get('entry_banner_meta');

            $this->addChild(
                'responses/common/resource_library/get_resources_html', 'resources_html',
                'responses/common/resource_library_scripts.tpl'
            );
            $resources_scripts = $this->dispatch(
                'responses/common/resource_library/get_resources_scripts',
                [
                    'object_name' => 'banners',
                    'object_id'   => (int)$this->data['banner_id'],
                    'types'       => ['image'],
                ]
            );

            $this->view->assign('current_url', $this->html->currentURL());
            $this->view->assign('resources_scripts', $resources_scripts->dispatchGetOutput());
            $this->view->assign(
                'rl',
                $this->html->getSecureURL('common/resource_library', '&object_name=banners&type=image')
            );
        } else {
            $this->data['form']['fields']['description'] = $form->getFieldHtml(
                [
                    'type'  => 'texteditor',
                    'name'  => 'description',
                    'value' => $this->data ['description'],
                    'attr'  => '',
                ]
            );
            $this->data['form']['text']['description'] = $this->language->get('entry_banner_html');
            $resources_scripts = $this->dispatch(
                'responses/common/resource_library/get_resources_scripts',
                [
                    'object_name' => '',
                    'object_id'   => '',
                    'types'       => ['image'],
                ]
            );
            $this->view->assign('resources_scripts', $resources_scripts->dispatchGetOutput());
        }

        $this->view->batchAssign($this->language->getASet());

        $this->data['form_language_switch'] = $this->html->getContentLanguageSwitcher();
        $this->data['language_code'] = $this->session->data['language'];
        $this->view->assign('help_url', $this->gen_help_url('banner_edit'));

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/extension/banner_manager_form.tpl');
    }

    protected function _validateForm($data)
    {
        if (!$this->user->canModify('extension/banner_manager')) {
            $this->session->data['warning'] = $this->error ['warning'] = $this->language->get('error_permission');
        }

        if ($data['banner_id']) {
            $banner = Banner::find($data['banner_id']);
            $bd = $banner?->description()->getModel();
            if (!$banner) {
                $this->error['warning'] = 'Banner #' . $data['banner_id'] . ' not found.';
                return false;
            }
        } else {
            $banner = new Banner();
            $bd = new BannerDescription();
        }

        try {
            $banner->validate($data);
            $bd->validate($data);
        } catch (ValidationException $e) {
            H::SimplifyValidationErrors($banner->errors()['validation'], $this->error);
        }

        $this->extensions->hk_ValidateData($this, __FUNCTION__, $data);
        return (!$this->error);
    }

    // Prepare data before passing to model
    protected function prepareBannerData($inData)
    {
        if (isset($inData['start_date']) && $inData['start_date']) {
            $inData['start_date'] = H::dateDisplay2ISO($inData['start_date']);
        }
        if (isset($inData['end_date']) && $inData['end_date']) {
            $inData['end_date'] = H::dateDisplay2ISO($inData['end_date']);
        }

        if (in_array($inData['banner_group_name'][0], ['', 'new'])) {
            $inData['banner_group_name'] = mb_ereg_replace('/^[0-9A-Za-z\ \. _\-]/', '', trim($inData['banner_group_name'][1]));
        } else {
            $inData['banner_group_name'] = $inData['banner_group_name'][0];
        }
        $inData['banner_type'] = (int)$inData['banner_type'];
        $inData['sort_order'] = (int)$inData['sort_order'];
        return $inData;
    }

    public function insert_block()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('design/blocks');
        $this->loadLanguage('banner_manager/banner_manager');
        $this->document->setTitle($this->language->get('banner_manager_name'));
        $this->data['heading_title'] = $this->language->get('text_banner_block');

        $lm = new ALayoutManager();
        $block = $lm->getBlockByTxtId('banner_block');
        $this->data['block_id'] = (int)$block['block_id'];
        unset($lm);

        if ($this->request->is_POST() && $this->validateBlockForm($this->request->post)) {
            if (isset($this->session->data['layout_params'])) {
                $layout = new ALayoutManager(
                    $this->session->data['layout_params']['tmpl_id'],
                    $this->session->data['layout_params']['page_id'],
                    $this->session->data['layout_params']['layout_id']
                );
                $blocks = $layout->getLayoutBlocks();
                $position = 0;
                $parent_instance_id = null;
                if ($blocks) {
                    foreach ($blocks as $block) {
                        if ($block['block_id'] == $this->session->data['layout_params']['parent_block_id']) {
                            $parent_instance_id = $block['instance_id'];
                            if ($block['children']) {
                                foreach ($block['children'] as $child) {
                                    $position = max($child['position'], $position);
                                }
                            }
                            break;
                        }
                    }
                }
                $saveData = $this->session->data['layout_params'];
                $saveData['parent_instance_id'] = $parent_instance_id;
                $saveData['position'] = $position + 10;
                $saveData['status'] = 1;
            } else {
                $layout = new ALayoutManager();
            }

            $content = '';
            if ($this->request->post['banner_group_name']) {
                $content = serialize(['banner_group_name' => $this->request->post['banner_group_name']]);
            }

            $custom_block_id = $layout->saveBlockDescription(
                $this->data['block_id'],
                0,
                [
                    'name'          => $this->request->post['block_name'],
                    'title'         => $this->request->post['block_title'],
                    'description'   => $this->request->post['block_description'],
                    'content'       => $content,
                    'status'        => (int)$this->request->post['block_status'],
                    'block_wrapper' => $this->request->post['block_wrapper'],
                    'block_framed'  => $this->request->post['block_framed'],
                    'language_id'   => $this->session->data['content_language_id'],
                ]
            );

            // save custom_block in layout
            if (isset($this->session->data['layout_params'])) {
                $saveData['custom_block_id'] = $custom_block_id;
                $saveData['block_id'] = $this->data['block_id'];
                $layout->saveLayoutBlocks($saveData);
                unset($this->session->data['layout_params']);
            }
            // save list if it is custom

            if ($this->request->post['block_banners']) {
                $listing_manager = new AListingManager($custom_block_id);
                $listing_manager->deleteCustomListing((int)$this->config->get('config_store_id'));
                foreach ($this->request->post['block_banners'] as $k => $id) {
                    $listing_manager->saveCustomListItem(
                        [
                            'data_type'  => 'banner_id',
                            'id'         => (int)$id,
                            'sort_order' => (int)$k,
                            'store_id'   => $this->config->get('config_store_id'),
                        ]
                    );
                }
            }

            $this->session->data ['success'] = $this->language->get('text_banner_success');
            abc_redirect(
                $this->html->getSecureURL('extension/banner_manager/edit_block', '&custom_block_id=' . $custom_block_id)
            );
        }

        foreach ($this->request->post as $k => $v) {
            $this->data[$k] = $v;
        }

        $this->getTabs();

        $this->getBlockForm();

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function edit_block()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('banner_manager/banner_manager');
        $this->loadLanguage('design/blocks');
        $this->data['heading_title'] = $this->language->get('text_banner_block');

        $lm = new ALayoutManager();
        $block = $lm->getBlockByTxtId('banner_block');
        $this->data['block_id'] = (int)$block['block_id'];
        $custom_block_id = (int)$this->request->get['custom_block_id'];
        if (!$custom_block_id) {
            abc_redirect($this->html->getSecureURL('extension/banner_manager/insert_block'));
        }

        $tabs = [
            [
                'name'       => '',
                'text'       => $this->language->get('text_banner_block'),
                'href'       => '',
                'active'     => true,
                'sort_order' => 0,
            ],
        ];
        $this->getTabs($tabs);

        if ($this->request->is_POST() && $this->validateBlockForm($this->request->post)) {
            $content = '';
            if ($this->request->post['banner_group_name']) {
                $content = serialize(['banner_group_name' => $this->request->post['banner_group_name']]);
            }
            // saving
            $lm->saveBlockDescription(
                $this->data['block_id'],
                $custom_block_id,
                [
                    'name'          => $this->request->post['block_name'],
                    'title'         => $this->request->post['block_title'],
                    'description'   => $this->request->post['block_description'],
                    'content'       => $content,
                    'status'        => (int)$this->request->post['block_status'],
                    'block_wrapper' => $this->request->post['block_wrapper'],
                    'block_framed'  => $this->request->post['block_framed'],
                    'language_id'   => $this->session->data['content_language_id'],
                ]
            );

            // save list if it is custom
            if ($this->request->post['block_banners']) {
                $listing_manager = new AListingManager($custom_block_id);
                $listing_manager->deleteCustomListing((int)$this->config->get('config_store_id'));
                $k = 0;
                foreach ($this->request->post['block_banners'] as $id) {
                    $listing_manager->saveCustomListItem(
                        [
                            'data_type'  => 'banner_id',
                            'id'         => $id,
                            'sort_order' => $k,
                            'store_id'   => $this->config->get('config_store_id'),
                        ]
                    );
                    $k++;
                }
            } else {
                //delete the list as nothing provided
                $listing_manager = new AListingManager($custom_block_id);
                $listing_manager->deleteCustomListing((int)$this->config->get('config_store_id'));
            }

            $this->session->data ['success'] = $this->language->get('text_banner_success');
            $this->cache->flush('banner');
            abc_redirect(
                $this->html->getSecureURL(
                    'extension/banner_manager/edit_block',
                    '&custom_block_id=' . $custom_block_id
                )
            );
        }

        $this->getBlockForm();

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function getBlockForm()
    {
        if (isset ($this->session->data['warning'])) {
            $this->data ['error_warning'] = $this->session->data['warning'];
            $this->session->data['warning'] = '';
        } else {
            $this->data ['error_warning'] = '';
        }
        $this->load->library('json');
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
                'href'      => $this->html->getSecureURL('design/blocks'),
                'text'      => $this->language->get('heading_title'),
                'separator' => ' :: ',
            ]
        );

        $this->data ['cancel'] = $this->html->getSecureURL('design/blocks');
        $custom_block_id = (int)$this->request->get ['custom_block_id'];

        // need to get data of custom listing
        $optionsList = $ids = [];
        if ($custom_block_id) {
            $lm = new ALayoutManager();
            $block_info = $lm->getBlockDescriptions($custom_block_id);

            $language_id = $this->language->getContentLanguageID();
            if (!isset($block_info[$language_id])) {
                $language_id = key($block_info);
            }

            $this->data = array_merge($this->data, (array)$block_info[$language_id]);

            $content = $block_info[$language_id]['content'];

            if ($content) {
                $content = H::is_serialized($content) ? unserialize($content) : (array)$content;
            } else {
                $content = current($block_info);
                $content = H::is_serialized($content['content']) ? unserialize($content['content']) : (array)$content['content'];
            }

            $this->data['banner_group_name'] = $content['banner_group_name'];
            $lm = new AListingManager($custom_block_id);
            $list = $lm->getCustomList((int)$this->config->get('current_store_id'));

            if ($list) {
                foreach ($list as $row) {
                    $optionsList[(int)$row['id']] = [];
                }
                $ids = array_column($list, 'id');
                $assignedBanners = (array)Banner::getBanners(
                    [
                        'filter' => [
                            'include' => $ids
                        ]
                    ]
                )?->toArray();

                $resource = new AResource('image');
                $thumbnails = $resource->getMainThumbList(
                    'banners',
                    $ids,
                    $this->config->get('config_image_grid_width'),
                    $this->config->get('config_image_grid_height'),
                    false
                );

                foreach ($assignedBanners as $banner) {
                    $id = $banner['banner_id'];
                    if (in_array($id, $ids)) {
                        $thumbnail = $thumbnails[$banner['banner_id']];
                        $icon = $thumbnail['thumb_html'] ?: '<i class="fa fa-code fa-4x"></i>&nbsp;';
                        $optionsList[$id] = [
                            'image'      => $icon,
                            'id'         => $id,
                            'name'       => $banner['name'],
                            'sort_order' => (int)$banner['sort_order'],
                        ];
                    }
                }
            }
        }

        if (!$custom_block_id) {
            $this->data ['action'] = $this->html->getSecureURL('extension/banner_manager/insert_block');
            $this->data ['form_title'] = $this->language->get('text_create_block', 'banner_manager/banner_manager');
            $this->data ['update'] = '';
            $form = new AForm ('ST');
        } else {
            $this->data ['action'] = $this->html->getSecureURL(
                'extension/banner_manager/edit_block',
                '&custom_block_id=' . $custom_block_id
            );
            $this->data ['form_title'] = $this->language->get('text_edit') . ' ' . $this->data['name'];
            $this->data ['update'] = $this->html->getSecureURL(
                'listing_grid/blocks_grid/update_field',
                '&custom_block_id=' . $custom_block_id
            );
            $form = new AForm ('HS');
        }

        $this->document->setTitle($this->data ['form_title']);

        $this->document->addBreadcrumb(
            [
                'href'      => $this->data['action'],
                'text'      => $this->data ['form_title'],
                'separator' => ' :: ',
                'current'   => true,
            ]
        );

        $form->setForm(
            [
                'form_name' => 'BannerBlockFrm',
                'update'    => $this->data ['update']
            ]
        );

        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'BannerBlockFrm',
                'action' => $this->data ['action'],
                'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
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

        if ($custom_block_id) {
            $this->data['form']['fields']['block_status'] = $form->getFieldHtml(
                [
                    'type'  => 'checkbox',
                    'name'  => 'block_status',
                    'value' => $this->data['status'],
                    'style' => 'btn_switch',
                ]
            );

            $this->data['form']['text']['block_status'] = $this->html->convertLinks(
                $this->language->get('entry_block_status')
            );

            $this->data['form']['fields']['block_status_note'] = '';
            $this->data['form']['text']['block_status_note'] = $this->html->convertLinks(
                $this->language->get('entry_block_status_note')
            );
        }

        $this->data['form']['fields']['block_name'] = $form->getFieldHtml(
            [
                'type'  => 'hidden',
                'name'  => 'block_id',
                'value' => $this->data['block_id'],
            ]
        );

        $this->data['form']['fields']['block_name'] .= $form->getFieldHtml(
            [
                'type'         => 'input',
                'name'         => 'block_name',
                'value'        => $this->data['name'],
                'multilingual' => true,
                'required'     => true,
            ]
        );

        $this->data['form']['text']['block_name'] = $this->language->get('entry_block_name');
        $this->data['form']['fields']['block_title'] = $form->getFieldHtml(
            [
                'type'         => 'input',
                'name'         => 'block_title',
                'required'     => true,
                'multilingual' => true,
                'value'        => $this->data ['title'],
            ]
        );
        $this->data['form']['text']['block_title'] = $this->language->get('entry_block_title');

        // list of templates for block
        $templateIds = $this->extensions->getInstalled('template');
        $templateIds[] = 'default';
        $this->data['block_wrappers'] = [];
        foreach ($templateIds as $tmplId) {
            // for tpls of block that stores in db
            $layout_manager = new ALayoutManager($tmplId);
            $block = $layout_manager->getBlockByTxtId('banner_block');
            $block_templates = $layout_manager->getBlockTemplates($block['block_id']);
            foreach ($block_templates as $item) {
                if ($item['template']) {
                    $this->data['block_wrappers'][$item['template']] = $item['template'];
                }
            }

            //Automatic block template selection mode based on parent is limited to 1 template per location
            //To extend, allow custom block's template to be selected to suppress automatic selection
            //for tpls that stores in main.php (other extensions templates)
            $extensionsTpls = $this->extensions->getExtensionTemplates();
            foreach ($extensionsTpls as $section) {
                foreach ($section as $s => $tpls) {
                    if ($s != 'storefront') {
                        continue;
                    }
                    foreach ($tpls as $tpl) {
                        if (isset($this->data['block_wrappers'][$tpl])
                            || !str_contains($tpl, 'blocks/banner_block/')) {
                            continue;
                        }
                        $this->data['block_wrappers'][$tpl] = $tpl;
                    }
                }
            }

            $tpls = glob(ABC::env('DIR_TEMPLATES') . '*/storefront/blocks/banner_block/*.tpl');
            foreach ($tpls as $tpl) {
                $pos = strpos($tpl, 'blocks/banner_block/');
                $tpl = substr($tpl, $pos);
                if (!isset($this->data['block_wrappers'][$tpl])) {
                    $this->data['block_wrappers'][$tpl] = $tpl;
                }
            }
        }

        ksort($this->data['block_wrappers']);
        array_unshift($this->data['block_wrappers'], $this->language->get('text_automatic'));

        if ($this->data['block_wrapper'] && !isset($this->data['block_wrappers'][$this->data['block_wrapper']])) {
            $this->data['block_wrappers'] = array_merge([$this->data['block_wrapper'] => $this->data['block_wrapper']],
                $this->data['block_wrappers']
            );
        }

        $this->data['form']['fields']['block_wrapper'] = $form->getFieldHtml(
            [
                'type'    => 'selectbox',
                'name'    => 'block_wrapper',
                'options' => $this->data['block_wrappers'],
                'value'   => $this->data['block_wrapper'],
            ]
        );
        $this->data['form']['text']['block_wrapper'] = $this->language->get('entry_block_wrapper');

        $this->data['form']['fields']['block_framed'] = $form->getFieldHtml(
            [
                'type'  => 'checkbox',
                'name'  => 'block_framed',
                'value' => $this->data['block_framed'],
                'style' => 'btn_switch',
            ]
        );
        $this->data['form']['text']['block_framed'] = $this->language->get('entry_block_framed');

        $this->data['form']['fields']['block_description'] = $form->getFieldHtml(
            [
                'type'         => 'textarea',
                'name'         => 'block_description',
                'value'        => $this->data ['description'],
                'attr'         => ' style="height: 50px;"',
                'multilingual' => true,
            ]
        );
        $this->data['form']['text']['block_description'] = $this->language->get('entry_block_description');

        // groups of banners
        $groups = ['' => $this->language->get('text_select')]
            + Banner::select('banner_group_name')
                ->orderBy('banner_group_name')
                ->distinct()
                ->get()
                ?->pluck('banner_group_name', 'banner_group_name')
                ->toArray();

        $this->data['form']['fields']['banner_group_name'] = $form->getFieldHtml(
            [
                'type'    => 'selectbox',
                'name'    => 'banner_group_name',
                'options' => $groups,
                'value'   => $this->data['banner_group_name'],
                'style'   => 'no-save',
            ]
        );
        $this->data['form']['text']['banner_group_name'] = $this->language->get('entry_banner_group_name');
        $this->data['form']['text']['listed_banners'] = $this->language->get('entry_banners_selected');

        //load only prior saved products
        $this->data['banners'] = [];

        $this->data['form']['fields']['listed_banners'] = $form->getFieldHtml(
            [
                'type'        => 'multiselectbox',
                'name'        => 'block_banners[]',
                'value'       => $ids,
                'options'     => $optionsList,
                'style'       => 'no-save chosen',
                'ajax_url'    => $this->html->getSecureURL('listing_grid/banner_manager/banners'),
                'placeholder' => $this->language->get('text_select_from_lookup'),
            ]
        );

        $this->data['form_language_switch'] = $this->html->getContentLanguageSwitcher();
        $this->data['language_code'] = $this->session->data['language'];
        $this->data['help_url'] = $this->gen_help_url('banner_edit');
        $this->data['rl'] = $this->html->getSecureURL(
            'common/resource_library',
            '&object_name=banners&type=image&mode=url'
        );

        $this->view->batchAssign($this->language->getASet());
        $this->view->batchAssign($this->data);

        $this->processTemplate('pages/extension/banner_manager_block_form.tpl');
    }

    protected function validateBlockForm(array $data)
    {
        $required = [];
        if (!$this->user->canModify('extension/banner_manager')) {
            $this->session->data['warning'] =
            $this->error ['warning'] = $this->language->get('error_permission');
        }

        if (!$this->data['block_id']) {
            $this->error ['warning'] =
            $this->session->data['warning'] = 'Block with txt_id "banner_block" does not exists in your database!';
        }

        if ($data) {
            $required = ['block_name', 'block_title'];

            foreach ($data as $name => $value) {
                if (in_array($name, $required) && empty($value)) {
                    $this->error ['warning'] = $this->session->data['warning'] = $this->language->get('error_empty');
                    break;
                }
            }
        }

        foreach ($required as $name) {
            if (!in_array($name, array_keys($data))) {
                return false;
            }
        }

        $this->extensions->hk_ValidateData($this, __FUNCTION__, $data);

        return (!$this->error);
    }
}