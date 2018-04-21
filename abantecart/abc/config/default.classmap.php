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
                    'application.log'
        ],
        'APackageInstallerJob' => APackageInstallerJob::class
];