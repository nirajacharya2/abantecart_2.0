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
use abc\core\lib\AJson;
use abc\core\lib\contracts\BaseReportInterface;
use Exception;
use ReflectionClass;
use ReflectionMethod;

class ControllerResponsesListingGridReports extends AController
{
    public $error = [];

    public function main()
    {
        $this->data['response'] = [];
        $report = $this->request->get['report'];
        if (!$report) {
            $this->session->data['error'] = $this->language->get('text_error_report_not_exist');
            abc_redirect($this->html->getSecureURL('report/reports'));
        }

        $env = ABC::getEnv();

        if (!isset($env['REPORTS']) || !isset($env['REPORTS']['REPORTS_LIST'])) {
            $this->session->data['error'] = $report.' '.$this->language->get('text_error_report_not_in_config');
            abc_redirect($this->html->getSecureURL('report/reports'));
        }
        $reportsList = $env['REPORTS']['REPORTS_LIST'];

        if (!$reportsList[$report]) {
            $this->session->data['error'] = $report.' '.$this->language->get('text_error_report_not_in_config');
            abc_redirect($this->html->getSecureURL('report/reports'));
        }

        $className = $reportsList[$report];

        try {
            $reflection = new ReflectionClass($className);

            if (!$reflection->implementsInterface(BaseReportInterface::class)) {
                $this->session->data['error'] = $className.' '.$this->language->get('text_error_class_not_report');
                abc_redirect($this->html->getSecureURL('report/reports'));
            }

            $classObj = new $className;

            if ($reflection->hasMethod('getGridData')) {
                $reflectionMethod = new ReflectionMethod($className, 'getGridData');
                $this->data['response'] = $reflectionMethod->invoke($classObj, $this->request->get, $this->request->post);
            }

        } catch (Exception $e) {
            $this->session->data['error'] = $e->getMessage();
            $this->log->error($e->getMessage());
            abc_redirect($this->html->getSecureURL('report/reports'));
        }
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data['response']));
    }

    public function exportCSV()
    {
        $report = $this->request->get['report'];
        if (!$report) {
            $this->session->data['error'] = $this->language->get('text_error_report_not_exist');
            abc_redirect($this->html->getSecureURL('report/reports'));
        }

        $env = ABC::getEnv();

        if (!isset($env['REPORTS']) || !isset($env['REPORTS']['REPORTS_LIST'])) {
            $this->session->data['error'] = $report.' '.$this->language->get('text_error_report_not_in_config');
            abc_redirect($this->html->getSecureURL('report/reports'));
        }
        $reportsList = $env['REPORTS']['REPORTS_LIST'];

        if (!$reportsList[$report]) {
            $this->session->data['error'] = $report.' '.$this->language->get('text_error_report_not_in_config');
            abc_redirect($this->html->getSecureURL('report/reports'));
        }

        $className = $reportsList[$report];

        try {
            $reflection = new ReflectionClass($className);

            if (!$reflection->implementsInterface(BaseReportInterface::class)) {
                $this->session->data['error'] = $className.' '.$this->language->get('text_error_class_not_report');
                abc_redirect($this->html->getSecureURL('report/reports'));
            }

            $classObj = new $className;


            if ($reflection->hasMethod('exportCSV')) {
                $reflectionMethod = new ReflectionMethod($className, 'exportCSV');
                $reflectionMethod->invoke($classObj, $report, $this->request->get, $this->request->get, true);
            }

        } catch (Exception $e) {
            $this->session->data['error'] = $e->getMessage();
            $this->log->error($e->getMessage());
            abc_redirect($this->html->getSecureURL('report/reports'));
        }

    }

    public function exportExcel()
    {

    }
}
