<?php
namespace Ninja\Strategy;
use \Ninja\Strategy\IStrategy;		
class Empti implements IStrategy 
{	
	public function algorithm($arg) 
	{	
		return empty($arg);
	}
}
