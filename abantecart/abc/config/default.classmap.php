<?php
/**
 * Class Map of default stage
 */

use abc\core\lib\ACustomer;
use abc\core\lib\AJobManager;
use abc\core\lib\AJson;
use abc\core\lib\ALog as ALog;
use abc\core\lib\ABackup as ABackup;

return [
    'ALog'                 => [
        ALog::class,
        //all errors
        'application.log',
    ],
    'ABackup'              => ABackup::class,
    'AJobManager'          => AJobManager::class,
    'AJson'                => AJson::class,
    'ACustomer'            => ACustomer::class,
];
