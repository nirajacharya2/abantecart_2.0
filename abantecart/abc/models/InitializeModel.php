<?php

namespace abc\models;

use abc\core\ABC;

trait InitializeModel
{
    public function initializeInitializeModel()
    {
        $env = ABC::getEnv();
        $shortClassName = (new \ReflectionClass($this))->getShortName();
        if ($env['MODEL']['INITIALIZE'][$shortClassName]) {
            foreach ($env['MODEL']['INITIALIZE'][$shortClassName] as $key => $value) {
                if (is_array($value) && !empty($value)) {
                    $this->{$key} = array_merge($this->{$key}, $value);
                }
            }
        }
    }
}
