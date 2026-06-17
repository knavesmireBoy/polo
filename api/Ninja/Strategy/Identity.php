<?php
namespace Ninja\Strategy;
use \Ninja\Strategy\IStrategy;	
class Identity implements IStrategy 
{	
	public function algorithm($arg) 
	{	
		return $arg;
        
	}
}
