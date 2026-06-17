<?php
$href = $myasset->getHref(true);
$alt = $myasset->getAlt(0);
$title = $myasset->getAlt(2) ?? '';
//current policy is to include background images in the same folder as display images and also reference them in the db
//! indicates the asset is to be used as a bg image (css) and not displayed
//a fair amount of refactoring to redirect bg images to another folder
$bg = is_int(strpos($myasset->attr_id, '!'));
if (!$bg) {
    $attr = $myasset->attr_id ? classify($myasset->attr_id) : '';

    if (fileExists(ARTICLE_IMG .  $myasset->path)) {  ?>
        <img src="<?= ARTICLE_IMG . $myasset->path ?? '' ?>" alt="<?= $alt ?? '' ?>" <?= $attr; ?> />
<?php }
    else { ?>
        <figure class=missing></figure>
   <?php }

}//bg
