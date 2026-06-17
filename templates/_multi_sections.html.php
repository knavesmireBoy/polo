<?php
$article = $articles[0];
$type = 'multiple';
$sectionhead = null;
//classify.html.php expects a SINGLE $article the leading one contains the necessary info
//BUT since the article order may change all articles should have the same id/class combo 
include '_classify.html.php';
$attrs = trim($section_attrs);
include '_section_factory.php';

if (isset($attrs[1])) {
    $id = $attrs[0];
    $kls = $attrs[1]; 
    ?>
    <section id="<?= $id ?>" class="<?= trim($kls) ?>">
        <?php } else {
        if (preg_match('/=/', $attrs[0])) { ?>
            <section <?= $attrs[0] ?>>
            <?php } else { ?>
                <section class="<?= $attrs[0]; ?>">
            <?php }
    }

    foreach ($articles as $article) {
        include '__section.html.php';
    } ?>
</section>