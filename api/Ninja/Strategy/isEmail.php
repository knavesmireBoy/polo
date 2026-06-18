<?php
namespace Ninja\Strategy;
use \Ninja\Strategy\IStrategy;

class isEmail implements IStrategy 
{	
	public function algorithm($arg) 
	{	
		return preg_match('/^[\w][\w.-]+@[\w][\w.-]+\.[A-Za-z]{2,6}$/', $arg);
        
	}
}
