<form action="<?= $action ?>/manage/" method="post" class="orphans">
    <ul class="adminlist">
        <?php
        $sub = false;
        if (count($group) || $backup) {
            $sub = true;
        }
        if (count($group) > 1) { ?>
            <input type="checkbox" name="all" id="all">
            <label for="all"></label>
        <?php
        }

        foreach ($group as $item) {
            $path = $dir . $item->path;
            if (preg_match('/pdf$/', $item->path)) {
                $path = doScanDir(ASSETS, $item->path);
                if (!fileExists($path)) {
                    $path = doScanDir(RESOURCES, $item->path);
                }
            }
            $path = fileExists($path) ? $path : FILENOTFOUND; ?>
            <li>
                <input type="checkbox" name="pics[]" id=<?= $item->id ?> value=<?= $item->id ?>>
                <label for=<?= $item->id ?> class="thumb"><img src="<?= $path ?>" alt="" title="<?= $item->path; ?>">
                </label>
            </li>

        <?php }
        ?>
    </ul>
    <?php if ($backup) { ?>
        <input type="checkbox" name="backup" id="backup" title="check to remove BACKUP as well as LOCAL files">
        <label for="backup"></label>
    <?php }

    if ($sub) { ?>
        <ul>
            <li><input type="submit" value="submit"></li>
        </ul>
</form>

<?php } ?>