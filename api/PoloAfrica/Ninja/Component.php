<?php

namespace Ninja;

include_once 'config.php';
include_once FUNCTIONS;

interface Component
{
    public function addItem(Component $com);
    public function removeItem(Component $com);
    public function getItem($i = 0);
}