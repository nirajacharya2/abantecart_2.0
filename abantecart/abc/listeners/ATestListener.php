<?php

namespace abc\listeners;

use abc\core\engine\Registry;
use abc\events\ATestEvent;

class ATestListener
{
    public function __construct()
    {
        //
    }

    /**
     *
     * @param  Registry $registry
     *
     * @return void
     */
    public function handle(Registry $registry)
    {
        echo 'Listener handled!';
    }
}