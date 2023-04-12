<?php

namespace abc\modules\listeners;

use abc\core\ABC;
use abc\core\engine\Registry;
use abc\core\lib\contracts\AuditLogStorageInterface;
use abc\core\lib\UserResolver;
use abc\models\BaseModel;
use abc\models\catalog\Product;
use abc\models\system\AuditEvent;
use abc\models\user\User;
use Exception;
use H;
use Illuminate\Database\Eloquent\Collection;
use ReflectionClass;
use ReflectionException;

class ModelAuditListener
{
    static $DEBUG_TO_LOG = false;

    /**
     * @param string $eventAlias
     * @param        $params
     *
     * @return array | false
     * @throws Exception
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

        if (static::$DEBUG_TO_LOG === true) {
            Registry::log()->debug('Start Handle Event: ' . $eventAlias);
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
                    $eventAlias = 'eloquent.' . $alias;
                    break;
                }
            }

            if ($params[1] instanceof Collection) {
                $collection = $params[1];
                $messages = '';
                foreach ($collection as $modelObject) {
                    $result = $this->handleModel($eventAlias, $modelObject);
                    if ($result['result'] === false) {
                        return false;
                    }
                    $messages .= $result['message'] . "\n";
                }

                //when all models handled return result
                return [
                    'result' => true,
                    'message' => $messages,
                ];
            } else {
                return $this->handleModel($eventAlias, $params[1], $relationName, $ids, $morphAttributes);
            }
        } else {
            return $this->handleModel($eventAlias, $params[0]);
        }
    }

    /**
     * @param string $eventAlias
     * @param BaseModel $modelObject
     * @param string $relationName
     * @param array $ids
     * @param array $morphAttributes
     *
     * @return array
     * @throws ReflectionException
     */
    protected function handleModel($eventAlias, $modelObject, $relationName = '', $ids = [], $morphAttributes = [])
    {
        if (!is_object($modelObject)
            || !($modelObject instanceof BaseModel)
        ) {
            return $this->output(
                false,
                'ModelAuditListener: Argument 1 not instance of base model ' . BaseModel::class
            );
        }

        $modelClassName = $modelObject->getClass();

        //skip if auditing disabled for orm-model
        if (!$modelObject::$auditingEnabled) {
            return $this->output(
                true,
                'ModelAuditListener: Auditing of model ' . $modelClassName . ' is disabled.'
            );
        }
        // check is event allowed by model-class
        $event_name = '';
        $allowedEvents = $modelObject::$auditEvents;
        foreach ($allowedEvents as $ev) {
            if (is_int(strpos($eventAlias, 'eloquent.' . $ev))) {
                $event_name = $ev;
                break;
            }
        }

        //skip empty or "retrieved" event as useless
        if (!$event_name || $event_name == 'retrieved') {
            return $this->output(
                true,
                'ModelAuditListener: Auditing of model '
                . $modelClassName . ' on event "' . $eventAlias . '" not found.'
            );
        }
        //get changed
        $newData = $modelObject->getDirty();
        $oldData = $modelObject->getOriginal();

        if ($modelObject::$auditExcludes) {
            foreach ($modelObject::$auditExcludes as $excludeColumnName) {
                unset(
                    $newData[$excludeColumnName],
                    $oldData[$excludeColumnName]
                );
            }
        }

        //if data still presents write log
        //exclude touches! (case when only date_modified updated)
        if ((!$newData && !$oldData) || array_keys($newData) == [$modelObject->getUpdatedAtColumn()]) {
            return $this->output(
                true,
                'ModelAuditListener: Nothing to audit of model ' . $modelClassName . '.'
            );
        }

        //Skip saving event before inserts to prevent duplication
        if ($event_name == 'saving' && !$oldData) {
            return $this->output(
                false,
                'ModelAuditListener: Skipped "' . $event_name . '" event before inserting for ' . $modelClassName . '.'
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
        if ($event_name == 'updating' && (!$newData || !$oldData)) {
            return $this->output(
                false,
                'ModelAuditListener: Skipped "updating" event with empty data set for ' . $modelClassName . '.'
            );
        }

        $request_id = Registry::request()?->getUniqueId() ?: H::genRequestId();

        $user = new UserResolver(Registry::getInstance());
        $user_type = $user->getUserType();
        $user_id = $user->getUserId();
        $actorOnBehalf = $user->getActoronbehalf()
            ? User::find($user->getActoronbehalf())
            : null;
        $user_name = $user->getUserName() . ($actorOnBehalf ? '(' . $actorOnBehalf->username . ')' : 'system');

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
            'group' => $user_type,
            'id' => $user_id,
            'name' => $user_name,
        ];

        $auditableModelId = AuditEvent::getModelIdByName($auditable_model);
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

        //skip creating event if created will be fired.
        // creating event do not save auditable_id into audit log table because key (ID) does not exist yet
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
                'Skipped "' . $event_name . '" of model ' . $modelClassName . ' to prevent duplication of data'
            );
        }

        $storageData = [
            'id'      => $request_id,
            'actor'   => $userData,
            'app'     => [
                'name'    => 'Abantecart',
                'server'  => '',
                'version' => '2.0',
                'build'   => ABC::env('BUILD_ID') ?: '',
                'stage'   => ABC::$stage_name,
            ],
            'entity'  => [
                'name'  => $main_auditable_model,
                'id'    => $main_auditable_id,
                'group' => $event_name,
            ],
            'request' => [
                'ip'        => $user->getUserIp(),
                'timestamp' => date('Y-m-d\TH:i:s.v\Z'),
            ],
            'changes' => [],
        ];

        foreach ($newData as $colName => $newValue) {
            $newValue = $this->stringify($newValue);
            $oldValue = $this->stringify($oldData[$colName]);

            if ($oldValue === $newValue) {
                //write to debug if needed
                $this->output(
                    true,
                    'DATA SKIPPED: ' . $colName . " because values are equal."
                );
                continue;
            }
            $storageData['changes'][] = [
                'auditable_model_id' => $auditableModelId,
                'groupId'            => $auditable_id ?: 0,
                'groupName'          => $auditable_model,
                'name'               => $colName,
                'oldValue'           => $oldValue,
                'newValue'           => $newValue,
            ];
        }

        try {
            /** @var AuditLogStorageInterface $auditLogStorage */
            $auditLogStorage = Registry::getInstance()->get('AuditLogStorage');
            if (!$auditLogStorage) {
                $auditLogStorage = ABC::getObjectByAlias('AuditLogStorage');
            }
            if (!$auditLogStorage) {
                return $this->output(
                    false,
                    'Unknown Audit log storage, please check classmap.php'
                );
            }

            if (!($auditLogStorage instanceof AuditLogStorageInterface)) {
                throw new Exception(
                    'Audit log storage not instance of AuditLogStorageInterface, please check classmap.php'
                );
            }
            Registry::getInstance()->set('AuditLogStorage', $auditLogStorage);
            $auditLogStorage->write($storageData);
        } catch (Exception $e) {
            $error_message = __CLASS__ . ": Auditing of " . $modelClassName . " failed.";
            Registry::log()->error(
                $error_message . "\n"
                . $e->getMessage() . "\n"
                . $event_name
            );

            //TODO: need to check
            return $this->output(
                false,
                $error_message
            );
        }

        return $this->output(
            true,
            'ModelAuditListener: Auditing of model '
            . $modelClassName . ' on event "' . $eventAlias . '" (' . $auditable_model . ':' . $auditable_id . ') '
            . 'has been finished successfully.'
        );
    }

    protected function output($result, $message)
    {
        $output = [
            'result'  => $result,
            'message' => $message,
        ];

        if (static::$DEBUG_TO_LOG === true) {
            //see debug log file with current date in the name
            Registry::log()->debug(var_export($output, true));
        }
        return $output;
    }

    protected function stringify($value)
    {
        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                $value = (string)$value;
            } else {
                $value = var_export($value, true);
            }
        } elseif (is_array($value)) {
            $value = var_export($value, true);
        }
        return (string)$value;
    }
}
