<?php

use abc\modules\listeners\ModelAuditListener;

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
        //listeners for model Product on "saving" event
        'eloquent.saving: abc\models\base\Product' => [ ],
        //listeners for all models on "saving" event
        'eloquent.saving: *' => [ ],
        //call listeners on every model event
        'eloquent.*: *' => [
            //this listener firing by base model property $auditEvents
            ModelAuditListener::class
        ]
    ]
];