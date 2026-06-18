<?php
namespace Ninja\Strategy;
use \Ninja\Strategy\IStrategy;		
class isSmallMsg implements IStrategy 
{	
	public function algorithm($arg) 
	{	
		return strlen($arg) > 15;
        
	}
}
