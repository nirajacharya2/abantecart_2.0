<?php

namespace abc\models;

use abc\core\ABC;
use Illuminate\Database\Eloquent\Scope;

trait InitializeModel
{
    public function initializeInitializeModel()
    {
        $env = ABC::getEnv();
        $modelClassName = $this->getClass();

        // extends of scopes (additional methods)
        if ($env['MODEL']['INITIALIZE'][$modelClassName]['scopes']) {
            foreach ($env['MODEL']['INITIALIZE'][$modelClassName]['scopes'] as $scopeClassName) {
                //add scope class into global scope
                if(class_exists($scopeClassName)) {
                    $scope = new $scopeClassName();
                    if ($scope instanceof Scope) {
                        static::addGlobalScope($scope);
                    }
                }
            }
        }

        //extends properties
        if ($env['MODEL']['INITIALIZE'][$modelClassName]['properties']) {
            foreach ($env['MODEL']['INITIALIZE'][$modelClassName]['properties'] as $property => $values) {
                // add properties
                if (is_array($values) && !empty($values)) {
                    $this->{$property} = array_merge((array)$this->{$property}, $values);
                }
            }
        }

    }
}
