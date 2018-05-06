<?php

namespace abc\core\view;

require_once __DIR__.DS.'ViewRenderInterface.php';

abstract class AViewRender implements AViewRenderInterface
{
    protected $view;

    public function __construct($view, $instance_id = 0)
    {
        $this->view = $view;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->view->{$name};
    }

    /**
     * To allow to call AView methods from tpl-files
     *
     * @param string $function_name
     * @param array  $args
     *
     * @return mixed|null
     */
    public function __call($function_name, $args)
    {
        if (method_exists($this->view, $function_name) && is_callable([$this->view, $function_name])) {
            return call_user_func_array(array($this->view, $function_name), $args);
        } else {
            return null;
        }
    }
}