<?php

namespace abc\core\view;

class AViewDefaultRender extends AViewRender
{
    public function __construct($view, $instance_id = 0)
    {
        parent::__construct($view, $instance_id);
    }

    /**
     * @param $file
     * @param $data
     *
     * @return string
     */
    public function fetch($file, $data)
    {
        extract($data);
        ob_start();
        /** @noinspection PhpIncludeInspection */
        require($file);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
}