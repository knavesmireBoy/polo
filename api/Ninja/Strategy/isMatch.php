<?php

namespace Ninja\Strategy;
use \Ninja\Strategy\IStrategy;

class isMatch implements IStrategy
{
    private $match;
    public function __construct($match)
    {
        $this->match = $match;
    }
    public function algorithm($arg)
    {
        return preg_match($this->match, $arg);
    }
}
