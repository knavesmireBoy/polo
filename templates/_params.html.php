<?php
include_once 'funcs.php';
$myratio = $_ratio ? $_ratio : $ratio;
$offset = $_offset ? $_offset : .5;
$appearance = $_appearance ? $_appearance : -1;
$maxsize = $_maxsize ? $_maxsize : 0;
?>
<div id="params">
    <label for="ratio">ratio</label>
    <input id="ratio" name="floats[ratio]" value="<?= $myratio; ?>" minlength=1 maxlength=7>
    <label for="offset">offset</label>
    <input id="offset" name="floats[offset]" value="<?= $offset; ?>" minlength=1 maxlength=13>
    <label for="appearance">appearance</label>
    <input id="appearance" name="ints[appearance]" value="<?= $appearance; ?>" minlength=1 maxlength=7>
    <label for="maxsize">max</label>
    <input id="maxsize" name="ints[maxsize]"  value="<?= $maxsize; ?>" minlength=1 maxlength=7>
</div>