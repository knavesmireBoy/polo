<?php
$incsubmit = true;
$assign = '';
$link = '';

$imgId = $img ? $img->id : 0;
$_untracked = GAL_MANAGE  . "-1";// -1: manage expects an articleId it may be empty, but -1 indicates the request originated from gallery
$para = isset($_COOKIE['upload_guide']) ? " Please restore the <a href='/gallery/upload/$imgId/true' id='restore_guide'>guide</a> for further information." : '';

$archived = $select['options'] !== [];
$myfilestore = $filestore !== [];
$myuntracked = $untracked !== [];
$rm = $archived ? ' Remove any archived images <a href="' . $_untracked  . '">here*</a>.' : '';

$rm = !$rm && ($myfilestore || $myuntracked) ? ' Assign backup images <a href="' . $_untracked  . '">here*</a>.' : $rm;

$info = "*NOTE: ALL FILES ARE COPIED FROM AN UPLOAD FOLDER TO AN APPROPRIATE DESTINATION FOLDER";
$info = $rm ? $info : substr($info, 1);

$para .= $rm;

$imgroute = preg_match('/slide/', $previewklas);

if ($imgId) {
    $imgroute = $imgroute ? GAL_UP . $img->id : GAL_UP . $img->id  . "/$img->id";
}


if (isset($img->id) && isset($super)) {
    $link = GAL_ASSIGN . $img->id;
    $assign = "<p>If you prefer you can simply <a href='$link'>assign</a> a library pic to a box. 
    $para </p><p>$info</p>";  
    $assign = $archived ? $assign : "<p>$para</p><p>$info</p>";
}

if (isset($key)) {
    include '_picvalidation.html.php';
}
if (isset($message)) { ?>
    <h5><?= $message; ?></h5>
<?php } ?>
<h3>Upload pic to gallery</h3>
<?= $assign ?>
<div class="uploadpreview gallery <?= $previewklas; ?>">
    <?php
    if (empty($omitguide) && !isset($_COOKIE['upload_guide'])) {
        include '_uploadguide.html.php';
    }
    ?>

    <form action="<?= $action; ?>" method="post" enctype="multipart/form-data" class="edit gallery upload <?= $warning; ?>">
        <fieldset>
            <label for="<?= $filename; ?>">upload</label>
            <input id="<?= $filename; ?>" type="file" name="<?= $filename; ?>" <?= $accept; ?>>
            <label for="box">box</label>
            <input type="number" name="box" id="box" step="1" min="0" max="92" value="<?= $box ?? '' ?>">
            <label for="alt">alt</label>
            <input id="alt" name="data[alt]" value="<?= $_alt ?? ' '; ?>" pattern="[A-Za-z /]+" maxlength="125"/>
            <?php include '_params.html.php'; ?>
            <input type="hidden" name="action" value="upload" id="upload" />
        </fieldset>
        <input type="submit" value="upload">
    </form>
    <?php
    if (isset($img)) {
        //don't show preview if uploading new pic to empty slot
    ?>
        <div class="previews">
            <?php
            if (!empty($img->path) && fileExists(GALLERY_IMG .  $img->path)) { ?>
                <figure><a href="<?= $imgroute ?? '' ?>"><img alt='<?= $img->alt; ?>' src='<?=  GALLERY_IMG. $img->path ?>' /></a>
                    <?php if (!empty($fileinfo)) { ?>
                        <figcaption><?= 'ratio: ' . $fileinfo['ratio'] . '<br> max: ' . $fileinfo['max'] . 'px' ?></figcaption>
                    <?php } ?>
                </figure>
            <?php
            } else { ?>
                <figure class="notfound">
                    <a href="<?= ASSET_EDIT ?><?= $img->id; ?>" title="file not found"></a>
                </figure>
            <?php }
            ?>
        </div>
    <?php } ?>
</div>
<p class="remplacer"><a href="<?= GAL_REVIEW ?>" id="ret" title="back to review">back to review</a></p>