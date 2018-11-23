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
use abc\core\lib\AException;
use H;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as OrmModel;
use Illuminate\Database\Eloquent\Builder;
use Exception;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class AModelBase
 *
 * @package abc\models
 * @method static Collection find(integer $id)
 * @method static Builder where(string $column, string $operator, mixed $value = null, string $boolean = 'and')
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
     * @var \abc\core\lib\ADB
     */
    protected $db;
    /**
     * @var array
     */
    protected $errors;

    protected $rules = [];

    protected $permissions = [
        self::CLI      => ['update', 'delete'],
        self::ADMIN    => ['update', 'delete'],
        self::CUSTOMER => [],
    ];

    /**
     * AModelBase constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->actor = H::recognizeUser();
        $this->registry = Registry::getInstance();
        $this->config = $this->registry->get('config');
        $this->cache = $this->registry->get('cache');
        $this->db = $this->registry->get('db');
        parent::__construct($attributes);
    }



    /**
     * @return array
     */
    public function rules()
    {
        return $this->rules;
    }

    /**
     * @return array
     */
    public function getPermissions(): array
    {
        return $this->permissions[$this->actor['user_type']];
    }

    /**
     * @param $operation
     *
     * @return bool
     */
    public function hasPermission($operation): bool
    {
        return in_array($operation, $this->getPermissions());
    }

    /**
     * Extend save the model to the database.
     *
     * @param  array $options
     *
     *
     * @throws Exception
     */
    public function save(array $options = [])
    {
        if ($this->hasPermission('update')) {
            if (!$this->validate($this->all())) {
                throw new Exception(
                    'Class '. __CLASS__
                    .' Validation before save failed: '
                    . implode("\n",$this->errors)
                );
            }
            parent::save();
        } else {
            throw new Exception('No permission for object (class '.__CLASS__.') to save the model.');
        }
    }

    /**
     * @throws Exception
     */
    public function delete()
    {
        if ($this->hasPermission('delete')) {
            parent::delete();
        } else {
            throw new Exception('No permission for object to delete the model.');
        }
    }

    /**
     * @param $data
     *
     * @return bool
     */
    public function validate($data)
    {
        if ($rules = $this->rules()) {
            $v = new Validator(new ValidationTranslator(), $data, $rules);
            try {
                $v->validate();
            } catch (ValidationException $e) {
            }
            if ($v->fails()) {
                $this->errors = $v->errors()->toArray();
                return false;
            }
        }
        return true;
    }

    /**
     * @param string|array $input
     */
    public function addFillable($input)
    {
        if (is_string($input)) {
            $this->fillable[] = $input;
        } elseif (is_array($input)) {
            $this->fillable = array_merge($this->fillable, $input);
        }
    }

    /**
     * @param string $name
     */
    public function removeFillable($name)
    {
        $key = array_search($name, $this->fillable);
        if ($key !== false) {
            unset($this->fillable[$key]);
        }
    }

    /**
     * @param array $rules
     */
    public function appendRules(array $rules)
    {
       $this->rules = array_merge($this->rules, $rules);
    }

    /**
     * @param string $key
     */
    public function removeRule(string $key)
    {
       unset($this->rules[$key]);
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function updateRule(string $key, string $value)
    {
       $this->rules[$key] = $value;
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
     *
     * @throws \ReflectionException
     */
    public function updateRelationships(array $data)
    {
        foreach ($this->getRelationships() as $relationship_name => $relationship_details) {
            if (isset($data[$relationship_name])) {
                switch ($relationship_details['type']) {
                    case 'BelongsToMany':
                        $this->syncBelongsToManyRelationship(
                            $relationship_name,
                            $data[$relationship_name]
                        );
                        break;
                    case 'MorphMany':
                    case 'HasMany':
                        $this->syncHasManyRelationship(
                            $relationship_details['model'],
                            $data[$relationship_name]
                        );
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
        /**
         * @var OrmModel|\Illuminate\Database\Query\Builder|Builder $relObj
         */
        $relObj = $this->$relationship_name();
        if ($relObj) {
            $relObj->fill($data)->save();
        } else {
            $this->$relationship_name()->create($data);
        }
    }

    /**
     * @param $model
     * @param array $data
     */
    private function syncHasManyRelationship($model, array $data)
    {
        $presentIds = [];
        /**
         * @var OrmModel|\Illuminate\Database\Query\Builder|Builder $relObj
         */
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
            //TODO implement deletion of relations that were not updated
        } else {
            $id = $relObj->primaryKey;
            foreach ($data as $related) {
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
     * @param array $data
     *
     * @return mixed
     */
    private function syncBelongsToManyRelationship($relationship_name, array $data)
    {
        return $this->$relationship_name()->sync($data);
    }

    /**
     * @return array
     * @throws \ReflectionException
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
     * @param  \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        if (isset($this->primaryKeySet)) {
            foreach ($this->primaryKeySet as $key) {
                $query->where($key, '=', $this->getAttribute($key));
            }
        } else {
            parent::setKeysForSaveQuery($query);
        }
        return $query;
    }

    public static function __callStatic($method, $parameters)
    {
        //check permissions for static methods of model
        $abac = Registry::getInstance()->get('abac');
        if($abac && !$abac->hasAccess(__CLASS__)){
            throw new AException('Forbidden');
        }
        return parent::__callStatic($method, $parameters);
    }

}