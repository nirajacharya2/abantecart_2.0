<?php

class ALibBase
{
    private $overload = [];

    /**
     * function extends libraries
     *
     * @param string         $new_method_name
     * @param string | array $callback (function name or array($object, $method_name))
     */
    public function addMethod($new_method_name, $callback)
    {
        $this->overload[$new_method_name] = $callback;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (isset($this->overload[$name])) {
            return call_user_func_array($this->overload[$name], $arguments);
        }
    }

}