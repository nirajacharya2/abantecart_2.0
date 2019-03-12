<?php

use abc\models\catalog\Category;
use abc\models\catalog\Product;
use abc\models\catalog\ProductDescription;
use abc\models\customer\Customer;
use abc\models\locale\Currency;
use abc\models\user\User;
use abc\modules\listeners\ModelAuditListener;

return [
    /** events for ORM Models
     * can be
     * eloquent.retrieved
     * eloquent.creating
     * eloquent.created
     * eloquent.updating
     * eloquent.updated
     * eloquent.saving
     * eloquent.saved
     * eloquent.deleting
     * eloquent.deleted
     * eloquent.restoring
     * eloquent.restored
     *
     * @see more info https://laravel.com/docs/5.6/eloquent#events
     */

    'EVENTS'         => [
        //listeners for model Product on "saving" event
        //'eloquent.saving: abc\models\catalog\Product' => [ ],
        //listeners for all models on "saving" event
        'eloquent.saving: *' => [],
        //call listeners on every model event
        'eloquent.*: *'      => [
            //this listener firing by base model property $auditEvents
            ModelAuditListener::class,
        ],
    ],
    'MORPH_MAP'      => [
        'Currency'           => Currency::class,
        'Customer'           => Customer::class,
        'Product'            => Product::class,
        'User'               => User::class,
        'ProductDescription' => ProductDescription::class,
        'Category'           => Category::class,
    ],
    //allow to enable/disable soft-deleting for models. Default value "false"
    //see eloquent documentation for details
    'FORCE_DELETING' => [
        //Product::class => false
    ],
];
