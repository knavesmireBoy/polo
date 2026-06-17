<?php
$vsrc =  '';
$vtype = '';
foreach ($videodata as $video) {
    $vsrc = $video['vsrc'];
    $vtype = $video['vtype'];
?>
    <source src="<?= $vsrc ?>" type=<?= $vtype; ?>>
<?php }
