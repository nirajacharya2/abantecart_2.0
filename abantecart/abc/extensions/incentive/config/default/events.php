<?php
/**
 * key - event alias (class-name)
 * values - listeners
 */

use abc\extensions\incentive\modules\listeners\StorefrontOrderConfirmBonusApply;

return [

    'abc\checkout\order@confirm' => [
        StorefrontOrderConfirmBonusApply::class,
    ],

];