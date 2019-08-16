<?php
/**
 * EventListeners that wll be called from controllers and libs
 * key - event alias (class-name)
 * values - listeners
 */

use abc\modules\listeners\AdminOrderUpdateProductStatusesChange;
use abc\modules\listeners\AdminOrderUpdateTotalBalance;
use abc\modules\listeners\AdminSendApprovalEmailListener;
use abc\modules\listeners\AdminSendNewTransactionNotifyEmailListener;
use abc\modules\listeners\AdminSendOrderStatusNotifyEmailListener;
use abc\modules\listeners\StorefrontOrderConfirmEmailListener;
use abc\modules\listeners\StorefrontResetPasswordNotifyEmailListener;
use abc\modules\listeners\StorefrontSendActivateLinkEmailListener;
use abc\modules\listeners\StorefrontSendLoginNameEmailListener;
use abc\modules\listeners\StorefrontSendOrderUpdateEmailListener;
use abc\modules\listeners\StorefrontSendResetPasswordLinkListener;
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
    'admin\SendOrderStatusNotifyEmail'            => [
        AdminSendOrderStatusNotifyEmailListener::class,
    ],
    'abc\models\admin\order@update'               => [
        AdminOrderUpdateTotalBalance::class,
        AdminOrderUpdateProductStatusesChange::class,
    ],
    'storefront\sendWelcomeEmail'                 => [
        StorefrontSendWelcomeEmailListener::class,
    ],
    'storefront\sendActivationLinkEmail'          => [
        StorefrontSendActivateLinkEmailListener::class,
    ],
    'storefront\sendPasswordResetLinkEmail'       => [
        StorefrontSendResetPasswordLinkListener::class,
    ],
    'storefront\sendPasswordResetNotifyEmail'     => [
        StorefrontResetPasswordNotifyEmailListener::class,
    ],
    'storefront\sendLoginNameEmail'               => [
        StorefrontSendLoginNameEmailListener::class,
    ],
    'storefront\sendOrderConfirmEmail'           => [
        StorefrontOrderConfirmEmailListener::class,
    ],
    'storefront\sendOrderUpdateEmail'           => [
        StorefrontSendOrderUpdateEmailListener::class,
    ],

];

