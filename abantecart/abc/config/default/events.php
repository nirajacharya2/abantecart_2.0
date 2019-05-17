<?php
/**
 * EventListeners that wll be called from controllers and libs
 * key - event alias (class-name)
 * values - listeners
 */

use abc\modules\listeners\AdminSendApprovalEmailListener;

return [

    'abc\core\lib\customer@login'           => [],
    'abc\core\lib\customer@logout'          => [],
    'abc\core\lib\customer@transaction'     => [],
    'admin\sendApprovalEmail'               => [
            AdminSendApprovalEmailListener::class,
        ],
];

