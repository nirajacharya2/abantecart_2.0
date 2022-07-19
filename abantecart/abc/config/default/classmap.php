<?php
/**
 * Class Map of default stage
 */

use abc\core\engine\Attribute;
use abc\core\lib\AbcCache;
use abc\core\lib\AttributeManager;
use abc\core\lib\Abac;
use abc\core\lib\ACart;
use abc\core\lib\ACustomer;
use abc\core\lib\AdminCommands;
use abc\core\lib\AUser;
use abc\core\lib\AEncryption;
use abc\core\lib\exceptions\AExceptionHandler;
use abc\core\lib\JobManager;
use abc\core\lib\AJson;
use abc\core\lib\ALog as ALog;
use abc\core\lib\ABackup as ABackup;
use abc\core\lib\AOrder;
use abc\core\lib\APromotion;
use abc\core\lib\AResourceManager;
use abc\core\lib\CheckOut;
use abc\core\lib\CheckOutAdmin;
use abc\core\lib\ACurrency;
use abc\core\lib\UserResolver;
use abc\core\view\AViewDefaultRender;
use abc\modules\audit_log\AuditLogDbStorage;
use abc\modules\injections\models\ModelSearch;
use abc\modules\workers\FixCategoriesCounters;
use Illuminate\Events\Dispatcher as EventDispatcher;
use PhpAbac\AbacFactory;

return [
    'cache'             => AbcCache::class,
    'AViewRender'       => AViewDefaultRender::class,
    'ALog'              => [
        ALog::class,
        [
            'app'      => 'application.log',
            'security' => 'security.log',
            'warn'     => 'application.log',
            'debug'    => 'debug.log',
        ],
    ],
    'ABAC'              => Abac::class,
    'ABACFactory'       => AbacFactory::class,
    'Checkout'          => CheckOut::class,
    'CheckoutAdmin'     => CheckOutAdmin::class,
    'AResourceManager'  => AResourceManager::class,
    'ABackup'           => ABackup::class,
    'JobManager'        => JobManager::class,
    'AJson'             => AJson::class,
    'ACustomer'         => ACustomer::class,
    'Attribute'         => Attribute::class,
    'AttributeManager'  => AttributeManager::class,
    'APromotion'        => APromotion::class,
    'ACart'             => ACart::class,
    'AOrder'            => AOrder::class,
    'EventDispatcher'   => EventDispatcher::class,
    'AEncryption'       => AEncryption::class,
    'ACurrency'         => ACurrency::class,
    'AUser'             => AUser::class,
    'UserResolver'      => UserResolver::class,
    'AdminCommands'     => AdminCommands::class,
    'AExceptionHandler' => AExceptionHandler::class,
    'AuditLogStorage'   => AuditLogDbStorage::class,
    'ModelSearch'       => ModelSearch::class,

    'FixCategoriesCounters' => FixCategoriesCounters::class,
];
