<?php
//dump($article);
include '_classify.html.php';
//DON'T FORGET classify returns "id='myid'" OR class="myclass" OR "id='myid' class='myclass'"
$type = 'singular';

include '_section_factory.php';

if (isset($attrs[1])) {
    list($id, $kls) = $attrs;
?>
    <section id="<?= $id ?>" class="<?= trim($kls) ?>">
        <?php } else {
        $_multi = preg_match('/multi/i', $attrs[0]);
        $classy = $attrs[0];
        if($_multi){
            $isMulti = partial('queryMulti', $classy);
        }
        if (preg_match('/=/', $classy) || empty($classy)) { ?>
            <section <?= $classy; ?>>
            <?php } else { ?>
                <section class="<?= $classy; ?>">
            <?php }
    }

    include '__section.html.php'; ?>
                </section>