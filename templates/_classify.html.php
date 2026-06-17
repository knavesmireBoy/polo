<?php
$parent = "";
$multi = false;
$section_attrs = "";
$i = 0;
//NOTE $klas supplied by template don't overrwite

$scan = lazyMittel('scanMultiDir', [IMAGES, ASSETS, VIDEOS, RESOURCES], ['thumb']);
$get = curry2(fn($o, $p) => $o->{$p})('path');
$cb = composer('fileExists', $scan, $get);
$notBg = fn($o) => $o->attr_id !== '!';

if (isset($article->assets) && isset($article->assets[0])) {
    $article->assets = safeFilter($article->assets, $cb);
    $article->assets = safeFilter($article->assets, $notBg);
    if (count($article->assets) > 1) {
        $parent = classify('.multi');
        $multi = true;
    }
    if (isset($article->assets[0]->attr_id)) {
        $kls = classify($article->assets[0]->attr_id, false);

        if ($multi && !empty($kls)) {
            $parent = preg_replace('/(=\w+)/', '$1&nbsp;multi', $kls);
        }
    }
}
if (!empty($article->attr_id)) {
    if (!empty($parent)) {
        //true returns id only; ASSUMES child class USURPS any parent class
        //thinking portait/landscape; mobile/desktop
        $id = classify($article->attr_id, true);
        $kls = classify($article->attr_id, false);
        if (preg_match('/=$/', $parent)) { //empty : "class="
            $parent = $kls;
        } else if (!preg_match('/=$/', $kls)) {
            $k = explode('.', $article->attr_id);
            if(isset($k[1])){
                $k = $k[1];
                $parent = preg_replace('/(=\w+)/', "$1&nbsp;$k", $parent);
            } 
        }
        $section_attrs = "$id $parent";
    } else {
        $section_attrs = classify($article->attr_id);
    }
} else {
    $section_attrs = empty($section_attrs) ? $parent : $section_attrs;
}
