<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2023 Belavier Commerce LLC
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
namespace abc\modules\injections\models;

use abc\models\BaseModel;
use Exception;
use Illuminate\Support\Collection;

class ModelSearch
{
    /**
     * @var BaseModel $modelObj
     */
    protected $modelObj;

    public function __construct(BaseModel $model)
    {
        if (!$model::$searchMethod) {
            throw new Exception('Model ' . $model->getClass() . ' does not support searching.');
        }
        if (!method_exists($model, $model::$searchMethod)) {
            throw new Exception('Model ' . $model->getClass() . ' does not have search method ' . $model::$searchMethod . '.');
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