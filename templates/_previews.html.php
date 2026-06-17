<div class="previews">
    <?php
    foreach ($files as $k => $file) {
        $path = $file->path;
        $pathtofile = scanMultiDir([ASSETS, IMAGES, VIDEOS, GALLERY, RESOURCES], $path);

        if (preg_match('/\.pdf$/', $path)) {
            if ($assetId && $assetId == $file->id || !$assetId) {
                $pdfs[] = ['path' => $pathtofile, 'id' => $file->id];
            }
            continue;
        }

        if (validate_extension($path, VIDEO_EXT)) {
            $poster = preparePoster($path, 'jpg');
            $page = queryPath($pathtofile, 'parent');
            $videodata = prepareVideo($path, $page);
            include '_video.html.php';
            continue;
        }
        $path =  $file->getStatus(true);
       include '_previewimage.html.php';
    }
    if (!empty($pdfs)) : ?>
        <div class="pdf">
            <?php
            foreach ($pdfs as $pdf) :
                include '_previewpdf.html.php';
            endforeach;  ?>
        </div>
    <?php endif; ?>

</div>