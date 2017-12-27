<?php

namespace abc\lib;

class AViewDefaultRender
{
    protected $view;

    public function __construct($view, $instance_id = 0)
    {
        $this->view = $view;
    }

    public function __get($name)
    {
        return $this->view->{$name};
    }
    //allow to call AView methods from tpl-files
    public function __call($function_name, $args)
    {
        if (method_exists($this->view, $function_name)) {
            return call_user_func_array(array($this->view, $function_name), $args);
        } else {
            return null;
        }
    }

    /**
     * @param $file
     * @param $data
     *
     * @return string
     */
    public function fetch($file, $data)
    {
        $data['this'] = $this->view;
        extract($data);
        ob_start();
        /** @noinspection PhpIncludeInspection */
        require($file);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

}