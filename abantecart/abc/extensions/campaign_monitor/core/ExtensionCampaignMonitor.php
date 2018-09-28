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
        $currentCustomer = Customer::find($that->request->get['customer_id'])->toArray();
        if (!$currentCustomer) {
            return;
        }
        $newCustomerData = $that->request->post;
        require_once(__DIR__.DS."lib/campaign_monitor.php");

        CampaignMonitor::changeSubscriber($this->listId, $this->auth, $currentCustomer, $newCustomerData);
    }


}