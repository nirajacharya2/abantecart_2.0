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
use abc\core\engine\Registry;
use abc\core\lib\AError;
use abc\core\lib\AFilter;
use abc\core\lib\AJson;
use abc\core\lib\JobManager;
use H;
use stdClass;

class ControllerResponsesListingGridJob extends AController
{
    /**
     * @var JobManager
     */
    protected $jm;

    public function __construct(Registry $registry, int $instance_id, string $controller, $parent_controller = '')
    {
        parent::__construct($registry, $instance_id, $controller, $parent_controller);
        $this->jm = ABC::getObjectByAlias('JobManager', [$registry]);
    }

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('tool/job');
        if (!$this->user->canAccess('tool/job')) {
            $response = new stdClass();
            $response->userdata = new stdClass();
            $response->userdata->error = sprintf($this->language->get('error_permission_access'), 'tool/job');
            $this->load->library('json');
            $this->response->setOutput(AJson::encode($response));
            return null;
        }

        $page = $this->request->post ['page']; // get the requested page
        $limit = $this->request->post ['rows']; // get how many rows we want to have into the grid

        //Prepare filter config
        $grid_filter_params = array_merge(['name'], (array) $this->data['grid_filter_params']);
        $filter = new AFilter(['method' => 'post', 'grid_filter_params' => $grid_filter_params]);
        $filter_data = $filter->getFilterData();

        $total = $this->jm->getTotalJobs($filter_data);
        if ($total > 0) {
            $total_pages = ceil($total / $limit);
        } else {
            $total_pages = 0;
        }
        $results = $this->jm->getJobs($filter_data);

        $response = new stdClass ();
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $total;
        $response->userdata = new stdClass();

        $i = 0;
        foreach ($results as $result) {
            $id = $result ['job_id'];
            $response->rows [$i] ['id'] = $id;
            $status = $result['status'];
            //if job works more than 30min - we think it's stuck
            if ($status == 2 && time() - H::dateISO2Int($result['start_time']) > 1800) {
                $status = -1;
            }

            switch ($status) {
                case -1: // stuck
                    $response->userdata->classes[$id] = 'warning disable-run disable-edit disable-restart';
                    $text_status = $this->language->get('text_stuck');
                    break;
                case $this->jm::STATUS_READY:
                    $response->userdata->classes[$id] = 'success disable-restart  disable-continue disable-edit';
                    $text_status = $this->language->get('text_ready');
                    break;
                case $this->jm::STATUS_RUNNING:
                    //disable all buttons for running jobs
                    $response->userdata->classes[$id] =
                        'attention disable-run disable-continue disable-restart disable-edit disable-delete';
                    $text_status = $this->language->get('text_running');
                    break;
                case $this->jm::STATUS_FAILED:
                    $response->userdata->classes[$id] = 'attention disable-run disable-restart';
                    $text_status = $this->language->get('text_failed');
                    break;
                case $this->jm::STATUS_SCHEDULED:
                    $response->userdata->classes[$id] = 'success disable-restart disable-continue disable-edit';
                    $text_status = $this->language->get('text_scheduled');
                    break;
                case $this->jm::STATUS_COMPLETED:
                    $response->userdata->classes[$id] = 'disable-run disable-continue disable-edit';
                    $text_status = $this->language->get('text_completed');
                    break;
                case $this->jm::STATUS_INCOMPLETE:
                    $response->userdata->classes[$id] = 'disable-run disable-restart disable-edit';
                    $text_status = $this->language->get('text_incomplete');
                    break;
                default: // disabled
                    $response->userdata->classes[$id] = 'attention disable-run disable-restart '
                        .'disable-continue disable-edit disable-delete';
                    $text_status = $this->language->get('text_disabled');
            }

            $response->rows [$i] ['cell'] = [
                $result ['job_id'],
                $result ['job_name'],
                $text_status,
                H::dateISO2Display(
                    $result ['start_time'],
                    $this->language->get('date_format_short').' '.$this->language->get('time_format')
                ),
                H::dateISO2Display(
                    $result ['date_modified'],
                    $this->language->get('date_format_short').' '.$this->language->get('time_format')
                ),
            ];
            $i++;
        }
        $this->data['response'] = $response;

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data['response']));
    }

    public function restart()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->load->library('json');
        $this->load->language('tool/job');
        $this->response->addJSONHeader();
        $job_id = (int) $this->request->post_or_get('job_id');

        $job = $this->jm->getJobById($job_id);

        if (!$job_id || !$job) {
            $err = new AError('Task runtime error');
            return $err->toJSONResponse(
                'APP_ERROR_402',
                [
                    'error_text' => $this->language->get('text_job_not_found'),
                ]
            );
        }

        //remove job without steps
        if (!$job['steps']) {
            $this->jm->deleteJob($job_id);
            $err = new AError('Task runtime error');
            return $err->toJSONResponse(
                'APP_ERROR_402',
                ['error_text' => $this->language->get('text_empty_job')]
            );
        }

        //check status
        if (!in_array(
            $job['status'],
            [
                $this->jm::STATUS_RUNNING,
                $this->jm::STATUS_FAILED,
                $this->jm::STATUS_COMPLETED,
                $this->jm::STATUS_INCOMPLETE,
            ]
        )
        ) {
            $err = new AError('Task runtime error');
            return $err->toJSONResponse(
                'APP_ERROR_402',
                ['error_text' => $this->language->get('text_forbidden_to_restart')]
            );
        }

        //if some of steps have sign for interruption on fail - restart whole job
        if ($this->request->get['continue']) {
            $restart_all = false;
            foreach ($job['steps'] as $step) {
                if ($step['settings']['interrupt_on_step_fault'] === true) {
                    $restart_all = true;
                    break;
                }
            }
        } else {
            $restart_all = true;
        }

        $this->jm->updateJob($job_id,
                             [
                                 'status' => $this->jm::STATUS_READY,
                                 'start_time' => date('Y-m-d H:i:s'),
                             ]
        );
        // $this->runJob($job_id, (!$restart_all ? 'continue' : ''));

        $this->data['output'] = '{}';
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        $this->response->setOutput($this->data['output']);
    }

    /* public function run()
     {
         //init controller data
         $this->extensions->hk_InitData($this, __FUNCTION__);

         $this->load->library('json');
         $this->response->addJSONHeader();

         if (H::has_value($this->request->post_or_get('job_id'))) {

             $job = $this->jm->getJobById($this->request->post_or_get('job_id'));
             $job_id = null;
             //check
             if ($job && $job['status'] == $this->jm::STATUS_READY) {
                 $this->jm->updateJob($job['job_id'], [
                     'start_time' => date('Y-m-d H:i:s'),
                 ]);
                 $job_id = $job['job_id'];
             }
             $this->runJob($job_id);
         } else {
             $this->response->setOutput(AJson::encode(['result' => false]));
         }
         $this->data['output'] = '{}';
         //update controller data
         $this->extensions->hk_UpdateData($this, __FUNCTION__);
         $this->response->setOutput($this->data['output']);
     }

     // run job in separate process
     private function runJob($job_id = 0, $run_mode = '')
     {
         $connect = new AConnect(true);
         $url = $this->config->get('config_url').'job.php?mode=html&job_api_key='.$this->config->get('job_api_key');
         if ($job_id) {
             $url .= '&job_id='.$job_id;
         }
         if ($run_mode) {
             $url .= '&run_mode='.$run_mode;
         }
         $connect->getDataHeaders($url);
         session_write_close();
     }*/

}