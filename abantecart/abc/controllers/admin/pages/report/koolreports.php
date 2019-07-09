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

class ControllerPagesReportKoolReports extends AController
{

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        //???? try to make directory with temporary assets
        if(!is_dir(ABC::env('DIR_PUBLIC').'vendor'.DS.'koolreport'.DS)){
            @mkdir(ABC::env('DIR_PUBLIC').'vendor'.DS.'koolreport'.DS, 0775);
        }

        if (!$this->request->get['report']) {
            $this->getReportList();
        } else {
            $this->showReport($this->request->get['report']);
        }
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function getReportList()
    {
        $this->data['report_list'] = $this->seekAllReports();
        $this->loadLanguage('report/reports');

        $this->document->setTitle($this->language->get('heading_title'));
        $this->data['title'] = $this->language->get('heading_title');

        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([

            'href'      => $this->html->getSecureURL('report/koolreports'),
            'text'      => 'All Reports',
            'separator' => ' :: ',
            'current'   => true,
        ]);
        $this->document->setTitle('All Reports');
        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/report/list.tpl');
    }

    protected function showReport($reportHash)
    {
        $report = $this->getReportPathByHash($reportHash);
        $file = $report['path'].'/render.php';
        $report_html = include $file;

        $reportName = $this->getNameFromDirName(dirname($file));
        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('report/koolreports'),
            'text'      => 'All Reports',
            'separator' => ' :: ',
            'current'   => false,
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('report/koolreports', '&report='.$this->request->get['report']),
            'text'      => 'Report: '.$reportName,
            'separator' => ' :: ',
            'current'   => true,
        ]);

        $this->document->setTitle($reportName);
        $this->response->setOutput($report_html);
    }

    protected function seekAllReports()
    {
        $core_dirs = $this->getReportDirs(ABC::env('DIR_MODULES').'reports');
        $extDirs = [];
        $extensionReportDirs = glob(ABC::env('DIR_APP_EXTENSIONS').'*/modules/reports/*', GLOB_ONLYDIR);
        foreach ((array)$extensionReportDirs as $dir) {
            if ($this->config->get(basename(realpath($dir.'/../../../')).'_status')) {
                $extDirs = array_merge($extDirs, $this->getReportDirs($dir));
            }
        }
        $output = array_merge_recursive((array)$core_dirs, ['reports' => $extDirs]);

        return $output;
    }

    protected function getReportDirs($path)
    {
        $output = [];
        $path = rtrim($path, DS);
        $dirs = glob($path.DS.'*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $group = basename(dirname($dir));
            if (is_file($dir.DS.'render.php')) {
                $hash = md5($dir);
                $output[$group][] = [
                    'name' => $this->getNameFromDirName($dir),
                    'dir'  => $dir,
                    'path' => $dir.DS,
                    'hash' => $hash,
                    'url'  => $this->html->getSecureURL('report/koolreports', '&report='.$hash),
                ];
            } else {
                $output[$group] = array_merge((array)$output[$group], $this->getReportDirs($dir));
            }
        }

        return $output;
    }

    protected function getReportPathByHash($hash)
    {
        $all_reports = $this->seekAllReports();
        foreach ($all_reports['reports'] as $group => $items) {
            foreach ($items as $item) {
                if ($item['hash'] == $hash) {
                    return $item;
                }
            }
        }
        return [];
    }

    protected function getNameFromDirName($dir)
    {
        return implode(" ", preg_split('/(?=[A-Z])/', basename($dir)));
    }
}
