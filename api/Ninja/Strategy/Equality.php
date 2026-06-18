<?php
namespace Ninja\Strategy;
use \Ninja\Strategy\IStrategy;	
class Equality implements IStrategy
{
    private $match;
    public function __construct($match)
    {
        $this->match = $match;
    }
    public function algorithm($arg)
    {
        return $arg === $this->match;
    }
}
