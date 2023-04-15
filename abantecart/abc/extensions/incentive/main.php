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
require_once __DIR__ . DS . 'core' . DS . 'hooks.php';

$controllers = [
    'storefront' => [
        'api/account/my_incentives',
        'api/incentive/incentive',
        'pages/account/my_incentives',
        'responses/account/incentive',
    ],
    'admin'      => [
        'pages/sale/incentive',
        'pages/total/incentive_total',
        'responses/listing_grid/incentive',
        'responses/extension/incentive_condition_fields',
        'responses/extension/incentive_bonus_fields',
        'pages/sale/incentive_applied',
        'responses/listing_grid/incentive_applied'
    ],
];

$models = [
    'storefront' => ['total/incentive_total'],
    'admin'      => [],
];

$templates = [
    'storefront' => [
        'pages/account/my_incentives.tpl'
    ],
    'admin'      => [
        'pages/sale/incentive.tpl',
        'pages/sale/incentive_form.tpl',
        'pages/sale/incentive_form_bonuses.tpl',
        'pages/sale/incentive_form_conditions.tpl',
        'responses/conditions/default.tpl',
        'responses/bonuses/default.tpl',
        'responses/incentive/applied_details.tpl'
    ],
];

$languages = [
    'storefront' => [],
    'admin'      => [
        'incentive/incentive',
    ],
];

