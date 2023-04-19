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
use stdClass;

class ControllerResponsesListingGridBannerManagerStat extends AController
{
    public function main()
    {
        $this->loadLanguage('banner_manager/banner_manager');
        $page = (int)$this->request->post['page'] ?: 1;
        $limit = $this->request->post['rows'];
        $sort = $this->request->post['sidx'];
        $order = $this->request->post['sord'];

        $this->data['banner_stat_search_parameters'] = [
            'language_id' => $this->language->getContentLanguageID(),
            'start'       => ($page - 1) * $limit,
            'limit'       => $limit,
            'sort'        => $sort,
            'order'       => $order
        ];

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $results = BannerStat::getStatistic($this->data['banner_stat_search_parameters']);
        $total = $results::getFoundRowsCount();
        $total_pages = $total > 0 ? ceil($total / $limit) : 0;

        $response = new stdClass();
        $response->page = $this->request->post['page'];
        $response->total = $total_pages;
        $response->records = $total;

        $i = 0;
        foreach ($results as $result) {
            $response->rows[$i]['id'] = $result['banner_id'];
            $response->rows[$i]['cell'] = [
                $result['name'],
                $result['banner_group_name'],
                $result['clicked'],
                $result['viewed'],
                $result['percents'],
            ];
            $i++;
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($response));
    }
}