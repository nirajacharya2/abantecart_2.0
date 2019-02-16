<?php

namespace abc\modules\listeners;

use abc\core\engine\Registry;
use abc\core\lib\UserResolver;
use abc\models\BaseModel;
use abc\models\catalog\Product;
use ReflectionClass;

class ModelAuditListener
{

    protected $registry;

    const DEBUG_TO_LOG = true;

    public function __construct()
    {
        $this->registry = Registry::getInstance();
    }

    /**
     * @param string $eventAlias
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function handle($eventAlias, $params)
    {

        /**
         * @var BaseModel | Product $modelObject
         */
        $modelObject = $params[0];

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
        $reflect = new ReflectionClass($modelObject);

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

        foreach ($newData as $colName => $newValue) {
            $auditData[] = [
                'user_type'      => $user_type,
                'user_id'        => $user_id,
                'user_name'      => $user_name,
                'event'          => $event_name,
                'request_id'     => $request_id,
                'session_id'     => $session_id,
                'auditable_type' => $reflect->getShortName(),
                'auditable_id'   => $auditable_id,
                'attribute_name' => $colName,
                'old_value'      => $oldData[$colName],
                'new_value'      => $newValue,
            ];
        }

        try {
            $this->registry->get('db')->table('audits')->insert($auditData);
        } catch (\PDOException $e) {
            $error_message = __CLASS__.": Auditing of ".$modelClassName." failed.";
            $this->registry->get('log')->write($error_message);
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
                .$modelClassName.' on event "'.$eventAlias.'" has been finished successfully.'
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