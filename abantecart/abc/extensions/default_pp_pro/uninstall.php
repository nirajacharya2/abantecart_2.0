<?php

namespace abc\controllers\admin;

use abc\core\lib\AResourceManager;

if ( ! class_exists( 'abc\core\ABC' ) ) {
    header( 'Location: static_pages/?forbidden='.basename( __FILE__ ) );
}

$rm = new AResourceManager();
$rm->setType( 'image' );

$resources = $rm->getResources( 'extensions', 'default_pp_pro' );
if ( is_array( $resources ) ) {
    foreach ( $resources as $resource ) {
        $rm->deleteResource( $resource['resource_id'] );
    }
}
