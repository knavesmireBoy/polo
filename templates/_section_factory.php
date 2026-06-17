<?php

$attr = trim($section_attrs);
$attrs = [];

//https://stackoverflow.com/questions/15797622/php-i-cant-echo-spaces-into-a-class-name
if (preg_match('/&nbsp;/', $attr)) {
    $attr = preg_replace('/&nbsp;/', " ", $attr);
    if (preg_match('/class=/', $attr)) {
        $attr = preg_replace('/class=/', "", $attr);
        if (preg_match('/id=/', $attr)) {
            $id = preg_replace('/id=(\w+)[\s\w]+/', "$1", $attr);
            $kls = preg_replace('/id=\w+\s([\s\w]+)/', "$1", $attr);
            $attrs[] = $id;
            $attrs[] = $kls;
        } else {
            $attrs[] = $attr;
        }
    }
} else {
    $attrs[] = $attr;
}
