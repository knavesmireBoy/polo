<?php

namespace Ninja\Composite;

use \Ninja\Component;

include_once 'config.php';
include_once FUNCTIONS;

class Leaf implements Component
{

    public function __construct()
    {
    }

    public function addItem(Component $com)
    { /*Leaf does not need */
    }
    public function removeItem(Component $com)
    { /*Leaf does not need */
    }
    public function getItem($i = 0)
    { /*Leaf does not need */
    }
}
