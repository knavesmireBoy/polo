<?php

namespace Ninja\Strategy;

class Checker
{
    public $message = null;
    public $strategy = null;
    public function __construct($msg, IStrategy $strategy)
    {
        $this->message = $msg;
        $this->strategy = $strategy;
        return $this;
    }
    public function validate($arg)
    {
        return $this->strategy->algorithm($arg);
    }
}
