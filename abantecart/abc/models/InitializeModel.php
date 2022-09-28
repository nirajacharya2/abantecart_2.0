<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2022 Belavier Commerce LLC
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
                if (is_array($values) && !empty($values) && property_exists($this, $property)) {
                    $this->{$property} = array_merge((array)$this->{$property}, $values);
                }
            }
        }
        //need to remove this in the future!
        if($this->dates){
            foreach($this->dates as $attrName){
                if(!isset($this->casts[$attrName]) || $this->casts[$attrName] == 'datetime'){
                    $this->casts[$attrName] = 'datetime:'.static::$defaultDatetimeStringFormat;
                }
            }
        }
        if(in_array('datetime', $this->casts)){
            foreach($this->casts as $attrName => $attrType){
                if($this->casts[$attrName] == 'datetime'){
                    $this->casts[$attrName] = 'datetime:'.static::$defaultDatetimeStringFormat;
                }
            }
        }
        //end of section to remove
    }
}
