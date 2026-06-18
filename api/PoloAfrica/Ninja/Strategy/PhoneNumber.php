<?php
namespace Ninja\Strategy;
use \Ninja\Strategy\IStrategy;
class PhoneNumber implements IStrategy 
{	
	public function algorithm($arg) 
	{	
        //https://stackoverflow.com/questions/7649752/php-is-numeric-or-preg-match-0-9-validation
		return preg_match('/^([0-9]{3,}[0-9 -]{0,8})+$/', $arg);
	}
}