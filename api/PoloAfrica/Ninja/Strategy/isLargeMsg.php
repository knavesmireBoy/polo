<?php
namespace Ninja\Strategy;
use \Ninja\Strategy\IStrategy;		
class isLargeMsg implements IStrategy 
{	
	public function algorithm($arg) 
	{	
        return strlen($arg) < 1000;   
	}
}
