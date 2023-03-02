<?php

namespace abc\modules\scopes;

use abc\core\ABC;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CategoryModelScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     * NOTE: to avoid of usage this scope use withoutScope() method of the query builder
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (ABC::env('IS_ADMIN') !== true) {
            $builder->where('active_products_count', '>', 0);
        }
    }
}