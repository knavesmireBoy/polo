<?php

namespace Ninja\Composite;

use \Ninja\Component;

include_once 'config.php';
include_once FUNCTIONS;

class Composite implements Component
{
    protected $items = [];

    public function __construct()
    {
    }

    public function addItem(Component $comp)
    {
        if (!in_array($comp, $this->items)) {
            array_push($this->items, $comp);
        }
    }

    public function removeItem(Component $comp)
    {
        //Reserved for code to remove component
    }

    public function getItem($i = 0)
    {
        if (!empty($i)) {
            return $this->items[$i];
        }
        return $this->items;
    }
}
