<?php
if ( ! class_exists( 'abc\core\ABC' ) ) {
    header( 'Location: static_pages/?forbidden='.basename( __FILE__ ) );
}

require_once( 'core/default_pp_pro.php' );

$controllers = array(
    'storefront' => array( 'responses/extension/default_pp_pro' ),
    'admin'      => array( 'responses/extension/default_pp_pro' ),
);

$models = array(
    'storefront' => array( 'extension/default_pp_pro' ),
    'admin'      => array( 'extension/default_pp_pro' ),
);

$languages = array(
    'storefront' => array(
        'default_pp_pro/default_pp_pro',
    ),
    'admin'      => array(
        'default_pp_pro/default_pp_pro',
    ),
);

$templates = array(
    'storefront' => array(
        'responses/default_pp_pro.tpl',
    ),
    'admin'      => array(
        'pages/extension/default_pp_pro_settings.tpl',
        'pages/sale/pp_pro_payment_details.tpl',
    ),
);
