<?php

namespace abc\modules\listeners;

use abc\core\engine\Registry;
use abc\core\lib\UserResolver;
use abc\models\BaseModel;
use abc\models\base\Product;
use ReflectionClass;

class ModelAuditListener
{

    protected $registry;
    const DECIMAL = 2;

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
            return [
                'result'  => false,
                'message' => 'ModelAuditListener: Argument 1 not instance of base model '.BaseModel::class,
            ];
        }

        //skip if auditing disabled for orm-model
        if (!$modelObject::$auditingEnabled) {
            return [
                'result'  => true,
                'message' => 'ModelAuditListener: Auditing of model '.$modelObject->getClass().' is disabled.',
            ];
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

        if (!$event_name) {
            return [
                'result'  => true,
                'message' => 'ModelAuditListener: Auditing of model '
                    .$modelObject->getClass().' on event '.$eventAlias.' not found.',
            ];
        }
        //get changed
        $newData = $modelObject->getDirty();
        if ($newData && $modelObject::$auditExcludes) {
            foreach ($modelObject::$auditExcludes as $excludeColumnName) {
                unset($newData[$excludeColumnName]);
            }
        }

        //if data still presents write log
        if (!$newData) {
            return [
                'result'  => true,
                'message' => 'ModelAuditListener: Nothing to audit of model '.$modelObject->getClass().'.',
            ];
        }

        $oldData = $modelObject->getOriginal();

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
            $error_message = __CLASS__.": Auditing of ".$modelObject->getClass()." failed.";
            $this->registry->get('log')->write($error_message);
            //???? need to check
            if ($modelObject::$auditingStrictMode) {
                throw new \Exception($error_message);
            }
            // stop event listeners firing
            return null;
        }

        return [
            'result'  => true,
            'message' => 'ModelAuditListener: Auditing of model '
                .$modelObject->getClass().' on event '.$eventAlias.' has been finished successfully.',
        ];
    }
}