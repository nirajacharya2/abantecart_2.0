<?php
require_once("swagger_autoload.php");
require("vendor/autoload.php");

$excluse = [
    'common',
    'pages',
    'responses',
    'task',
    'block',
    'modules',
    'vendor'
];

$pattern = 'api/*.php';

$appBaseDir = dirname(getcwd()).DS.'abc';

$dirs = [
    $appBaseDir.'/core/lib/ApiSuccessResponse.php',
    $appBaseDir.'/core/lib/ApiErrorResponse.php',
    $appBaseDir.'/docs/api',
    $appBaseDir.'/controllers/admin/api',
    $appBaseDir.'/controllers/storefront/api',
    $appBaseDir.'/extensions/',
    $appBaseDir.'/core/engine/controller_api.php',
];

$openapi = \OpenApi\Generator::scan($dirs, ['exclude' => $excluse, 'pattern' => $pattern]);

file_put_contents($appBaseDir.'/../public/api/openapi.yaml', $openapi->toYaml());
