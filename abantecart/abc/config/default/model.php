<?php

use abc\models\catalog\Category;
use abc\models\catalog\Product;
use abc\models\catalog\ProductDescription;
use abc\models\customer\Address;
use abc\models\customer\Customer;
use abc\models\locale\Currency;
use abc\models\order\Order;
use abc\models\user\User;
use abc\modules\listeners\ModelAuditListener;
use abc\modules\listeners\ModelCategoryListener;
use abc\modules\listeners\ModelProductListener;

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
        //listener recalculates product counts of category,
        // count of subcategories and build path
        'eloquent.saved: abc\models\catalog\Category' => [
            ModelCategoryListener::class
        ],
    ],
    'MORPH_MAP'      => [
        'Currency'           => Currency::class,
        'Customer'           => Customer::class,
        'Product'            => Product::class,
        'User'               => User::class,
        'ProductDescription' => ProductDescription::class,
        'Category'           => Category::class,
        'Order'              => Order::class,
    ],
    //allow to enable/disable soft-deleting for models. Default value "false"
    //see eloquent documentation for details
    //you can extends base model with this array
    'INITIALIZE'       => [
            Product::class => [
                //merge with model properties
                'properties' => [
                                    'fillable' => [],
                                    'guarded'  => []
                ],
                //add scopes with array of scope class full names
                'scopes'     => [
                    //SomeFullScopeClassName  myScope::class,
                ]
            ]
    ]
];
