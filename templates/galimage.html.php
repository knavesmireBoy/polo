<?php
$link = GAL_UP;
if (isset($img->id)) {
    $link .= $img->id;
}

if (isset($key)) {
    include '_picvalidation.html.php';
}
if (isset($message)) { ?>
    <h5><?= $message; ?></h5>
<?php }
if (!empty($select['options'])) { ?>
    <h3>Edit/Assign pic details</h3>
<?php } else { ?>
    <h3>Assign a preloaded pic</h3>
<?php
} 

echo $para;
echo $optpara;
$path = empty($select['options']) ? 'path' : 'list';
?>
<form action="<?= $action ?? '' ?>" method="post" class="edit box">
    <fieldset>
        <input type="hidden" id="pk" name="pk" value="<?= $img->id ?? '' ?>">
        <label for="<?= $path ?>">path</label>
        <?php

        if (!empty($select['orphans'])) {
            include '_optgroupie.html.php';
        } else if (!empty($select['options'])) {
            include '_selector.html.php';
        } else { ?>
            <input type="text" name="path" id="path" value="<?= $img->path ?? '' ?>" placeholder="images/gallery/fullsize/image.ext" pattern="[A-Za-z\d.-_]+" maxlength="125">
        <?php }
        ?>
        <label for="box">box</label>
        <input type="number" name="box" id="box" step="1" min="0" max="92" value="<?= $box; ?>" />
        <div id="shuffle" title="click to shuffle pics"><input type="checkbox" name="shuffle" /></div>
        <label for="alt">alt</label>
        <input type="text" name="alt" id="alt" value="<?= $img->alt ?? '' ?>"  pattern="[A-Za-z\s/]+" maxlength="125">
        <label for="date">date</label>
        <input type="date" name="date" id="date" value="<?= $img->date ?? '' ?>">
    </fieldset>
    <input type="submit" value="submit">
</form>

<p class="remplacer"><a href="<?= $link ?>" id="upload_link" title="upload a pic">upload new pic</a>
    <a href="<?= GAL_REVIEW ?>" id="ret" title="back to review">back to review</a>
</p>