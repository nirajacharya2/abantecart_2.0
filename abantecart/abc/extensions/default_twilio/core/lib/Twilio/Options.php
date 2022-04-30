<?php

namespace Twilio;

abstract class Options implements \IteratorAggregate
{
    protected $options = array();

    public function getIterator():\ArrayIterator
    {
        return new \ArrayIterator($this->options);
    }
}