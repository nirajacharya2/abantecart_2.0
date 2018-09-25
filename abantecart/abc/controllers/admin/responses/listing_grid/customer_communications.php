<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 19.09.2018
 * Time: 16:59
 */
namespace abc\controllers\admin;
use abc\core\engine\AController;
use abc\core\helper\AHelperUtils;
use abc\core\lib\AFilter;
use abc\core\lib\AJson;
use abc\models\base\CustomerCommunication;
use stdClass;
use H;

if (!class_exists('abc\core\ABC') || !\abc\core\ABC::env('IS_ADMIN')) {
    header('Location: static_pages/?forbidden='.basename(__FILE__));
}

class ControllerResponsesListingGridCustomerCommunications extends AController
{
    /**
     * @var array
     */
    public $data = array();

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this,__FUNCTION__);

        $customer_id = $this->request->get['customer_id'];

        $communications = CustomerCommunication::getCustomerCommunications($customer_id);


        //Prepare filter config
        $filter_params =  array_merge(array('date_start', 'date_end'), (array)$this->data['grid_filter_params']);
        $grid_filter_params = array( 'name', 'model' );

       /* if(!$this->request->get['date_start']){
            $this->request->get['date_start'] = AHelperUtils::dateInt2Display(strtotime('-30 day'));
        }
        if(!$this->request->get['date_end']){
            $this->request->get['date_end'] = AHelperUtils::dateInt2Display(time());
        } */

        $filter_form = new AFilter(array( 'method' => 'get', 'filter_params' => $filter_params ));
        $filter_grid = new AFilter(array( 'method' => 'post', 'grid_filter_params' => $grid_filter_params ) );
        $data = array_merge($filter_form->getFilterData(), $filter_grid->getFilterData());

        $total = count($communications);

        $response = new stdClass();
        $response->userdata = new stdClass();
        $response->userdata->classes = array();
        $response->page = $filter_grid->getParam('page');
        $response->total = $filter_grid->calcTotalPages($total);
        $response->records = $total;

        $communications = CustomerCommunication::getCustomerCommunications($customer_id, $data);

        $i = 0;
        foreach ($communications as $communication) {
            $user_first_name = $communication->user->firstname;
            $user_last_mame = $communication->user->lastname;
            $username = $communication->user->username;
            $response->rows[$i]['id'] = $communication->communication_id;
            $response->rows[$i]['cell'] = [
                $communication->subject,
                $communication->type,
                H::dateISO2Display($communication->date_added,
                    $this->language->get('date_format_short').' '.$this->language->get('time_format')),
                ($user_first_name || $user_last_mame) ?
                    $user_first_name.' '.$user_last_mame.' ('.$username.')' : $username,
            ];
            $i++;
        }

     $this->data['response'] = $response;

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data['response']));
    }

    /**
     * @return mixed
     * @throws \abc\core\lib\AException
     */
    public function communication_info()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('sale/customer');

        $id = $this->request->get['id'];
        if ($id) {
            $communication = CustomerCommunication::getCustomerCommunicationById($id);

            if ($communication) {

                $this->data['message'] = $communication;
                $this->data['message']['date_added'] =
                    H::dateISO2Display($this->data['message']['date_added'],
                        $this->language->get('date_format_short').' '.$this->language->get('time_format'));
                $this->data['message']['subject_title'] = $this->language->get('communication_subject_title');
                $this->data['message']['body_title'] = $this->language->get('communication_body_title');
                $this->data['message']['date_title'] = $this->language->get('communication_date_title');
                $this->data['message']['title'] = $this->language->get('communication_title');
            } else {
                $this->data['message']["message"] = $this->language->get('text_not_found');
            }
        }

        $this->view->assign('readonly', $this->request->get['readonly']);
        $this->view->batchAssign($this->data);
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        $this->processTemplate('responses/sale/communication_info.tpl');
    }
}
