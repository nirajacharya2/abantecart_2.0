<?php

namespace abc\core\view;
require 'interface.php';
class AViewDefaultRender extends AViewRender implements AViewRenderInterface
{
    public function __construct($view, $instance_id = 0)
    {
        parent::__construct($view,$instance_id);
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
        extract($data);
        ob_start();
        /** @noinspection PhpIncludeInspection */
        require($file);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
}