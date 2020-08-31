<?php

namespace abc\core\view;

use abc\core\lib\AException;
use Twig_Environment;
use Twig_Loader_Filesystem;

class AViewTwigRender extends AViewRender
{
    protected $view;
    protected $twig;
    protected $twig_env = [];

    public function __construct($view, $instance_id = 0)
    {
        //check vendor classes
        if (!class_exists('Twig_Environment')) {
            throw new AException('Twig_Environment class not found!', AC_ERR_LOAD);
        }
        parent::__construct($view, $instance_id);
    }

    public function setTwigEnv(array $env = [])
    {
        $this->twig_env = array_merge($this->twig_env, $env);
    }

    /**
     * @param $file
     * @param $data
     *
     * @return string
     */
    public function fetch($file, $data)
    {
        //share AView instance with tpl scope
        $data['this'] = $this->view;
        $loader = new Twig_Loader_Filesystem(dirname($file));
        $twig = new Twig_Environment($loader, $this->twig_env);
        return $twig->render(basename($file), $data);
    }

}