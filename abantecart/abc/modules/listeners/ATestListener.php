<?php

namespace abc\modules\listeners;

use abc\core\engine\Registry;

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