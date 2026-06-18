<?php
namespace Ninja\Strategy;
use \Ninja\Strategy\IStrategy;	
class Context 
{
    private $strategy;
 
    public function __construct(IStrategy $strategy) 
	{
        $this->strategy = $strategy;
    }
 
    public function algorithm($arg) 
	{
        $this->strategy->algorithm($arg);
    }
}
