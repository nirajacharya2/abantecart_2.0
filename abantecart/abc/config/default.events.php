<?php
/**
 * key - event alias (class-name)
 * values - listeners
 */
return [

    'abc\models\admin\product\update'       => [],
    'abc\models\admin\customer@create'      => [],
    'abc\models\admin\customer@update'      => [],
    'abc\models\admin\customer@delete'      => [],
    'abc\models\admin\customer@addAddress'  => [],
    'abc\models\storefront\order@create'    => [],
    'abc\models\storefront\order@confirm'   => [],
    'abc\models\storefront\order@update'    => [],
    'abc\models\storefront\order@remove'    => [],
    'abc\models\storefront\customer@create' => [],
    'abc\models\storefront\customer@update' => [],
    'abc\core\lib\customer@login'           => [],
    'abc\core\lib\customer@logout'          => [],
    'abc\core\lib\customer@transaction'     => []
];

