<?php
/**
 * EventListeners that wll be called from controllers and libs
 * key - event alias (class-name)
 * values - listeners
 */

use abc\modules\listeners\AdminSendApprovalEmailListener;
use abc\modules\listeners\AdminSendNewTransactionNotifyEmailListener;
use abc\modules\listeners\StorefrontSendActivateLinkEmailListener;
use abc\modules\listeners\StorefrontSendWelcomeEmailListener;

return [

    'abc\core\lib\customer@login'       => [],
    'abc\core\lib\customer@logout'      => [],
    'abc\core\lib\customer@transaction' => [],

    'admin\sendApprovalEmail'                     => [
        AdminSendApprovalEmailListener::class,
    ],
    'admin\sendNewCustomerTransactionNotifyEmail' => [
        AdminSendNewTransactionNotifyEmailListener::class,
    ],

    'storefront\sendWelcomeEmail'        => [
        StorefrontSendWelcomeEmailListener::class,
    ],
    'storefront\sendActivationLinkEmail' => [
        StorefrontSendActivateLinkEmailListener::class,
    ],

];

