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
use abc\models\customer\Customer;
use abc\models\locale\Currency;
use abc\models\order\Order;
use H;

class ControllerPagesIndexHome extends AController
{
    public $data = [];

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('common/header');
        $this->loadLanguage('common/home');

        $this->document->setTitle($this->language->get('heading_title', 'common/home'));
        $this->document->resetBreadcrumbs();
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
            'current'   => true,
        ]);

        $this->view->assign('token', $this->session->data['token']);

        $total_sale = Order::where('order_status_id', '>', 0)->sum('total');

        $this->view->assign(
            'total_sale',
            $this->currency->format(
                $total_sale,
                $this->config->get('config_currency')
            )
        );

        $total_sale_year = Order::whereRaw("YEAR(date_added) = ".(int)date('Y'))
                                ->where('order_status_id', '>', 0)
                                ->sum('total');
        $this->view->assign(
            'total_sale_year',
            $this->currency->format(
                $total_sale_year,
                $this->config->get('config_currency')
            )
        );
        $this->view->assign('total_order', Order::search(['mode' => 'total_only']));

        $this->view->assign('total_customer', Customer::getTotalCustomers());
        $this->view->assign('total_customer_approval', Customer::getTotalCustomers(['filter' => ['approved' => 0]]));

        $this->loadModel('catalog/product');
        $this->view->assign('total_product', $this->model_catalog_product->getTotalProducts());
        $this->loadModel('catalog/review');
        $this->view->assign('total_review', $this->model_catalog_review->getTotalReviews());
        $this->view->assign('total_review_approval', $this->model_catalog_review->getTotalReviewsAwaitingApproval());
        $this->view->assign('shortcut_heading', $this->language->get('text_dashboard'));
        $this->view->assign('shortcut', [
            [
                'href' => $this->html->getSecureURL('catalog/category'),
                'text' => $this->language->get('text_category'),
                'icon' => 'categories_icon.png',
            ],
            [
                'href' => $this->html->getSecureURL('catalog/product'),
                'text' => $this->language->get('text_product'),
                'icon' => 'products_icon.png',
            ],
            [
                'href' => $this->html->getSecureURL('catalog/manufacturer'),
                'text' => $this->language->get('text_manufacturer'),
                'icon' => 'brands_icon.png',
            ],
            [
                'href' => $this->html->getSecureURL('catalog/review'),
                'text' => $this->language->get('text_review'),
                'icon' => 'icon_manage3.png',
            ],
            [
                'href' => $this->html->getSecureURL('sale/customer'),
                'text' => $this->language->get('text_customer'),
                'icon' => 'customers_icon.png',
            ],
            [
                'href' => $this->html->getSecureURL('sale/order'),
                'text' => $this->language->get('text_order_short'),
                'icon' => 'orders_icon.png',
            ],
            [
                'href' => $this->html->getSecureURL('extension/extensions/extensions'),
                'text' => $this->language->get('text_extensions_short'),
                'icon' => 'extensions_icon.png',
            ],
            [
                'href' => $this->html->getSecureURL('localisation/language'),
                'text' => $this->language->get('text_language'),
                'icon' => 'languages_icon.png',
            ],
            [
                'href' => $this->html->getSecureURL('design/content'),
                'text' => $this->language->get('text_content'),
                'icon' => 'content_manager_icon.png',
            ],
            [
                'href' => $this->html->getSecureURL('setting/setting'),
                'text' => $this->language->get('text_setting'),
                'icon' => 'settings_icon.png',
            ],
            [
                'href' => $this->html->getSecureURL('tool/message_manager'),
                'text' => $this->language->get('text_messages'),
                'icon' => 'icon_messages.png',
            ],
            [
                'href' => $this->html->getSecureURL('design/layout'),
                'text' => $this->language->get('text_layout'),
                'icon' => 'icon_layouts.png',
            ],
        ]);

        //10 new orders and customers
        $filter = [
            'sort'  => 'date_added',
            'order' => 'DESC',
            'start' => 0,
            'limit' => 10,
        ];
        $top_customers = Customer::search($filter)->toArray();
        foreach ($top_customers as $index => $customer) {
            $action = [];
            $action[] = [
                'text' => $this->language->get('text_edit'),
                'href' => $this->html->getSecureURL(
                    'sale/customer/update',
                    '&customer_id='.$customer['customer_id']
                ),
            ];
            $top_customers[$index]['action'] = $action;
        }
        $this->view->assign('customers', $top_customers);
        $this->view->assign('customers_url', $this->html->getSecureURL('sale/customer'));

        $orders = [];
        $filter = [
            'sort'  => 'date_added',
            'order' => 'DESC',
            'start' => 0,
            'limit' => 10,
        ];
        $this->view->assign('orders_url', $this->html->getSecureURL('sale/order'));
        $this->view->assign('orders_text', $this->language->get('text_order'));

        $results = Order::search($filter)->toArray();
        foreach ($results as $result) {
            $action = [];
            $action[] = [
                'text' => $this->language->get('text_edit'),
                'href' => $this->html->getSecureURL('sale/order/details', '&order_id='.$result['order_id']),
            ];

            $orders[] = [
                'order_id'   => $result['order_id'],
                'name'       => $result['name'],
                'status'     => $result['status'],
                'date_added' => H::dateISO2Display($result['date_added'], $this->language->get('date_format_short')),
                'total'      => $this->currency->format($result['total'], $result['currency'], $result['value']),
                'action'     => $action,
            ];
        }
        $this->view->assign('orders', $orders);

        if ($this->config->get('config_currency_auto')) {
            $currencyInstance = new Currency();
            $currencyInstance->updateCurrencies();
        }

        $this->view->assign('chart_url', $this->html->getSecureURL('index/chart'));

        //check at least one enabled payment extension
        $no_payment_installed = true;
        $ext_list = $this->extensions->getInstalled('payment');
        foreach ($ext_list as $ext_txt_id) {
            if ($this->config->get($ext_txt_id.'_status')) {
                $no_payment_installed = false;
                break;
            }
        }

        if ($no_payment_installed) {
            $this->view->assign('no_payment_installed', $no_payment_installed);
            $this->loadLanguage('common/tips');
            $tip_content = $this->html->convertLinks($this->language->get('no_enabled_payments_tip'));
            $this->view->assign('tip_content', $tip_content);
        }

        //check quick start guide based on no last_login and if it is not yet completed
        if (!$this->user->getLastLogin()
            && $this->session->data['quick_start_step'] != 'finished'
            //show it for first administrator only
            && $this->user->getId() < 2
        ) {
            $store_id = !isset($this->session->data['current_store_id']) ? 0 : $this->session->data['current_store_id'];
            $resources_scripts = $this->dispatch(
                'responses/common/resource_library/get_resources_scripts',
                [
                    'object_name' => 'store',
                    'object_id'   => (int)$store_id,
                    'types'       => ['image'],
                    'onload'      => true,
                    'mode'        => 'single',
                ]
            );
            $this->view->assign('resources_scripts', $resources_scripts->dispatchGetOutput());
            $this->view->assign('quick_start_url', $this->html->getSecureURL('setting/setting_quick_form/quick_start'));
        }

        $this->processTemplate('pages/index/home.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

}