<?php

namespace abc\models\system;

use abc\core\engine\Registry;
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
        'forceDeleted'           => 16,
    ];

    static $auditModels = null;
    static $auditUsers = null;
    static $currentEvents = null;

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

    /**
     * @param string $modelName
     *
     * @return mixed
     */
    public static function getModelIdByName(string $modelName)
    {
        if (static::$auditModels === null) {
            static::$auditModels = Registry::db()
                                           ->table('audit_models')
                                           ->pluck('id', 'name')
                                           ->toArray();
        }

        if (!isset(static::$auditModels[$modelName])) {
            $id = Registry::db()
                          ->table('audit_models')
                          ->insertGetId(
                              [
                                  'name' => $modelName,
                              ]
                          );
            static::$auditModels[$modelName] = $id;
        }

        return static::$auditModels[$modelName];
    }

    /**
     * @param string $user_type_id
     * @param int $user_id
     * @param string $name
     *
     * @return mixed
     */
    public static function getUserId(string $user_type_id, int $user_id, string $name)
    {
        $userData = compact('user_type_id', 'user_id', 'name');
        $uKey = implode("_", $userData);

        if (static::$auditUsers === null) {
            $users = Registry::db()->table('audit_users')->get();
            foreach ($users as $u) {
                static::$auditUsers[$u->user_type_id."_".(int)$u->user_id."_".$u->name] = (int)$u->id;
            }
        }

        if (!static::$auditUsers[$uKey]) {
            $id = Registry::db()->table('audit_users')->insertGetId($userData);
            static::$auditUsers[$uKey] = $id;
        }
        return static::$auditUsers[$uKey];
    }

    public static function getEventIdByParams(array $eventInfo)
    {
        $key = implode("_", $eventInfo);
        if (!isset(static::$currentEvents[$key])) {
            $auditEvent = Registry::db()
                                  ->table('audit_events')
                                  ->where($eventInfo)
                                  ->first();
            if ($auditEvent) {
                static::$currentEvents[$key] = $auditEvent->id;
            } else {
                static::$currentEvents[$key] = Registry::db()
                                                       ->table('audit_events')
                                                       ->insertGetId($eventInfo);
            }
        }
        return static::$currentEvents[$key];
    }
}
