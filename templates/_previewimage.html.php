<?php
$href = ASSET_RELOAD . $file->id . '/reload';
//reload NOT reloaded which would display "your file was succesfully uploaded" not appropriate
if (is_numeric(strpos($previewklas, 'slide'))) {
    $href = ASSET_UPLOAD . $articleId . '/' . $file->id;
}
if (fileExists($path)) { ?>
    <figure><a class="preview" href='<?= $href; ?>' title="<?= $path ?>"><img alt='<?= $file->alt; ?>' src='<?= $path ?>' /></a>
        <?php if (isset($info[$k])) { ?>
            <figcaption><?= 'ratio: ' . $info[$k]['ratio'] . ' / max: ' . $info[$k]['max'] . 'px' ?></figcaption>
        <?php } ?>
    </figure>
<?php
} else { ?>
    <figure class="notfound">
        <a href="<?= ASSET_EDIT ?><?= $file->id ?>" title="<?= $path; ?>"></a>
    </figure>

<?php  }
