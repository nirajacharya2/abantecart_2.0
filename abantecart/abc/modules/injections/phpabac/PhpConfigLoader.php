<?php

namespace abc\modules\injections\phpabac;

use Symfony\Component\Config\Loader\FileLoader;

class PhpConfigLoader extends FileLoader
{
    public function load($resource, $type = null)
    {
        return include($resource);
    }

    public function supports($resource, $type = null): bool
    {
        return pathinfo($resource, PATHINFO_EXTENSION) === 'php';
    }
}
