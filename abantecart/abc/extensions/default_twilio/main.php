<?php
if (!class_exists('abc\core\ABC')) {
    header('Location: static_pages/?forbidden='.basename(__FILE__));
}
include_once('core/lib/default_twilio.php');
$controllers = [
    'storefront' => [],
    'admin'      => ['responses/extension/default_twilio'],
];

$models = [
    'storefront' => [],
    'admin'      => [],
];

$templates = [
    'storefront' => [],
    'admin'      => ['responses/extension/default_twilio_test.tpl'],
];

$languages = [
    'storefront' => [
        'english/default_twilio/default_twilio',
    ],
    'admin'      => [
        'english/default_twilio/default_twilio',
    ],
];

