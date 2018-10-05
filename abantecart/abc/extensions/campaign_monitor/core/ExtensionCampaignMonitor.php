<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 27/09/2018
 * Time: 15:10
 */

namespace abc\core\extension;

use abc\core\engine\Extension;
use abc\core\engine\Registry;
use abc\core\helper\AHelperUtils;
use abc\models\base\Customer;
use abc\extensions\campaign_monitor\core\lib\CampaignMonitor;

class ExtensionCampaignMonitor extends Extension
{
    protected $registry;
    protected $pp_data;

    public function __construct()
    {
        $this->registry = Registry::getInstance();
        $this->config = $this->registry->get('config');
        $log = $this->registry->get('log');

        $this->auth = ["api_key" => $this->config->get('default_campaign_monitor_apikey')];
        $this->listId = $this->config->get('default_campaign_monitor_site_subscribers_listid');

    }

    public function onControllerPagesSaleCustomer_InitData()
    {
        $that = $this->baseObject;
        if (!$this->baseObject_method == "update" || !$that->request->is_POST()) {
            return;
        }
        $newCustomerData = $that->request->post;
        if ($that->request->get['customer_id']) {
            $currentCustomer = Customer::find($that->request->get['customer_id']);
        }
        if ($currentCustomer) {
            $currentCustomer = $currentCustomer->toArray();
        } else {
            foreach ($newCustomerData as $key => $value) {
                $currentCustomer[$key] = 0;
            }
        }

        if (!$currentCustomer) {
            return;
        }

        CampaignMonitor::changeSubscriber($this->listId, $this->auth, $currentCustomer, $newCustomerData);
    }

    public function onControllerResponsesListingGridCustomer_InitData()
    {
        $that = $this->baseObject;

        if (!$this->baseObject_method == "update_field" || !$that->request->is_POST()) {
            return;
        }
        $customer_id = 0;
        $newCustomerData = [];

        if (isset($that->request->post['status']) && is_array($that->request->post['status'])) {
            $status = $that->request->post['status'];
            foreach ($status as $key => $value) {
                $customer_id = $key;
                $newCustomerData = ['status' => $value];
            }
        } else {
            $customer_id = $that->request->get['id'];
            $newCustomerData = $that->request->post;
        }

        if (!(int)$customer_id) {
            return;
        }
        $currentCustomer = Customer::find($customer_id);
        if (!$currentCustomer) {
            return;
        }
        $currentCustomer = $currentCustomer->toArray();

        $tempArr = $currentCustomer;
        foreach ($newCustomerData as $key => $value) {
            $tempArr[$key] = $value;
        }
        $newCustomerData = $tempArr;
        unset($tempArr);

        CampaignMonitor::changeSubscriber($this->listId, $this->auth, $currentCustomer, $newCustomerData);
    }

    public function onControllerPagesAccountUnsubscribe_InitData()
    {
        $that = $this->baseObject;
        if (!AHelperUtils::has_value($that->request->get['email'])) {
            return;
        }
        $that->loadModel('account/customer');
        $customer = $that->model_account_customer->getCustomerByEmail($that->request->get['email']);
        if ($customer && $customer['email'] == $that->request->get['email']) {
            $this->model_account_customer->editNewsletter(0, (int)$customer['customer_id']);
        }

    }

    public function onControllerPagesAccountCreate_UpdateData()
    { //ЛОвим регистрацию
        $that = $this->baseObject;
        $customer_id = $that->data['customer_id'];
        if (!(int)$customer_id) {
            return;
        }
        $currentCustomer = Customer::find($customer_id);
        if (!$currentCustomer) {
            return;
        }
        $newCustomerData = $currentCustomer->toArray();

        $tempArr = $newCustomerData;
        foreach ($tempArr as $key => $value) {
            $tempArr[$key] = 0;
        }
        $currentCustomer = $tempArr;
        unset($tempArr);

        CampaignMonitor::changeSubscriber($this->listId, $this->auth, $currentCustomer, $newCustomerData);

    }

    public function onControllerPagesAccountSubscriber_UpdateData()
    {
        $that = $this->baseObject;

        if ($that->request->is_POST()) {

            $customer_id = $that->data['customer_id'];
            if (!(int)$customer_id) {
                return;
            }
            $currentCustomer = Customer::find($customer_id);
            if (!$currentCustomer) {
                return;
            }
            $newCustomerData = $currentCustomer->toArray();

            $newCustomerData['status'] = 1;

            $tempArr = $newCustomerData;
            foreach ($tempArr as $key => $value) {
                $tempArr[$key] = 0;
            }
            $currentCustomer = $tempArr;
            unset($tempArr);

            CampaignMonitor::changeSubscriber($this->listId, $this->auth, $currentCustomer, $newCustomerData);

        }
    }

    public function onControllerPagesAccountNotification_InitData()
    {
        $that = $this->baseObject;

        if (!$that->request->is_POST()) {
            return;
        }
        $settings = $that->request->post['settings'];
        $customer_id = (int)$that->customer->getId();

        if (!$customer_id) {
            return;
        }

        $currentCustomer = Customer::find($customer_id);
        if (!$currentCustomer) {
            return;
        }
        $currentCustomer = $currentCustomer->toArray();
        $newCustomerData = $currentCustomer;

        if (is_array($settings) && $settings['newsletter']['email'] == 1) {
            $newCustomerData['newsletter'] = 1;
        } else {
            $newCustomerData['newsletter'] = 0;
        }

        CampaignMonitor::changeSubscriber($this->listId, $this->auth, $currentCustomer, $newCustomerData);
    }

    public function onControllerPagesAccountEdit_InitData() {
        $that = $this->baseObject;

        if (!$that->request->is_POST()) {
            return;
        }
        $request = $that->request->post;
        $customer_id = (int)$that->customer->getId();
        if (!$customer_id) {
            return;
        }
        $currentCustomer = Customer::find($customer_id);
        if (!$currentCustomer) {
            return;
        }
        $currentCustomer = $currentCustomer->toArray();
        $newCustomerData = $currentCustomer;

        if (!empty(trim($request['firstname']))) {
            $newCustomerData['firstname'] = $request['firstname'];
        }
        if (!empty(trim($request['lastname']))) {
            $newCustomerData['lastname'] = $request['lastname'];
        }
        if (!empty(trim($request['email']))) {
            $newCustomerData['email'] = $request['email'];
        }
        if (!empty(trim($request['telephone']))) {
            $newCustomerData['telephone'] = $request['telephone'];
        }
        if (!empty(trim($request['fax']))) {
            $newCustomerData['fax'] = $request['fax'];
        }

        CampaignMonitor::changeSubscriber($this->listId, $this->auth, $currentCustomer, $newCustomerData);
    }

}