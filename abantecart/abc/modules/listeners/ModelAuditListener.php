<?php

namespace abc\modules\listeners;

use abc\core\engine\Registry;
use abc\core\lib\UserResolver;
use abc\models\BaseModel;
use abc\models\catalog\Product;
use abc\models\system\AuditEvent;
use abc\models\system\AuditModel;
use abc\models\system\AuditUser;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;

class ModelAuditListener
{

    protected $registry;

    const DEBUG_TO_LOG = false;

    public function __construct()
    {
        $this->registry = Registry::getInstance();
    }

    /**
     * @param string $eventAlias
     * @param        $params
     *
     * @return array | false
     * @throws \Exception
     */
    public function handle($eventAlias, $params)
    {

        $relationName = '';
        $ids = [];
        $morphAttributes = [];
        if (is_int(strpos($eventAlias, 'belongsToManyAttaching'))
            || is_int(strpos($eventAlias, 'belongsToManyDetaching'))) {
            $relationName = $params[0];
            $ids = $params[2];
        }

        if (is_int(strpos($eventAlias, 'morphToManyAttaching'))
            || is_int(strpos($eventAlias, 'morphToManyDetaching'))) {
            $relationName = $params[0];
            $ids = $params[2];
            $morphAttributes = $params[3];
        }

        if (self::DEBUG_TO_LOG === true) {
            $this->registry->get('log')->write('Start Handle Event: '.$eventAlias);
        }

        /**
         * @var BaseModel | Product $modelObject
         */
        if (is_object($params[1])) {

            $eventsList = [
                'creating',
                'created',
                'updating',
                'updated',
                'deleting',
                'deleted',
                'saving',
                'saved',
                'restoring',
                'restored',
                'forceDeleted',
                'belongsToManyDetached',
                'belongsToManyDetaching',
                'belongsToManyAttached',
                'belongsToManyAttaching',
            ];

            foreach ($eventsList as $alias) {
                if (is_int(stripos($eventAlias, $alias))) {
                    $eventAlias = 'eloquent.'.$alias;
                    break;
                }
            }

            if ($params[1] instanceof \Illuminate\Database\Eloquent\Collection) {
                /**
                 * @var \Illuminate\Database\Eloquent\Collection $collection
                 */
                $collection = $params[1];
                $messages = '';
                foreach ($collection as $modelObject) {
                    $result = $this->handleModel($eventAlias, $modelObject);
                    if ($result === false) {
                        return false;
                    }
                    $messages .= $result['message']."\n";
                }

                //when all models handled return result
                return [
                    'result'  => true,
                    'message' => $messages,
                ];
            } else {
                return $this->handleModel($eventAlias, $params[1], $relationName, $ids, $morphAttributes);
            }
        } else {
            return $this->handleModel($eventAlias, $params[0]);
        }

    }

    protected function handleModel($eventAlias, $modelObject, $relationName = '', $ids = [], $morphAttributes = [])
    {

        if (!is_object($modelObject)
            || !($modelObject instanceof BaseModel)
        ) {
            return $this->output(
                false,
                'ModelAuditListener: Argument 1 not instance of base model '.BaseModel::class
            );
        }

        $modelClassName = $modelObject->getClass();

        //skip if auditing disabled for orm-model
        if (!$modelObject::$auditingEnabled) {
            return $this->output(
                true,
                'ModelAuditListener: Auditing of model '.$modelClassName.' is disabled.'
            );
        }
        // check is event allowed by model-class
        $event_name = '';
        $allowedEvents = (array)$modelObject::$auditEvents;
        foreach ($allowedEvents as $ev) {
            if (is_int(strpos($eventAlias, 'eloquent.'.$ev))) {
                $event_name = $ev;
                break;
            }
        }

        //skip empty or "retrieved" event as useless
        if (!$event_name || $event_name == 'retrieved') {

            return $this->output(
                true,
                'ModelAuditListener: Auditing of model '
                .$modelClassName.' on event "'.$eventAlias.'" not found.'
            );
        }
        //get changed
        $newData = $modelObject->getDirty();
        $oldData = $modelObject->getOriginal();

        if ($modelObject::$auditExcludes) {
            foreach ($modelObject::$auditExcludes as $excludeColumnName) {
                unset($newData[$excludeColumnName]);
                unset($oldData[$excludeColumnName]);
            }
        }

        //if data still presents write log
        if (!$newData && !$oldData) {
            return $this->output(
                true,
                'ModelAuditListener: Nothing to audit of model '.$modelClassName.'.'
            );
        }

        //Skip saving event before inserts to prevent duplication
        if ($event_name == 'saving' && !$oldData) {
            return $this->output(
                false,
                'ModelAuditListener: Skipped "saving" event before inserting for '.$modelClassName.'.'
            );
        }
        //Skip saved event after inserts and updates
        if ($event_name == 'saved') {
            return $this->output(
                false,
                'ModelAuditListener: "saved" event not supported. Use "saving" instead!'
            );
        }
        //Skip updating event after inserts and updates
        if ($event_name == 'updating' && !$newData) {
            return $this->output(
                false,
                'ModelAuditListener: Skipped "updating" event with empty newData for '.$modelClassName.'.'
            );
        }

        //skip creating event if created will be fired.
        // creating event do not save auditable_id into audit log table because id does not exists yet
        if (
            ($event_name == 'creating' && in_array('created', $allowedEvents))
            || //skip saving if creating presents to prevent duplication
            ($event_name == 'saving'
                && !$modelObject->exists
                && in_array('creating', $allowedEvents)
            )
            || //skip saving if updating presents to prevent duplication
            ($event_name == 'saving'
                && $modelObject->exists
                && in_array('updating', $allowedEvents)
            )
        ) {

            return $this->output(
                false,
                'Skipped "'.$event_name.'" of model '.$modelClassName.' to prevent duplication of data');
        }

        $request_id = $this->registry->get('request')->getUniqueId();
        $session_id = session_id();

        $user = new UserResolver($this->registry);
        $user_type = $user->getUserType();
        $user_id = $user->getUserId();
        $user_name = $user->getUserName();
        $auditData = [];

        //get primary key value
        $auditable_id = $modelObject->getKey();
        if (!$auditable_id) {
            $auditable_id = $newData[$modelObject->getKeySet()[0]];
            if (!$auditable_id) {
                $auditable_id = $oldData[$modelObject->getKeySet()[0]];
            }
        }

        if (!$newData && $oldData) {
            $newData = array_fill_keys(array_keys($oldData), null);
        }

        $reflect = new ReflectionClass($modelObject);
        $auditable_model = $reflect->getShortName();

        $reflect = new ReflectionClass($modelObject->getMainModelClassName());
        $main_auditable_model = $reflect->getShortName();
        $main_auditable_id = $newData[$modelObject->getMainModelClassKey()];
        $main_auditable_id = !$main_auditable_id ? $oldData[$modelObject->getMainModelClassKey()] : $main_auditable_id;

        $userData = [
            'user_type_id' => AuditUser::USER_TYPES[$user_type],
            'user_id'      => $user_id,
            'name'         => $user_name,
        ];
        $db = $this->registry->get('db');

        $auditModel = $db->table('audit_models')
            ->where('name', '=', $auditable_model)
            ->first();
        if ($auditModel) {
            $auditableModelId = $auditModel->id;
        } else {
            $auditableModelId = $db->table('audit_models')->insertGetId(['name' => $auditable_model]);
        }

        if ($event_name == 'belongsToManyAttaching') {
            $colName = $relationName;
            $event_name = 'creating';
            $newData = [];
            $oldData = [];
            foreach ($ids as $id) {
                $oldData[][$colName] = null;
                $newData[][$colName] = $id;
            }
        }

        if ($event_name == 'belongsToManyDetaching') {
            $colName = $relationName;
            $event_name = 'deleting';
            $newData = [];
            $oldData = [];
            foreach ($ids as $id) {
                $oldData[][$colName] = $id;
                $newData[][$colName] = null;
            }
        }

        $eventDescription = [];

        foreach ($newData as $colName => $newValue) {
            if (is_array($newValue)) {
                foreach ($newValue as $cName => $nValue) {
                    $eventDescription[] = [
                        'auditable_model_id' => $auditableModelId,
                        'auditable_id'       => $auditable_id,
                        'field_name'         => $cName,
                        'old_value'          => $oldData[$colName][$cName],
                        'new_value'          => $nValue,
                    ];
                }
            } else {
                $eventDescription[] = [
                    'auditable_model_id' => $auditableModelId,
                    'auditable_id'       => $auditable_id,
                    'field_name'         => $colName,
                    'old_value'          => $oldData[$colName],
                    'new_value'          => $newValue,
                ];
            }
        }


        $db = $this->registry->get('db');
        $mainModel = $db->table('audit_models')
            ->where('name', '=', $main_auditable_model)
            ->first();
        if ($mainModel) {
            $mainModelId = $mainModel->id;
        } else {
            $mainModelId = $db->table('audit_models')->insertGetId(['name' => $main_auditable_model]);
        }

        $event = [
            'request_id'              => $request_id,
            'event_type_id'           => AuditEvent::EVENT_NAMES[$event_name],
            'main_auditable_model_id' => $mainModelId,
            'main_auditable_id'       => $main_auditable_id,
        ];

        try {
            $this->registry->get('db')->transaction(function () use ($db, $session_id, $userData, $event, $eventDescription) {

                $auditSession = $db->table('audit_sessions')
                    ->where('session_id', '=', $session_id)
                    ->first();
                if ($auditSession) {
                    $auditSessionId = $auditSession->id;
                } else {
                    $auditSessionId = $db->table('audit_sessions')->insertGetId(['session_id' => $session_id]);
                }
                $event['audit_session_id'] = $auditSessionId;

                $auditUser = $db->table('audit_users')
                    ->where('user_type_id', '=', $userData['user_type_id'])
                    ->where('user_id', '=', $userData['user_id'])
                    ->where('name', '=', $userData['name'])
                    ->first();
                if ($auditUser) {
                    $auditUserId = $auditUser->id;
                } else {
                    $auditUserId = $db->table('audit_users')->insertGetId($userData);
                }
                $event['audit_user_id'] = $auditUserId;

                $auditEvent = $db->table('audit_events')
                    ->where('request_id', '=', $event['request_id'])
                    ->where('audit_user_id', '=', $auditUserId)
                    ->where('event_type_id', '=', $event['event_type_id'])
                    ->where('main_auditable_model_id', '=', $event['main_auditable_model_id'])
                    ->where('main_auditable_id', '=', $event['main_auditable_id'])
                    ->first();
                if ($auditEvent) {
                    $eventId = $auditEvent->id;
                } else {
                    $eventId = $db->table('audit_events')->insertGetId($event);
                }

                if ($eventId) {
                    foreach ($eventDescription as &$item) {
                        $item['audit_event_id'] = $eventId;
                    }
                    $db->table('audit_event_descriptions')->insert($eventDescription);
                }

            });
        } catch (\PDOException $e) {
            \H::df($e->getMessage());

            $error_message = __CLASS__.": Auditing of ".$modelClassName." failed.";
            $this->registry->get('log')->write($error_message);
            $this->registry->get('log')->write($e->getMessage());
            $this->registry->get('log')->write($event_name);
            //TODO: need to check
            if ($modelObject::$auditingStrictMode) {
                return false;
                //throw new \Exception($error_message);
            }
            // stop event listeners firing
            return false;
        }

        return $this->output(
            true,
            'ModelAuditListener: Auditing of model '
            .$modelClassName.' on event "'.$eventAlias.'" ('.$auditable_model.':'.$auditable_id.') '
            .'has been finished successfully.'
        );
    }

    protected function output($result, $message)
    {
        $output = [
            'result'  => $result,
            'message' => $message,
        ];

        if (self::DEBUG_TO_LOG === true) {
            $this->registry->get('log')->write(var_export($output, true));
        }
        return $output;
    }
}
