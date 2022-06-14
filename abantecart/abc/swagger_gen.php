<?php
require_once("swagger_autoload.php");
require("vendor/autoload.php");

$excluse = [
    'common',
    'pages',
    'responses',
    'task',
    'block'
];

$pattern = 'api/*.php';

$dirs = [
    __DIR__.'/controllers/admin/api',
    __DIR__.'/controllers/storefront/api',
    __DIR__.'/core/engine/controller_api.php',
];

$openapi = \OpenApi\Generator::scan($dirs, ['exclude' => $excluse, 'pattern' => $pattern]);

file_put_contents('../public/api/openapi.yaml', $openapi->toYaml());
