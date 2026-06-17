<?php
$pdfs = [];
$_max = 125;
$video = false;
$doppel = '';
$uploadguide = isset($_COOKIE['upload_guide']);
if (isset($_COOKIE['doppel'])) {
    $doppel = $_COOKIE['doppel'];//populate meta_data with the phrase that appears more than once
    unset($_COOKIE['doppel']);
    setcookie('doppel', '', -1, '/');
}
$guide = $uploadguide ? "please restore the <a href='/asset/upload/$articleId' id='restore_guide'>guide</a> for further information." : 'please refer to the GUIDE for naming files using the alt field.';
$id = $select['identity'];
if (strtoupper($id) === $id) {
    $id = strtolower($id);
    $selectname = "data[$id]";
} else {
    $selectname = $id;
}

$heading = empty($mytitle) ? 'Upload Asset' : 'Upload Asset' . ': “' . $mytitle . '”';
$retrive = "<p>MOVE FILES FROM THE UPLOAD FOLDER TO THE CURRENT WORKING DIRECTORY</p>";
if (isset($key)) {
    include '_picvalidation.html.php';
}
if (isset($message)) { ?>
    <h5><?= $message; ?></h5>
<?php } ?>
<div class="edit_asset">
    <h3><?= $heading ?></h3>
    <p>By default new files are <strong>ADDED</strong> to an article, you can use the dropdown menu to select a candidate for replacing, the candidate file will be archived, which means the <strong>replaced</strong> file will still exist in the target folder and a reference for it can then be found in a list of <?= $manage_text; ?> where you then have the option to remove the file entirely.</p>
    <p>Re-Uploading the EXACT SAME NAMED FILE will UPDATE your current file with the selected attributes, <?= $guide; ?></p>
    <?php
    // DECIDED TO PREVENT ACCESS TO FILE EDITING FROM THE UPLOAD FORM
    if (!empty($archived)) { ?>
        <p>Any currently archived files can be found in a <a href="<?= $routes['assign'] ?>">dropdown menu</a> for assigning to an article.</p>
    <?php }

    ?>
    <div class="uploadpreview <?= $previewklas; ?>">
        <?php
        if (empty($omitguide) && !$uploadguide) {
            include '_uploadguide.html.php';
        }
        ?>
        <form action="<?= $action; ?>" method="post" enctype="multipart/form-data" class="edit upload <?= $warning; ?>">
            <fieldset>
                <label for="<?= $fileinputname; ?>">upload</label>
                <input id="<?= $fileinputname; ?>" type="file" name="<?= $fileinputname; ?>" <?= $accept; ?>>
                <?php if (!empty($select['options'])) {
                    include '_myselector.html.php';
                } ?>
                <label for="alt">alt</label>
                <input id="alt" name="data[alt]" value="<?= $_alt ?? ' ';?>" maxlength="<?= $_max; ?>" pattern="[A-Za-z\s/]+"/>
                <label for="attr_id">meta_data</label>
                <input id="attr_id" name="data[attr_id]" value="<?= $_attrid ?? $doppel; ?>" maxlength="125" pattern="[^[\](){}]+"/>
                <?php include '_params.html.php'; ?>
                <input type="hidden" name="action" value="upload" id="upload" />
                <input type="hidden" name="data[article_id]" id="article_id" value="<?= $articleId ?? ''; ?>" />
                <input type="hidden" id="<?= $key ?>" name="<?= $key ?>" value="<?= $key ?>">
            </fieldset>
            <input type="submit" value="upload">
        </form>
        <?php if (!empty($files)) {
            if ($reloaded) {
                //most recent file
                $files = mypluck($files, true);
            }
            include '_previews.html.php';
        } ?>
    </div>
    <div class="remplacer"><a href="<?= $exit; ?>">Exit</a>
    </div>