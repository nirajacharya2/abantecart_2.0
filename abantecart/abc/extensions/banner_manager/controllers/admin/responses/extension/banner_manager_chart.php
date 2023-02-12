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

use abc\core\engine\AController;
use abc\core\lib\AJson;
use abc\extensions\banner_manager\models\BannerStat;


class ControllerResponsesExtensionBannerManagerChart extends AController
{
    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('banner_manager/banner_manager');

        $data = [];

        $data['viewed'] = [];
        $data['clicked'] = [];
        $data['xaxis'] = [];

        $data['viewed']['label'] = $this->language->get('column_viewed');
        $data['clicked']['label'] = $this->language->get('column_clicked');

        $range = $this->request->get['range'] ?? 'month';
        $banner_id = (int)$this->request->get['banner_id'];
        if (!$banner_id) {
            return;
        }

        switch ($range) {
            case 'day':
                for ($i = 0; $i < 24; $i++) {
                    $result = BannerStat::selectRaw('type, COUNT(type) as cnt')
                        ->where('banner_id', '=', $banner_id)
                        ->whereRaw("DATE(time) = DATE(NOW()) AND HOUR(time) = '" . $i . "'")
                        ->groupBy('type')
                        ->groupByRaw('HOUR(time)')
                        ->orderBy('time')
                        ->orderBy('type')
                        ->get()?->toArray();
                    if ($result) {
                        foreach ($result as $row) {
                            $type = $row['type'] == '1' ? 'viewed' : 'clicked';
                            $data[$type]['data'][] = [$i, $row['cnt']];
                        }
                    } else {
                        $data['viewed']['data'][] = [$i, 0];
                        $data['clicked']['data'][] = [$i, 0];
                    }

                    $data['xaxis'][] = [$i, date('H', mktime($i, 0, 0, date('n'), date('j'), date('Y')))];
                }
                $data['xaxisLabel'] = $this->language->get('text_hours');
                break;
            case 'week':
                $date_start = strtotime('-' . date('w') . ' days');

                for ($i = 0; $i < 7; $i++) {
                    $date = date('Y-m-d', $date_start + ($i * 86400));
                    $result = BannerStat::selectRaw('type, COUNT(type) as cnt')
                        ->where('banner_id', '=', $banner_id)
                        ->whereRaw("DATE(time) = '" . $this->db->escape($date) . "'")
                        ->groupBy('type')
                        ->groupByRaw('DATE(time)')
                        ->orderBy('type')
                        ->get()?->toArray();

                    if ($result) {
                        foreach ($result as $row) {
                            $type = $row['type'] == '1' ? 'viewed' : 'clicked';
                            $data[$type]['data'][] = [$i, $row['cnt']];
                        }
                    } else {
                        $data['viewed']['data'][] = [$i, 0];
                        $data['clicked']['data'][] = [$i, 0];
                    }
                    $data['xaxis'][] = [$i, date('D', strtotime($date))];
                }
                $data['xaxisLabel'] = $this->language->get('text_weeks');
                break;
            default:
            case 'month':
                for ($i = 1; $i <= date('t'); $i++) {
                    $date = date('Y') . '-' . date('m') . '-' . $i;

                    $result = BannerStat::selectRaw('type, COUNT(type) as cnt')
                        ->where('banner_id', '=', $banner_id)
                        ->whereRaw("DATE(time) = '" . $this->db->escape($date) . "'")
                        ->groupBy('type')
                        ->groupByRaw('DAY(time)')
                        ->orderBy('type')
                        ->get()?->toArray();

                    if ($result) {
                        foreach ($result as $row) {
                            $type = $row['type'] == '1' ? 'viewed' : 'clicked';
                            $data[$type]['data'][] = [$i, $row['cnt']];
                        }
                    } else {
                        $data['viewed']['data'][] = [$i, 0];
                        $data['clicked']['data'][] = [$i, 0];
                    }

                    $data['xaxis'][] = [$i, date('j', strtotime($date))];
                }
                $data['xaxisLabel'] = $this->language->get('text_days');
                break;
            case 'year':
                for ($i = 1; $i <= 12; $i++) {
                    $result = BannerStat::selectRaw('type, COUNT(type) as cnt')
                        ->where('banner_id', '=', $banner_id)
                        ->whereRaw("YEAR(time) = '" . date("Y") . "' AND MONTH(time) = " . $i)
                        ->groupBy('type')
                        ->groupByRaw('MONTH(time)')
                        ->orderBy('type')
                        ->get()?->toArray();

                    if ($result) {
                        foreach ($result as $row) {
                            $type = $row['type'] == '1' ? 'viewed' : 'clicked';
                            $data[$type]['data'][] = [$i, $row['cnt']];
                        }
                    } else {
                        $data['viewed']['data'][] = [$i, 0];
                        $data['clicked']['data'][] = [$i, 0];
                    }

                    $data['xaxis'][] = [$i, date('M', mktime(0, 0, 0, $i, 1, date('Y')))];
                }
                $data['xaxisLabel'] = $this->language->get('text_months');
                break;
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($data));
    }
}