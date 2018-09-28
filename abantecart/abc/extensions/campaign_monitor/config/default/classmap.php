<?php
/**
 * Class Map of default stage
 */

use abc\extensions\campaign_monitor\core\lib\CampaignMonitor;
use abc\extensions\campaign_monitor\core\lib\vendor\createsendphp\CS_REST_Subscribers;
use abc\extensions\campaign_monitor\core\lib\vendor\createsendphp\base\CS_REST_Wrapper_Base;
use abc\extensions\campaign_monitor\core\lib\vendor\createsendphp\CS_REST_Transactional_ClassicEmail;

return [
    'campaignmonitor'        => CampaignMonitor::class,
    'CS_REST_Subscribers'   => CS_REST_Subscribers::class,
    'CS_REST_Wrapper_Base' => CS_REST_Wrapper_Base::class,
    'CS_REST_Transactional_ClassicEmail' => CS_REST_Transactional_ClassicEmail::class,
];
