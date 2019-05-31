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

require_once __DIR__.DS.'core'.DS.'gdpr_hooks.php';

$controllers = [
    'storefront' => [
        'responses/extension/gdpr',
    ],
    'admin'      => [
        'pages/extension/gdpr_history',
        'responses/listing_grid/gdpr_history',
        'responses/extension/gdpr',
    ],
];

$models = [
    'storefront' => [
        'extension/gdpr',
    ],
    'admin'      => [
        'extension/gdpr',
    ],
];

$templates = [
    'storefront' => [
        'common/header_bottom.post.tpl',
        'responses/extension/gdpr_buttons.tpl',
        'responses/extension/gdpr_view_data_modal.tpl',
    ],
    'admin'      => [
        'pages/extension/gdpr_history.tpl',
        'responses/extension/gdpr_languages.tpl',
    ],
];

$languages = [
    'storefront' => [
        'english/gdpr/gdpr',
    ],
    'admin'      => [
        'english/gdpr/gdpr',
    ],
];
