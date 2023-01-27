<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2023 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */
namespace abc\controllers\admin;

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\engine\AForm;
use abc\core\lib\contracts\BaseReportInterface;
use Exception;
use ReflectionClass;
use ReflectionMethod;

class ControllerPagesReportReports extends AController
{
    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('report/reports');

        $this->document->setTitle($this->language->get('heading_title'));
        $this->data['title'] = $this->language->get('heading_title');

        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([
            'href'    => $this->html->getSecureURL('report/reports'),
            'text'    => $this->language->get('heading_title'),
            'current' => true,
        ]);

        $this->getForm();

        if (isset($this->session->data['error'])) {
            $this->data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        } else {
            $this->data['error_warning'] = $this->error;
        }

        $this->view->batchAssign($this->data);

        $this->processTemplate("pages/report/reports.tpl");

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    private function getForm()
    {
        $form = new AForm('ST');
        $this->data['form']['id'] = 'reportsFrm';
        $this->data['action'] = $this->html->getSecureURL('report/reports/show');

        $form->setForm([
            'form_name' => $this->data['form']['id'],
        ]);

        $this->data['form']['form_open'] = $form->getFieldHtml([
            'type'   => 'form',
            'name'   => $this->data['form']['id'],
            'action' => $this->data['action'],
            'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
        ]);

        $this->data['form']['submit'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'submit',
            'text'  => $this->language->get('text_button_view'),
            'style' => 'button1',
        ]);

        $this->data['text_report'] = $this->language->get('text_report');

        $availableReports = $this->getAvailableReports();

        $this->data['form']['fields']['report'] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'reports',
            'value'   => 0,
            'options' => $availableReports,
        ]);
    }

    private function getAvailableReports()
    {
        $arReports = [
            '0' => $this->language->get('text_select'),
        ];
        $env = ABC::getEnv();

        if (!isset($env['REPORTS']) || !isset($env['REPORTS']['REPORTS_LIST'])) {
            return $arReports;
        }

        $reportsList = $env['REPORTS']['REPORTS_LIST'];

        foreach ($reportsList as $alias => $className) {
            try {
                $reflection = new ReflectionClass($className);

                if (!$reflection->implementsInterface(BaseReportInterface::class)) {
                    continue;
                }

                if ($reflection->hasMethod('getName')) {
                    $obj = new $className;
                    $reflectionMethod = new ReflectionMethod($className, 'getName');
                    $arReports[$alias] = $reflectionMethod->invoke($obj);
                }
            } catch (Exception $e) {
                $this->log->error($e->getMessage());
            }
        }
        return $arReports;
    }

    public function show()
    {
        $this->loadLanguage('report/reports');
        if ($this->request->is_POST()) {
            $post = $this->request->post;
            if (isset($post['reports']) && $post['reports'] === '0') {
                $this->session->data['error'] = $this->language->get('text_error_report_not_selected');
                abc_redirect($this->html->getSecureURL('report/reports'));
            }

            //init controller data
            $this->extensions->hk_InitData($this, __FUNCTION__);

            $env = ABC::getEnv();

            if (!isset($env['REPORTS']) || !isset($env['REPORTS']['REPORTS_LIST'])) {
                $this->session->data['error'] = $post['reports'].' '.$this->language->get('text_error_report_not_in_config');
                abc_redirect($this->html->getSecureURL('report/reports'));
            }
            $reportsList = $env['REPORTS']['REPORTS_LIST'];

            if (!$reportsList[$post['reports']]) {
                $this->session->data['error'] = $post['reports'].' '.$this->language->get('text_error_report_not_in_config');
                abc_redirect($this->html->getSecureURL('report/reports'));
            }

            $className = $reportsList[$post['reports']];

            try {
                $reflection = new ReflectionClass($className);

                if (!$reflection->implementsInterface(BaseReportInterface::class)) {
                    $this->session->data['error'] = $className.' '.$this->language->get('text_error_class_not_report');
                    abc_redirect($this->html->getSecureURL('report/reports'));
                }

                $classObj = new $className;

                if ($reflection->hasMethod('getName')) {
                    $reflectionMethod = new ReflectionMethod($className, 'getName');
                    $headingTitle = $reflectionMethod->invoke($classObj);
                    $this->document->setTitle($headingTitle);

                    $this->document->initBreadcrumb([
                        'href'      => $this->html->getSecureURL('index/home'),
                        'text'      => $this->language->get('text_home'),
                        'separator' => false,
                    ]);
                    $this->document->addBreadcrumb([
                        'href'      => $this->html->getSecureURL('report/reports'),
                        'text'      => 'Reports',
                        'separator' => ' :: ',
                    ]);
                    $this->document->addBreadcrumb([
                        'href'      => $this->html->getSecureURL('report/reports/show'),
                        'text'      => $headingTitle,
                        'separator' => ' :: ',
                        'current'   => true,
                    ]);
                }

                $gridSettings = [
                    'table_id' => $post['reports'],
                    'url'      => $this->html->getSecureURL('r/listing_grid/reports', '&report='.$post['reports']),
                    'multiaction_class' => 'hidden',
                    'multiselect'    => 'false',
                    'search_form' => 'false',
                ];


                if ($reflection->hasMethod('getGridSortName')) {
                    $reflectionMethod = new ReflectionMethod($className, 'getGridSortName');
                    $gridSettings['sortname'] = $reflectionMethod->invoke($classObj);
                }

                if ($reflection->hasMethod('getGridSortOrder')) {
                    $reflectionMethod = new ReflectionMethod($className, 'getGridSortOrder');
                    $gridSettings['sortorder'] = $reflectionMethod->invoke($classObj);
                }

                if ($reflection->hasMethod('getGridColNames')) {
                    $reflectionMethod = new ReflectionMethod($className, 'getGridColNames');
                    $gridSettings['colNames'] = $reflectionMethod->invoke($classObj);
                }

                if ($reflection->hasMethod('getGridColModel')) {
                    $reflectionMethod = new ReflectionMethod($className, 'getGridColModel');
                    $gridSettings['colModel'] = $reflectionMethod->invoke($classObj);
                }

                if ($reflection->hasMethod('getTaskData')) {
                    $reflectionMethod = new ReflectionMethod($className, 'getTaskData');
                    $this->view->assign('taskData', $reflectionMethod->invoke($classObj));
                }

                $grid = $this->dispatch('common/listing_grid', [$gridSettings]);
                $this->view->assign('listing_grid', $grid->dispatchGetOutput());

                $this->view->assign('grid_url', $gridSettings['url']);
                $this->view->assign('export_csv_url',
                    $this->html->getSecureURL('r/listing_grid/reports/exportCSV', '&report=' . $post['reports']));
                $this->view->assign('table_id', $gridSettings['table_id']);

                $this->processTemplate('pages/report/reports_show.tpl');

                //update controller data
                $this->extensions->hk_UpdateData($this, __FUNCTION__);

            } catch (Exception $e) {
                $this->session->data['error'] = $e->getMessage();
                $this->log->error($e->getMessage());
                abc_redirect($this->html->getSecureURL('report/reports'));
            }

        } else {
            $this->session->data['error'] = $this->language->get('text_error_post_is_empty');
            abc_redirect($this->html->getSecureURL('report/reports'));
        }
    }
}
