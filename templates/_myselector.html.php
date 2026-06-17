<?php
$options = $select['options'];
$id = $select['identity'];
$tgt = $select['target'];
$prop = $select['optval'];
$default = $select['default'] ?? '';
$required = isset($required) ? 'required' : '';
$disabled = $select['disabled'] ?? false;

if (!isset($selectname)) {
    $selectname = $id;
}
if (strtoupper($id) === $id) {
    $id = strtolower($id);
    $selectname = "data[$id]";
}
if(!empty($options) && is_array($options[0])){
    include '_selectassoc.html.php';
}
else {
    include '_selectclass.html.php';
}