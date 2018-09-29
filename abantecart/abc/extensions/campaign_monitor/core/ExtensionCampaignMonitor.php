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
        $customer_id = $that->request->get['id'];
        $newCustomerData = $that->request->post;
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

}