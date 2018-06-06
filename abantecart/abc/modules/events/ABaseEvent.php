<?php

namespace abc\modules\events;

class ABaseEvent
{
    public $args;
    public function __construct()
    {
        $this->args = func_get_args();
    }
}