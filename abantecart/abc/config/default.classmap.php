<?php
/**
 * Class Map of default stage
 */
use abc\core\lib\ALog as ALog;
use abc\core\backend\jobs\APackageInstallerJob;
return [
        'ALog' => [
                    ALog::class,
                    //all errors
                    'application.log',
                    //security alerts
                    'application.log',
                    //warnings
                    'application.log',
                    //debug info
                    'application.log'
        ],
        'APackageInstallerJob' => APackageInstallerJob::class
];