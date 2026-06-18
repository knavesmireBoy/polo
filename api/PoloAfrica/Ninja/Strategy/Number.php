<?php
namespace Ninja\Strategy;
use \Ninja\Strategy\IStrategy;	
class Number implements IStrategy 
{	
	public function algorithm($arg) 
	{	
        //https://stackoverflow.com/questions/7649752/php-is-numeric-or-preg-match-0-9-validation
		return preg_match('/^[1-9][0-9 -]{0,15}$/', $arg);
	}
}
