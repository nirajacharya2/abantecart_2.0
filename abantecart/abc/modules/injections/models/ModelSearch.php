<?php

namespace abc\modules\injections\models;

use abc\core\engine\Registry;
use abc\models\BaseModel;
use Illuminate\Database\Eloquent\Collection;

class ModelSearch
{
    /**
     * @var BaseModel $modelObj
     */
    protected $modelObj;

    public function __construct(BaseModel $model)
    {
        if (!$model::$searchMethod) {
            throw new \Exception('Model '.$model->getClass().' does not support searching.');
        }
        if (!method_exists($model, $model::$searchMethod)) {
            throw new \Exception('Model '.$model->getClass().' does not have search method '.$model::$searchMethod.'.');
        }
        $this->modelObj = $model;
    }

    public function getAvailableParameters()
    {
        return $this->modelObj::getSearchParams();
    }

    /**
     * @param array $params
     *
     * @return Collection|null
     */
    public function search(array $params)
    {
        $method = $this->modelObj::getSearchMethod();
        return $this->modelObj->{$method}($params);
    }
}