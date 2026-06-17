<?php

namespace Ninja\Strategy;

use \Ninja\Strategy\IStrategy;

class isName implements IStrategy
{
	public function algorithm($arg)
	{
		//allow for an initial John D Rockefeller. Mr John D. Rockefeller
		$res = explode(' ', $arg);
		$ret = [];
		$i = 0;
		$L = count($res);
		for ($i = 0; $i < $L; $i++) {
			$test = true;
			if ($i >= 1 && !empty($res[$i + 1]) && strlen($res[$i]) <= 2) {
				$test = preg_match('/^[A-Z]\.?$/', $res[$i]) ? false : true;
			}
			if ($test) {
				$ret[] = $res[$i];
			}
		}
		$res = array_map(fn ($str) => preg_match('/^[\w]{2,20}$/', $str), $ret);
		return array_reduce($res, fn ($a, $c) => $a && $c, $res[0]);
	}
}
