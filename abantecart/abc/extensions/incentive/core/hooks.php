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

namespace abc\core\extension;

use abc\core\engine\Extension;

class ExtensionIncentive extends Extension
{
    public function onControllerResponsesListingGridExtension_UpdateData()
    {
        $that = $this->baseObject;
        if ($this->baseObject_method == 'update') {
            $incentive_total_status = 0;
            if (isset($that->request->post['incentive']['incentive_status'])) {
                if ($that->request->post['incentive']['incentive_status'] == 1) {
                    $incentive_total_status = 1;
                }
                if ($that->request->post['incentive']['incentive_status'] == 0) {
                    $incentive_total_status = 0;
                }
                $that->loadModel('setting/setting');
                $activateTotalArray = [
                    'incentive_total' => [
                        'incentive_total_status' => $incentive_total_status,
                    ]
                ];
                foreach ($activateTotalArray as $group => $values) {
                    $that->model_setting_setting->editSetting($group, $values);
                }
            }
        }
    }

    public function onControllerPagesExtensionExtensions_InitData()
    {

        $that =& $this->baseObject;
        if ($this->baseObject_method == 'edit') {
            $incentive_total_status = $that->config->get('incentive_status') ? 1 : 0;
            $that->loadModel('setting/setting');
            $activateTotalArray = [
                'incentive_total' => [
                    'incentive_total_status' => $incentive_total_status,
                ],
            ];
            foreach ($activateTotalArray as $group => $values) {
                $that->model_setting_setting->editSetting($group, $values);
            }
        }
    }

    public function onControllerCommonListingGrid_InitData()
    {
        $that = $this->baseObject;
        $that->loadLanguage('incentive/incentive');
        if ($this->baseObject_method != 'main' || $that->data['table_id'] != 'customer_grid') {
            return null;
        }

        $that->data['actions']['edit']['children']['incentives'] = [
            'text' => $that->language->t('incentive_name_applied', 'Applied Promotions'),
            'href' => $that->html->getSecureURL('sale/incentive_applied', '&customer_id=%ID%'),
        ];

    }
}