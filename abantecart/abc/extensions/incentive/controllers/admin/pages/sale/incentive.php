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
use abc\core\engine\Registry;
use abc\core\lib\AError;
use abc\core\lib\APromotion;
use abc\extensions\incentive\models\Incentive;
use abc\extensions\incentive\models\IncentiveDescription;
use abc\extensions\incentive\modules\conditions\CustomerPostcodes;
use H;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ControllerPagesSaleIncentive extends AController
{
    public $error = [];

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('incentive/incentive');
        $this->document->setTitle($this->language->get('incentive_name'));
        $this->view->assign('help_url', $this->gen_help_url('sale_incentive'));

        $this->document->initBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('index/home'),
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ]
        );
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('sale/incentive'),
                'text'      => $this->language->get('incentive_name'),
                'separator' => ' :: ',
                'current'   => true,
            ]
        );

        $this->view->assign('error_warning', $this->error['warning']);
        $this->view->assign('success', $this->session->data['success']);
        $this->view->assign('heading_title', $this->language->get('incentive_name'));
        if (isset($this->session->data['success'])) {
            unset($this->session->data['success']);
        }

        $grid_settings = [
            'table_id'     => 'incentive_grid',
            'url'          => $this->html->getSecureURL('listing_grid/incentive'),
            'editurl'      => $this->html->getSecureURL('listing_grid/incentive/update'),
            'update_field' => $this->html->getSecureURL('listing_grid/incentive/update_field'),
            'sortname'     => 'priority',
            'sortorder'    => 'asc',
            'actions'      => [
                'edit'   => [
                    'text'     => $this->language->get('text_edit'),
                    'href'     => $this->html->getSecureURL('sale/incentive/edit', '&incentive_id=%ID%'),
                    'children' => array_merge(
                        [
                            'details'    => [
                                'text' => $this->language->get('text_general'),
                                'href' => $this->html->getSecureURL('sale/incentive/edit', '&incentive_id=%ID%')
                            ],
                            'conditions' => [
                                'text' => $this->language->get('text_conditions'),
                                'href' => $this->html->getSecureURL('sale/incentive/edit', '&tab=conditions&incentive_id=%ID%')
                            ],
                            'bonuses'    => [
                                'text' => $this->language->get('text_bonuses'),
                                'href' => $this->html->getSecureURL('sale/incentive/edit', '&tab=bonuses&incentive_id=%ID%')
                            ],
                            'applied'    => [
                                'text' => $this->language->get('incentive_name_applied'),
                                'href' => $this->html->getSecureURL('sale/incentive_applied', '&incentive_id=%ID%')
                            ],
                        ],
                        (array)$this->data['grid_edit_expand']
                    ),
                ],
                'save'   => [
                    'text' => $this->language->get('button_save'),
                ],
                'delete' => [
                    'text' => $this->language->get('button_delete'),
                ],
            ],
        ];

        $grid_settings['colNames'] = [
            'ID',
            $this->language->get('column_name'),
            $this->language->get('column_priority'),
            $this->language->get('column_daterange'),
            $this->language->t('column_run_side', 'Run Side'),
            $this->language->get('column_status'),
        ];
        $grid_settings['colModel'] = [
            [
                'name'   => 'id',
                'index'  => 'incentive_id',
                'width'  => 20,
                'align'  => 'center',
                'search' => false,
            ],
            [
                'name'  => 'name',
                'index' => 'name',
                'width' => 240,
                'align' => 'center',
            ],
            [
                'name'   => 'priority',
                'index'  => 'priority',
                'width'  => 60,
                'align'  => 'center',
                'search' => false,
            ],
            [
                'name'   => 'daterange',
                'index'  => 'daterange',
                'width'  => 150,
                'align'  => 'center',
                'search' => false,
            ],
            [
                'name'     => 'type',
                'index'    => 'type',
                'width'    => 80,
                'align'    => 'center',
                'sortable' => false,
                'search'   => false,
            ],
            [
                'name'   => 'status',
                'index'  => 'status',
                'align'  => 'center',
                'search' => false,
            ],
        ];

        $grid = $this->dispatch('common/listing_grid', [$grid_settings]);
        $this->view->assign('listing_grid', $grid->dispatchGetOutput());
        $this->view->assign('grid_url', $this->html->getSecureURL('listing_grid/incentive'));

        $this->document->setTitle($this->language->get('incentive_name'));
        $this->view->assign('insert', $this->html->getSecureURL('sale/incentive/insert'));
        $this->view->assign('form_language_switch', $this->html->getContentLanguageSwitcher());

        $this->processTemplate('pages/sale/incentive.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

    }

    public function insert()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('incentive/incentive');

        //need to reset exclude list for multivalue list which can be set by
        // other multivalue lists (for ex. form of downloads for products)
        $this->session->data['multivalue_excludes'] = [];

        $this->document->setTitle($this->language->get('incentive_name'));

        if ($this->request->is_POST()) {
            $post = $this->prepareData($this->request->post);
            if ($this->validateForm('general', $post)) {
                $this->data['incentive_id'] = Incentive::addIncentive($post);
                if (!$this->data['incentive_id']) {
                    $this->session->data ['warning'] = 'Oops. Unexpected error occurred. Please see error log for details.';
                } else {
                    $this->session->data ['success'] = $this->language->get('text_success_create');
                    $this->extensions->hk_ProcessData($this, __FUNCTION__);
                    abc_redirect(
                        $this->html->getSecureURL(
                            'sale/incentive/edit',
                            '&tab=conditions&incentive_id=' . $this->data['incentive_id']
                        )
                    );
                }
            }
        }

        $this->data['error'] = $this->error;
        $this->data = array_merge($this->data, $this->request->post);

        $tabs['general'] = [
            'href'   => 'Javascript: void(0);',
            'text'   => $this->language->get('text_general'),
            'active' => true,
        ];

        $this->data['tabs'] = $this->processTabs($tabs);
        $this->getFormGeneral();

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function edit()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('incentive/incentive');

        $this->document->setTitle($this->language->get('incentive_name'));
        $this->data['heading_title'] = $this->language->get('incentive_name');
        $incentive_id = (int)$this->request->get['incentive_id'];

        //need to reset exclude list for multivalue list which can be set
        // by other multivalue lists (for ex. form of downloads for products)
        $this->session->data['multivalue_excludes'] = [];

        // saving
        if ($this->request->is_POST()) {
            $post = $this->prepareData($this->request->post);
            $post['incentive_id'] = $incentive_id;
            if ($this->validateForm($this->request->get['tab'], $post)) {
                $result = Incentive::editIncentive($incentive_id, $post);
                if ($result) {
                    $this->session->data ['success'] = $this->language->get('text_success_edit');
                    abc_redirect(
                        $this->html->getSecureURL(
                            'sale/incentive/edit',
                            '&tab=' . $this->request->get['tab'] . '&incentive_id=' . $incentive_id
                        )
                    );
                }
                $this->session->data ['warning'] = 'Oops. Unexpected error occurred. Please see error log for details';
            }
        }

        $incentive = (array)Incentive::with('description')->find($incentive_id)?->toArray();
        //put it into array to use info inside subform rendering
        $this->data['incentive_data'] = $incentive;

        if ($incentive) {
            $desc = $incentive['description'];
            unset($incentive['description']);
            $this->data = array_merge($this->data, $incentive);
            $this->data = array_merge($this->data, $desc);
        }
        $this->data['error'] = $this->error;

        $tabs['general'] = [
            'name'       => 'incentive_edit',
            'text'       => $this->language->get('text_general'),
            'href'       => $this->html->getSecureURL(
                'sale/incentive/edit',
                '&tab=general&incentive_id=' . $incentive_id
            ),
            'sort_order' => 0
        ];

        $tabs['conditions'] = [
            'name' => 'incentive_conditions_edit',
            'href' => $this->html->getSecureURL(
                'sale/incentive/edit',
                '&tab=conditions&incentive_id=' . $incentive_id
            ),
            'text' => $this->language->get('text_conditions'),
        ];
        $tabs['bonuses'] = [
            'name' => 'incentive_bonuses_edit',
            'href' => $this->html->getSecureURL(
                'sale/incentive/edit',
                '&tab=bonuses&incentive_id=' . $incentive_id
            ),
            'text' => $this->language->get('text_bonuses'),
        ];


        if (isset ($this->session->data['warning'])) {
            $this->data ['error_warning'] = $this->session->data['warning'];
            $this->session->data['warning'] = '';
        } else {
            $this->data['error_warning'] = '';
        }

        $this->view->assign('success', $this->session->data['success']);
        if (isset($this->session->data['success'])) {
            unset($this->session->data['success']);
        }

        switch ($this->request->get['tab']) {
            case 'conditions':
                $tabs['conditions']['active'] = true;
                $this->getFormConditions();
                break;
            case 'bonuses':
                $tabs['bonuses']['active'] = true;
                if (!$this->data['conditions']['conditions']) {
                    abc_redirect($this->html->getSecureURL('sale/incentive/edit', '&tab=conditions&incentive_id=' . $incentive_id));
                }
                $this->getFormBonuses();
                break;
            default:
                $tabs['general']['active'] = true;
                $this->getFormGeneral();
                break;
        }

        $this->data['tabs'] = $this->processTabs($tabs);
        $this->view->batchAssign($this->data);

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function prepareData($post)
    {
        $post['language_id'] = $post['language_id'] ?? Registry::language()->getContentLanguageID();
        if (isset($post['limit_of_usages'])) {
            $post['limit_of_usages'] = (int)$post['limit_of_usages'];
        }
        if (isset($post['image_resource_id'])) {
            $post['resource_id'] = (int)$post['image_resource_id'] ?: null;
        }
        if (isset($post['conditions']['conditions'])) {
            foreach ($post['conditions']['conditions'] as $condKey => $items) {
                foreach ($items as $k => $rule) {
                    if (in_array($rule['operator'], ['in', 'notin'])) {
                        $isStringValue = is_string($rule['value']);
                        $temp = array_map(
                            function ($v) {
                                return is_string($v) ? trim($v) : $v;
                            },
                            (is_array($rule['value']) ? $rule['value'] : explode(',', $rule['value']))
                        );
                        $temp = array_filter($temp);

                        $post['conditions']['conditions'][$condKey][$k]['value'] = $isStringValue ? implode(',', $temp) : $temp;
                    } else {
                        $post['conditions']['conditions'][$condKey][$k]['value'] = is_string($rule['value']) ? trim($rule['value']) : $rule['value'];
                    }
                }
            }
        }

        $this->data['prepared'] = $post;
        $this->extensions->hk_ProcessData($this, __FUNCTION__, $this->data['prepared']);
        return $this->data['prepared'];
    }

    protected function processTabs($tabs)
    {
        $obj = $this->dispatch('responses/common/tabs', [
                'group'    => 'incentives',
                'parentRt' => 'sale/incentive/edit',
                'data'     => ['tabs' => $tabs]
            ]
        );

        return $obj->dispatchGetOutput('responses/common/tabs');
    }

    public function delete()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $incentiveId = (int)$this->request->get['incentive_id'];
        if ($incentiveId) {
            Incentive::find($incentiveId)?->delete();
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        abc_redirect($this->html->getSecureURL('sale/incentive'));
    }

    protected function getFormGeneral()
    {
        $this->document->initBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('index/home'),
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ]
        );
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('sale/incentive'),
                'text'      => $this->language->get('incentive_name'),
                'separator' => ' :: ',
            ]
        );

        $this->data ['cancel_href'] = $this->html->getSecureURL('sale/incentive');
        if (!isset ($this->request->get ['incentive_id'])) {
            $this->data ['action'] = $this->html->getSecureURL('sale/incentive/insert', '&tab=general');
            $this->data ['form_title'] = $this->language->get('text_create');
            $this->data['heading_title'] = $this->language->get('text_create');
            $this->data ['update'] = '';
            $form = new AForm ('ST');
        } else {
            $this->data ['action'] = $this->html->getSecureURL(
                'sale/incentive/edit',
                '&tab=general&incentive_id=' . $this->request->get ['incentive_id']
            );
            $this->data ['form_title'] = $this->language->get('text_edit') . ' ' . $this->data['name'];
            $this->data ['update'] = $this->html->getSecureURL(
                'listing_grid/incentive/update_field',
                '&incentive_id=' . $this->request->get ['incentive_id']
            );
            $form = new AForm ('HS');
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
                'form_name' => 'incentiveFrm',
                'update'    => $this->data ['update'],
            ]
        );

        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'incentiveFrm',
                'action' => $this->data ['action'],
                'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
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
                'href' => $this->html->getSecureUrl('sale/incentive'),
                'text' => $this->language->get('button_cancel'),
            ]
        );
        $this->data['form_title'] = $this->language->get('text_general');
        $this->data['form']['fields']['status']['field'] = $form->getFieldHtml(
            [
                'type'    => 'checkbox',
                'name'    => 'status',
                'value'   => 1,
                'checked' => (!isset($this->data['status']) || (bool)$this->data['status']),
                'style'   => 'btn_switch',
            ]
        );
        $this->data['form']['fields']['status']['text'] = $this->language->get('incentive_status');

        $this->data['form']['fields']['name']['field'] = $form->getFieldHtml(
            [
                'type'     => 'input',
                'name'     => 'name',
                'value'    => $this->data['name'],
                'required' => true,
            ]
        );
        $this->data['form']['fields']['name']['text'] = $this->language->get('entry_incentive_name');

        $resources_scripts = $this->dispatch(
            'responses/common/resource_library/get_resources_scripts',
            [
                'object_name' => '',
                'object_id'   => '',
                'types'       => ['image'],
            ]
        );
        $this->data['resources_scripts'] = $resources_scripts->dispatchGetOutput();

        $this->data['form']['fields']['resource']['field'] = $form->getFieldHtml(
            [
                'type'        => 'resource',
                'name'        => 'image',
                'resource_id' => $this->data['resource_id'],
                'rl_type'     => 'image',
            ]
        );
        $this->data['form']['fields']['resource']['text'] = $this->language->t('entry_incentive_image', 'Image');

        $this->data['form']['fields']['priority']['field'] = $form->getFieldHtml(
            [
                'type'  => 'input',
                'name'  => 'priority',
                'value' => (isset($this->data['priority']) ? (int)$this->data['priority'] : 1),
                'style' => 'small-field',
                '',
            ]
        );
        $this->data['form']['fields']['priority']['text'] = $this->language->get('entry_incentive_priority');

        $this->data['form']['fields']['description_short']['field'] = $form->getFieldHtml(
            [
                'type'  => 'textarea',
                'name'  => 'description_short',
                'value' => $this->data['description_short'],
            ]
        );
        $this->data['form']['fields']['description_short']['text'] = $this->language->get(
            'entry_incentive_description_short'
        );

        $this->data['form']['fields']['description']['field'] = $form->getFieldHtml(

            [
                'type'         => 'texteditor',
                'name'         => 'description',
                'value'        => $this->data['description'],
                'multilingual' => true,
            ]
        );
        $this->data['form']['fields']['description']['text'] = $this->language->get(
            'entry_incentive_description'
        );

        $this->data['form']['fields']['stop']['field'] = $form->getFieldHtml(
            [
                'type'  => 'checkbox',
                'name'  => 'stop',
                'value' => $this->data['stop'],
                'style' => 'btn_switch',
            ]
        );
        $this->data['form']['fields']['stop']['text'] = $this->language->get('entry_incentive_stop');

        $this->data['form']['fields']['daterange']['field'] .= $form->getFieldHtml(
            [
                'type'       => 'date',
                'name'       => 'start_date',
                'value'      => ($this->data['start_date']
                    ? H::dateISO2Display($this->data['start_date'], $this->language->get('date_format_short'))
                    : null),
                'default'    => date($this->language->get('date_format_short')),
                'dateformat' => H::format4Datepicker($this->language->get('date_format_short')),
                'required'   => true,
            ]
        );

        $this->data['form']['fields']['daterange']['field'] .= $form->getFieldHtml(
            [
                'type'       => 'date',
                'name'       => 'end_date',
                'value'      => ($this->data['end_date']
                    ? H::dateISO2Display($this->data['end_date'], $this->language->get('date_format_short'))
                    : null),
                'default'    => '',
                'dateformat' => H::format4Datepicker($this->language->get('date_format_short')),
            ]
        );
        $this->data['form']['fields']['daterange']['text'] = $this->language->get('entry_incentive_daterange');

        $this->data['form']['fields']['limit_of_usages']['field'] = $form->getFieldHtml(
            [
                'type'  => $this->data['conditions']['condition_type'] == 'background' ? 'hidden' : 'input',
                'name'  => 'limit_of_usages',
                'value' => ((int)$this->data['limit_of_usages'] ?: ''),
                'style' => 'small-field',
            ]
        );

        $this->data['form']['fields']['limit_of_usages']['text'] =
            $this->language->get('entry_incentive_limit_of_usages')
            . (!isset($this->data['conditions']['condition_type'])
                ? ' (' . $this->language->t('incentive_text_storefront_only', 'for storefront side only') . ')'
                : '');

        $this->data['incentive_id'] = $this->request->get['incentive_id'] ?: '-1';

        $this->data['subform_url'] = $this->html->getSecureURL(
            'listing_grid/incentive/getsubform',
            '&incentive_id=' . $this->data['incentive_id']
        );

        $this->view->batchAssign($this->language->getASet());
        $this->view->batchAssign($this->data);
        $this->view->assign('form_language_switch', $this->html->getContentLanguageSwitcher());
        $this->view->assign('language_code', $this->session->data['language']);
        $this->view->assign('help_url', $this->gen_help_url('incentive_edit'));
        $this->view->assign(
            'rl',
            $this->html->getSecureURL(
                'common/resource_library',
                '&object_name=incentives&type=image&mode=url'
            )
        );

        $this->processTemplate('pages/sale/incentive_form.tpl');
    }

    protected function getFormConditions()
    {
        $this->document->initBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('index/home'),
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ]
        );
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('sale/incentive'),
                'text'      => $this->language->get('incentive_name'),
                'separator' => ' :: ',
            ]
        );

        $this->document->addStyle(
            [
                'href'  => $this->view->templateResource('assets/css/incentive.css'),
                'rel'   => 'stylesheet',
                'media' => 'screen',
            ]
        );

        $this->data ['cancel_href'] = $this->html->getSecureURL('sale/incentive');

        $this->data ['action'] = $this->html->getSecureURL(
            'sale/incentive/edit',
            '&tab=conditions&incentive_id=' . $this->request->get ['incentive_id']
        );

        $this->data ['form_title'] = $this->language->get('text_edit') . ' ' . $this->data['name'];
        $this->data ['update'] = $this->html->getSecureURL(
            'listing_grid/incentive/update_field',
            '&incentive_id=' . $this->request->get ['incentive_id']
        );
        $form = new AForm ('HT');

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
                'form_name' => 'incentiveFrm',
                'update'    => $this->data ['update'],
            ]
        );
        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'incentiveFrm',
                'action' => $this->data ['action'],
                'attr'   => 'data-confirm-exit="true" class="aform form-horizontal" ',
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
                'href' => $this->html->getSecureUrl('sale/incentive'),
                'text' => $this->language->get('button_cancel'),
            ]
        );
        $this->data['form_title'] = $this->language->get('text_conditions');
        // relations between conditions
        $this->data['conditions_relation']['fields']['if'] = [
            'label' => $this->language->get('text_if_1'),
            'html'  => $form->getFieldHtml(
                [
                    'type'    => 'selectbox',
                    'name'    => 'conditions[relation][if]',
                    'options' => [
                        'all' => $this->language->get('text_all'),
                        'any' => $this->language->get('text_any'),
                    ],
                    'value'   => $this->data['conditions']['relation']['if'] ?? '',
                ]
            ),
        ];

        $this->data['conditions_relation']['fields']['value'] = [
            'label' => $this->language->get('text_if_2'),
            'html'  => $form->getFieldHtml(
                [
                    'type'    => 'selectbox',
                    'name'    => 'conditions[relation][value]',
                    'options' => [
                        'true'  => $this->language->get('text_true'),
                        'false' => $this->language->get('text_false'),
                    ],
                    'value'   => $this->data['conditions']['relation']['value'] ?? '',
                ]
            ),
        ];

        /** @var APromotion $promo */
        $promo = ABC::getObjectByAlias('APromotion');
        // 	conditions
        $this->data['form']['fields'] = [];
        $conditionSection = 'storefront';
        if (isset($this->data['conditions']['conditions'])) {
            $conditionSection = $this->data['conditions']['condition_type'];
            foreach ($this->data['conditions']['conditions'] as $condKey => $items) {
                $condition = $promo->getConditionObjectByKey($condKey);
                if (!$condition) {
                    $error = new AError('Condition with key ' . $condKey . ' not found');
                    $error->toLog();
                    continue;
                }
                $condition->setIncentiveInfo($this->data['incentive_data']);
                $items = array_values($items);
                foreach ($items as $idx => $rule) {
                    $inData = [
                        'idx'    => $idx,
                        'params' => [
                            'operator' => $rule['operator'],
                            'value'    => $rule['value'],
                        ],
                    ];

                    $render = $condition->renderSubForm($inData);
                    if ($render) {
                        $this->data['form']['fields'][$condKey][$idx]['id'] = $idx;
                        $this->data['form']['fields'][$condKey][$idx]['label'] = $render['label'];
                        $this->data['form']['fields'][$condKey][$idx]['html'] = $render['html'];
                    }
                }
            }
        }

        $this->data['condition_object']['type'] = $form->getFieldHtml(
            [
                'type'    => 'selectbox',
                'name'    => 'condition_type',
                'options' => [
                    'background' => $this->language->get('incentive_type_background'),
                    'storefront' => $this->language->get('incentive_type_storefront'),
                ],
                'style'   => 'small-field',
                'attr'    => ($this->data['form']['fields'] ? ' disabled ' : ''),
                'value'   => $conditionSection,
            ]
        );

        $this->data['condition_object']['label'] = $this->language->get('entry_condition_object');
        $this->data['condition_object']['html'] = $form->getFieldHtml(
            [
                'type'    => 'selectbox',
                'name'    => 'condition_object',
                'options' => ['' => $this->language->get('text_select')] + $promo->getConditionList($conditionSection),
                'value'   => $this->data['incentive_type'],
            ]
        );
        $this->data['condition_object']['url'] = $this->html->getSecureURL('extension/incentive_condition_fields/getConditionsListField');

        $this->data['condition_subform_url'] = $this->html->getSecureURL(
            'extension/incentive_condition_fields',
            '&incentive_id=' . $this->data['incentive_id']
        );

        $this->view->batchAssign($this->language->getASet());
        $this->view->batchAssign($this->data);
        $this->view->assign('help_url', $this->gen_help_url('incentive_edit'));
        $this->view->assign(
            'rl',
            $this->html->getSecureURL(
                'common/resource_library',
                '&object_name=incentives&type=image&mode=url'
            )
        );

        $this->processTemplate('pages/sale/incentive_form_conditions.tpl');
    }

    protected function getFormBonuses()
    {
        $this->document->initBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('index/home'),
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ]
        );
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('sale/incentive'),
                'text'      => $this->language->get('incentive_name'),
                'separator' => ' :: ',
            ]
        );

        $this->document->addStyle(
            [
                'href'  => $this->view->templateResource('assets/css/incentive.css'),
                'rel'   => 'stylesheet',
                'media' => 'screen',
            ]
        );

        $this->data ['cancel_href'] = $this->html->getSecureURL('sale/incentive');

        $this->data ['action'] = $this->html->getSecureURL(
            'sale/incentive/edit',
            '&tab=bonuses&incentive_id=' . $this->request->get['incentive_id']
        );
        $this->data ['form_title'] = $this->language->get('text_edit') . ' ' . $this->data['name'];
        $this->data ['update'] = $this->html->getSecureURL(
            'listing_grid/incentive/update_field',
            '&incentive_id=' . $this->request->get['incentive_id']
        );
        $form = new AForm ('HT');

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
                'form_name' => 'incentiveFrm',
                'update'    => $this->data ['update'],
            ]
        );
        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'incentiveFrm',
                'action' => $this->data ['action'],
                'attr'   => 'data-confirm-exit="true"  class="aform form-horizontal"',
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
                'href' => $this->html->getSecureUrl('sale/incentive'),
                'text' => $this->language->get('button_cancel'),
            ]
        );
        $this->data['form_title'] = $this->language->get('text_bonuses');

        $this->data['form']['fields'] = [];
        $href = '';

        $conditionSection = $this->data['conditions']['condition_type'];

        if (isset($this->data['bonuses'])) {
            /** @var APromotion $promo */
            $promo = ABC::getObjectByAlias('APromotion');
            foreach ($this->data['bonuses'] as $idx => $rule) {

                $bonus = $promo->getBonusObjectByKey($idx);
                if (!$bonus) {
                    $error = new AError('Bonus with key ' . $idx . ' not found');
                    $error->toLog();
                    continue;
                }

                if (!in_array($bonus->getSection(), ['both', $conditionSection])) {
                    continue;
                }

                $inData = [
                    'idx'    => $idx,
                    'params' => $rule,
                ];

                $render = $bonus->renderSubForm($inData);
                if ($render) {
                    $this->data['form']['fields'][$idx]['id'] = $idx;
                    $this->data['form']['fields'][$idx]['label'] = $render['label'];
                    $this->data['form']['fields'][$idx]['html'] = $render['html'];
                }
            }

        }

        $this->data['list_content_url'] = $this->html->getSecureUrl('listing_grid/incentive/discount_products', $href);
        // selectbox with condition's objects list
        /** @var APromotion $promo */
        $promo = ABC::getObjectByAlias('APromotion');
        $this->data['bonus_object']['field'] = $form->getFieldHtml(
            [
                'type'    => 'selectbox',
                'name'    => 'bonus_object',
                'options' => ['' => $this->language->get('text_select')] + $promo->getBonusList($conditionSection),
                'value'   => $this->data['incentive_type'],
            ]
        );

        $this->data['bonus_object']['text'] = $this->language->get('entry_bonus_object');

        $this->data['bonus_url'] = $this->html->getSecureURL(
            'extension/incentive_bonus_fields',
            '&incentive_id=' . $this->data['incentive_id']
        );

        $this->view->batchAssign($this->language->getASet());
        $this->view->batchAssign($this->data);
        $this->view->assign('help_url', $this->gen_help_url('incentive_edit'));

        $this->processTemplate('pages/sale/incentive_form_bonuses.tpl');

    }

    protected function validateForm(string $section, array $inData)
    {
        if (!$this->user->hasPermission('modify', 'sale/incentive')) {
            $this->error ['warning'] = $this->language->get('error_permission');
        }

        if ($section == 'bonuses' && !$inData['bonuses']) {
            $this->error ['warning'] = $this->language->get('incentive_error_empty_bonuses');
        } elseif ($section == 'conditions' && !isset($inData['conditions']['conditions'])
        ) {
            $this->error ['warning'] = $this->language->get('incentive_error_empty_conditions');
        }

        if ($inData['incentive_id']) {
            $incentive = Incentive::find($inData['incentive_id']);
            $iDesc = $incentive?->description()->getModel();
            if (!$incentive) {
                $this->error['warning'] = 'Incentive #' . $inData['incentive_id'] . ' not found.';
                return false;
            }
        } else {
            $incentive = new Incentive();
            $iDesc = new IncentiveDescription();
        }

        foreach ([$incentive, $iDesc] as $mdl) {
            try {
                $mdl->validate($inData);
            } catch (ValidationException $e) {
                H::SimplifyValidationErrors($mdl->errors()['validation'], $this->error);
            }
        }

        if (isset($this->error['start_date'])) {
            $this->error['daterange'] = $this->error['start_date'];
        }

        if (isset($inData['start_date']) && isset($inData['end_date'])
            && Carbon::parse($inData['start_date'])->timestamp > Carbon::parse($inData['end_date'])->timestamp) {
            $this->error['daterange'] = $this->language->t('error_incorrect_date_range', 'Incorrect Date Range');
        }

        if (isset($inData['conditions'])) {
            $this->validateConditionRules($inData);
        }

        if (isset($inData['bonuses'])) {
            $this->validateBonusRules($inData);
        }

        $this->extensions->hk_ValidateData($this);
        return (!$this->error);
    }


    protected function validateConditionRules($inData)
    {
        /** @var APromotion $promo */
        $promo = ABC::getObjectByAlias('APromotion');
        foreach ($inData['conditions']['conditions'] as $condKey => $items) {
            /** @var CustomerPostcodes $condObj */
            $condObj = $promo->getConditionObjectByKey((string)$condKey);
            if (!$condObj) {
                $this->error['warning'] = 'Condition ' . $condKey . ' not found' . "\n";
                return;
            }

            foreach ($items as $idx => $rule) {
                if (!isset($rule['value'])) {
                    $this->error['warning'] .= "Condition " . $condObj->getName() . ": value is empty!\n";
                    continue;
                }

                if (!defined(get_class($condObj) . '::valueValidationPattern') || !$condObj::valueValidationPattern) {
                    continue;
                }
                $values = in_array($rule['operator'], ['in', 'notin'])
                    ? array_map('trim', (is_array($rule['value']) ? $rule['value'] : explode(',', $rule['value'])))
                    : [trim($rule['value'])];
                foreach ($values as $condValue) {
                    if (!preg_match($condObj::valueValidationPattern, $condValue)) {
                        $this->error['warning'] = 'Condition ' . $condObj->getName()
                            . ": value '" . $condValue . "' is invalid! (validation pattern is "
                            . $condObj::valueValidationPattern . ")";
                    }
                }
            }
        }
    }

    protected function validateBonusRules($inData)
    {
        /** @var APromotion $promo */
        $promo = ABC::getObjectByAlias('APromotion');
        foreach ($inData['bonuses'] as $bonusKey => $rule) {
            /** @var CustomerPostcodes $bonusObj */
            $bonusObj = $promo->getBonusObjectByKey((string)$bonusKey);
            if (!$bonusObj) {
                $this->error['warning'] = 'Bonus ' . $bonusKey . ' not found' . "\n";
                return;
            }

            if (!$rule['value'] && !$rule['products']) {
                $this->error['warning'] .= "Bonus " . $bonusObj->getName() . ": value is empty!\n";
            }
        }
    }
}