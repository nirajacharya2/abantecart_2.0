<?php

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
        //call listeners on model event
        'eloquent.saved: abc\models\base\Product' => [ ],
        //call listeners on every model event
        'eloquent.saved: *' => [  ],
    ]
];