<?php

use abc\models\catalog\Product;
use abc\models\customer\Address;
use abc\models\customer\Customer;
use abc\modules\listeners\ModelAuditListener;
use abc\tests\unit\modules\listeners\ATestListener;

return [
    /** events for ORM Models
     can be
     eloquent.retrieved
     eloquent.creating
     eloquent.created
     eloquent.updating
     eloquent.updated
     eloquent.saving
     eloquent.saved
     eloquent.deleting
     eloquent.deleted
     eloquent.restoring
     eloquent.restored
     * @see more info https://laravel.com/docs/5.6/eloquent#events */

    'EVENTS' => [
        //call some listeners on model event
        'eloquent.saved: abc\models\catalog\Product' => [ ATestListener::class ],
        //call listeners on every model event
        'eloquent.*: *' => [
            //this listener firing by base model property $auditEvents
            ModelAuditListener::class
        ],
    ],

    //allow to enable/disable soft-deleting for models. Default value "false"
    //see eloquent documentation for details
    'FORCE_DELETING' => [
        /*Product::class => true*/
        Customer::class => true,
        Address::class => true,
    ]
];