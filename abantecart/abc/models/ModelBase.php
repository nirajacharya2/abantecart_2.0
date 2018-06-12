<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */

namespace abc\models;

use abc\core\engine\Registry;
use Illuminate\Database\Eloquent\Model as OrmModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\validation\Validator;
use abc\core\helper\AHelperUtils;
use Exception;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class AModelBase
 *
 * @package abc\models
 */
class AModelBase extends OrmModel
{
    /**
     *
     */
    const CREATED_AT = 'date_added';
    /**
     *
     */
    const UPDATED_AT = 'date_modified';
    /**
     *
     */
    const CLI = 0;
    /**
     *
     */
    const ADMIN = 1;
    /**
     *
     */
    const CUSTOMER = 2;

    /**
     * @var array
     */
    protected $actor;
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var \abc\core\lib\AConfig
     */
    protected $config;
    /**
     * @var \abc\core\cache\ACache
     */
    protected $cache;
    /**
     * @var \abc\core\lib\db
     */
    protected $db;
    /**
     * @var array
     */
    protected $errors;

    protected $permisions = [
        self::CLI      => ['update', 'delete'],
        self::ADMIN    => ['update', 'delete'],
        self::CUSTOMER => [],
    ];

    /**
     * AModelBase constructor.
     */
    public function __construct()
    {
        $this->actor = AHelperUtils::recognizeUser();
        $this->registry = Registry::getInstance();
        $this->config = $this->registry->get('config');
        $this->cache = $this->registry->get('cache');
        $this->db = $this->registry->get('db');
    }

    /**
     * @param none
     *
     * @return array
     */
    public function getPermisions(): array
    {
        return $this->permisions[$this->actor['user_type']];
    }

    /**
     * @param $operation
     *
     * @return bool
     */
    public function hasPermision($operation): bool
    {
        return in_array($operation, $this->getPermisions());
    }

    /**
     * Extend save the model to the database.
     *
     * @param  array $options
     *
     * @return bool
     *
     * @throws Exception
     */
    public function save(array $options = [])
    {
        if ($this->hasPermision('update')) {
//            if ($this->validate($this->all())) {
//                throw new Exception('Validation failed');
//            }
            parent::save();
        } else {
            throw new Exception('No permission for object to save the model.');
        }
    }

    /**
     * @throws Exception
     */
    public function delete()
    {
        if ($this->hasPermision('delete')) {
            parent::delete();
        } else {
            throw new Exception('No permission for object to delete the model.');
        }
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public function validate($data)
    {
        if ($rules = $this->rules()) {
            $v = Validator::make($data, $rules);
            if ($v->fails()) {
                $this->errors = $v->errors;
                return false;
            }
            return true;
        }
    }

    /**
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * @param array $data
     */
    public function updateRelationships(array $data)
    {
        foreach ($this->getRelationships() as $relationship_name => $relationship_details) {
            if (isset($data[$relationship_name])) {
                switch ($relationship_details['type']) {
                    case 'BelongsToMany':
                        $this->syncBelongsToManyRelationship($relationship_name, $data[$relationship_name]);
                        break;
                    case 'MorphMany':
                    case 'HasMany':
                        $this->syncHasManyRelationship($relationship_name, $relationship_details['model'], $data[$relationship_name]);
                        break;
                    case 'HasOne':
                        $this->syncHasOneRelationship($relationship_name, $data[$relationship_name]);
                        break;
                    default:
                        break;
                }
                unset($data[$relationship_name]);
            }
        }
    }

    /**
     * @param string $relationship_name
     * @param array $data
     */
    private function syncHasOneRelationship($relationship_name, array $data)
    {
        $relObj = $this->$relationship_name();
        if ($relObj) {
            $relObj->fill($data)->save();
        } else {
            $this->$relationship_name()->create($data);
        }
    }

    /**
     * @param string $relationship_name
     * @param array $data
     */
    private function syncHasManyRelationship($relationship_name, $model, array $data)
    {
        $presentIds = [];
        $relObj = new $model;
        if (isset($relObj->primaryKeySet)) {
            //process composite primary keys relationship
            foreach ($data as $related) {
                $conditions = [];
                foreach ($relObj->primaryKeySet as $key) {
                    if ($key == $this->primaryKey) {
                        $related[$key] = $this->$key;
                    }
                    $conditions[$key] = isset($related[$key]) ? $related[$key] : null;
                }
                $updated = $relObj->updateOrCreate($conditions, $related);
                $keys = [];
                foreach ($relObj->primaryKeySet as $key) {
                    $keys[$key] = $updated->$key;
                }
                $presentIds[] = $keys;
            }
            //TODO implment deletion of relations that were not updated
        } else {
            foreach ($data as $related) {
                $id = $relObj->primaryKey;
                $conditions = [
                    $id => isset($this->$id) ? $this->$id : null,
                ];
                $presentIds[] = $relObj->updateOrCreate($conditions, $related)->$id;
            }
            $relObj->whereNotIn($id, $presentIds)->delete();
        }
    }

    /**
     * @param string $relationship_name
     * @param array  $data
     *
     * @return mixed
     */
    private function syncBelongsToManyRelationship($relationship_name, array $data)
    {
        return $this->$relationship_name()->sync($data);
    }

    /**
     * @return array
     */
    public function getRelationships()
    {
        $model = new static;
        $relationships = [];
        foreach ((new ReflectionClass($model))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class != get_class($model)
                || !empty($method->getParameters())
                || $method->getName() == __FUNCTION__
            ) {
                continue;
            }

            try {
                $return = $method->invoke($model);

                if ($return instanceof Relation) {
                    $relationships[$method->getName()] = [
                        'type'  => (new ReflectionClass($return))->getShortName(),
                        'model' => (new ReflectionClass($return->getRelated()))->getName(),
                    ];
                }
            } catch (Exception $e) {
            }
        }
        return $relationships;
    }

    /**
     * Set the keys for a save update query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        if (isset($this->primaryKeySet)) {
            foreach ($this->primaryKeySet as $key) {
                $query->where($key, '=', $this->getAttribute($key));
            }
        }
        return $query;
    }
}