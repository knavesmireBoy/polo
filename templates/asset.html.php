<?php
if (isset($link)) {
    $heading = empty($title) ? $link : $link . ': “' . $title . '”';
}
$heading = isset($heading) ? $heading : (empty($title) ? '' : $title);
$pathname = '';
$divklas = $replace ? "edit_asset replace" : "edit_asset";
//$pdfmatch = doWhen(partial('preg_match', '/\.pdf$/'), 'identity');
if (preg_match('/\.pdf$/', $asset->path ?? '') || $key === 'doppel') {
    $divklas .= ' pdf';
}

if ($replace && !$message) {
    $message = "Form is now in replace mode, any changes to the alt and meta_data fields will apply to the selected asset";
}

$datapath = $select['identity'] ?? '';
if ($datapath && strtoupper($datapath) === $datapath) {
    $datapath = strtolower($datapath);
    $pathname = "data[$datapath]";
} else {
    $pathname = $datapath;
}

?>
<div class="<?= $divklas ?>">
    <h3><?= $heading ?></h3>
    <?php if (!$replace) { ?>
        <input type="checkbox" id="usage">
        <label for="usage">Guide for Usage</label>
    <?php
    }
    $assetId = isset($asset) ? $asset->id : null;
    $upload = $routes['upload'];
    $freetext = $routes['add'];
    $doAssign = $assetId ? 'checked' : '';
    $checked = $doAssign ? 1 : 0;
    $manage = ASSET_MANAGE . $articleId ?? '';
    $assetuntracked  = ASSET_UNTRACKED . "article_img/0";

    $reinstate = "<strong>ADD/REINSTATE</strong> an <strong>archived</strong> asset to the article by using the <em>path</em> dropdown provided. Any <strong><em>changes</em></strong> to the other fields will be applied to the <strong>RETRIEVED</strong> asset.</p>";
    $doupload = "<p>You may prefer to <a href='$upload'>upload</a> a new asset, which provides the additional benefits of cropping, scaling and renaming of the file.</p>";
    $dropuse = "<p>The <strong>default</strong> intention of this form is simply to edit the <em>alt</em> and <em>meta_data</em> fields of the <strong>selected</strong> asset.</p><p>But it can be used, <strong>instead</strong>, to $reinstate";

    if ($key === 'assetfreearchive') {
        $dropuse = "<p>You may $reinstate</p>";
    }

    $freeuse = "<p>Use this form to edit the <em>alt</em> and <em>meta_data</em> fields of the <em>selected</em> file. You may also archive the file by unchecking the <em>assign</em> checkbox. You may <strong>INSTEAD</strong> reinstate a file - copied over from the uploads folder - by inputting a <strong>valid path</strong> into the path field. The subject of the form now becomes the reinstated file. To associate the asset with an article check the assign field or enter the article title. ";

    $xx = empty($untracked) ? '</p>' : "</p><p>You may view untracked files <a href=$assetuntracked >here</a>.<p>";
    $freeuse .= $xx;

    $freeoption = ", however, files residing in the <a href='#' title=$dir>target</a> folder can be similarly REINSTATED to the database using <a href='$freetext' >free text input</a></p>";
    $title = 'files that are referenced in the database but not assigned to an article';
    $disablefreeinput = !isset($super) ? 'disabled' : '';
    $dropdownoption = "<p>Only $manage_text will be present in the dropdown menu";
    $usage = isset($untracked) && empty($untracked) ? $dropdownoption . $freeoption : $dropdownoption . '.</p>';
    $overrule = '<p>By default new assets are <strong>ADDED</strong> to an article, to overrule this behaviour, return to the <a href="' . ASSETS_EDIT . $articleId . '">asset list</a> click the trash button, the ensuing page will present an  option to replace the resident asset and then return you to this form, choose your replacement file from the dropdown menu, the <em>replaced</em> file will be archived. Do bear in mind that only files of the same <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types" target="_blank" title="all about mime types">MIME type</a> may be swapped.</p>';
    $myuse = (empty($select['options']) && isset($super)) ? $freeuse : $dropuse;
    //$myuse = empty($select['options']) ? $freeuse : $dropuse;

    $klas = empty($select['options']) ? 'assign' : '';
    $checkbox = '<input type="checkbox" id="assign"  name="assign" value="' . $checked . '" ' . $doAssign . '>';
    $definput = '<input name="assign" id="assign">';
    $definput = $gallery ? '<input name="assign" id="assign" value="gallery">' : $definput;
    $_assign = $articleId > 0 ? $checkbox : $definput;

    $overrule = isset($asset) ? $overrule : '';
    //converts key to message, unless $message is explicitly set
    if (isset($key)) {
        include '_picvalidation.html.php';
    }
    if (!empty($message)) { ?>
        <h5><?= $message; ?></h5>
    <?php } ?>
    <div id="info">
        <?php
        if (!$replace) {
            if ($myuse === $dropuse) { ?>
                <?= $dropuse; ?>
                <?= $usage; ?>
                <?= $doupload; ?>
                <?= $overrule; ?>

            <?php } else { ?>
                <?= $freeuse; ?>
                <?= $doupload; ?>

        <?php  }
        } ?>
    </div>
    <form action="<?= $action; ?>" method="post" class="<?= $klas; ?>">
        <fieldset>
            <input type="hidden" id="orphans" name="orphans" value="<?= count($select['options']); ?>">
            <?php if ($assetId) { ?>
                <input type="hidden" id="pk" name="pk" value="<?= $assetId; ?>">
            <?php
            }
            if ($articleId) {
            ?>
                <input type="hidden" id="article_id" name="data[article_id]" value="<?= $articleId; ?>">
                <?php if (!empty($key)): ?>
                    <input type="hidden" id="<?= $key ?>" name="<?= $key ?>" value="<?= $key ?>">
                <?php endif; ?>
            <?php
            }
            if (!empty($select['options'])) {
            ?>
                <label for="path">path</label>
            <?php include '_myselector.html.php';
            } else { ?>
                <label for="path">path</label>
                <input type="text" name="<?= $pathname; ?>" id="path" placeholder="<?= $dir ?>" value="<?= $asset->path ?? $filename ?? '' ?>" required <?= $disablefreeinput; ?> pattern="[-A-Za-z\d._]+" maxlength="125">
            <?php }
            //allow editing of alt and attr_id fields only
            ?>
            <label for="alt">alt</label>
            <input type="text" name="data[alt]" id="alt" value="<?= $asset->alt ?? ' ' ?>" maxlength="63" pattern="[A-Za-z ]+">
            <label for="attr_id">meta_data</label>
            <input type="text" name="data[attr_id]" id="attr_id" placeholder="attr_id" value="<?= $asset->attr_id ?? '' ?>" maxlength="128" pattern="[^[\](){}]+">

            <?php if ($replace) { ?>
                <input type="hidden" id="replace" name="replace" value="<?= $replace; ?>">
            <?php
            } else if (empty($select['options'])) { ?>
                <label for="assign">assign</label>
            <?php
                echo $_assign;
            } ?>
        </fieldset>
        <input type="submit" value="submit" name="submit">
    </form>
    <div class="remplacer"><a href="<?= $exit ?>">Exit</a></div>
</div>