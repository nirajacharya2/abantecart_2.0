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

use abc\core\engine\AController;
use abc\core\lib\AFilter;
use abc\core\lib\AJson;
use abc\extensions\gdpr\models\admin\extension\ModelExtensionGdpr;
use stdClass;

/**
 * Class ControllerResponsesListingGridGdprHistory
 *
 * @property ModelExtensionGdpr $model_extension_gdpr
 */
class ControllerResponsesListingGridGdprHistory extends AController
{
    public $error = [];
    public $data = [];

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('gdpr/gdpr');
        if (!$this->user->canAccess('extension/gdpr_history')) {
            $response = new stdClass();
            $response->userdata->error = sprintf(
                $this->language->get('error_permission_access'),
                'extension/gdpr_history'
            );
            $this->load->library('json');
            $this->response->setOutput(AJson::encode($response));
            return null;
        }

        $this->loadModel('extension/gdpr');

        $page = $this->request->post ['page']; // get the requested page
        $limit = $this->request->post ['rows']; // get how many rows we want to have into the grid

        $grid_filter_params = ['name'];

        $filter_grid = new AFilter(['method' => 'post', 'grid_filter_params' => $grid_filter_params]);
        $data = $filter_grid->getFilterData();

        $total = $this->model_extension_gdpr->getHistoryTotalRows($data);

        if ($total > 0) {
            $total_pages = ceil($total / $limit);
        } else {
            $total_pages = 0;
        }

        $response = new stdClass ();
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $total;
        $response->userdata = new stdClass();

        $results = $this->model_extension_gdpr->getHistory($data);

        $i = 0;
        foreach ($results as $result) {

            $response->rows [$i] ['id'] = $result['id'];

            switch ($result['request_type']) {
                case 'v':
                    //$response->userdata->classes[ $k ] = 'warning';
                    $type = $this->language->get('gdpr_type_viewed');
                    break;
                case 'd':
                    $type = $this->language->get('gdpr_type_data_downloaded');
                    //$response->userdata->classes[ $k ] = 'success';
                    break;
                case 'r':
                    $type = $this->language->get('gdpr_type_requested');
                    //$response->userdata->classes[ $k ] = 'attention';
                    break;
                case 'e':
                    $type = $this->language->get('gdpr_type_erased');
                    //$response->userdata->classes[ $k ] = 'attention';
                    break;
                default:
                    $type = 'unknown';
            }

            $response->rows [$i] ['cell'] = [
                $result['id'],
                $result ['date_modified'],
                $type,
                $result ['name'],
                $result ['user_agent'],
                $result ['ip'],
            ];

            $i++;
        }

        $this->data['response'] = $response;
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data['response']));

    }

}