<?php
namespace Ninja\Strategy;
use \Ninja\Strategy\IStrategy;
class Negator implements IStrategy 
{	
	public function __construct(private IStrategy $strategy){
        $this->strategy = $strategy;
    }
    
    public function algorithm($arg) 
	{	
		return !$this->strategy->algorithm($arg);
	}
}

