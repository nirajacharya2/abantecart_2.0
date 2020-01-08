<?php

namespace abc\models\system;

use abc\models\BaseModel;

class AuditEvent extends BaseModel
{
    const EVENT_NAMES = [
        'creating'               => 1,
        'created'                => 2,
        'updating'               => 3,
        'updated'                => 4,
        'deleting'               => 5,
        'deleted'                => 6,
        'belongsToManyAttaching' => 7,
        'belongsToManyDetaching' => 8,
        'morphToManyAttaching'   => 9,
        'morphToManyDetaching'   => 10,
        'saving'                 => 11,
        'saved'                  => 12,
        'restoring'              => 13,
        'restored'               => 14,
        //Note: forceDeleting event not fired from softDelete trait!
        //Name is just reserved!
        //'forceDeleting'              => 15,
        'forceDeleted'               => 16,
    ];

    /**
     * @param int $id
     *
     * @return bool|int|string
     */
    public static function getEventById(int $id)
    {
        foreach (self::EVENT_NAMES as $key=>$value) {
            if ($value === (int)$id) {
                return $key;
            }
        }
        return false;
    }
}
