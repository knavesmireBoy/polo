<a class="pagenav" id="gal_back" href="<?= GAL_PREV_PP . $prevpage ?>"><span></span></a>
<ul id="thumbnails" class="gallery <?= $layout ?>">
    <?php
    $myhref = '';
    $myalt = '';
    $mypath = '';
    foreach ($gallery as $gal) {
        $nobox = !$gal->box;
        $klas = $gal->orient === 'landscape' ? '' : 'portrait';
        $mypath = GALLERY_IMG . $gal->path;
       // $mypath = preg_match('/\./', $mypath) ? $mypath : '';
        $myhref =  GAL_LOAD . $gal->id;
        $myalt = $gal->alt;
        if (!fileExists($mypath)) {
            $klas .= ' missing';
            if ($nobox) {
                $klas .= ' unassigned';
            } ?>
            <li class="<?= $klas ?>"><a href="<?= $myhref ?>"></a></li>

        <?php } else {
        ?>
            <li class="<?= $klas ?>"><a href="<?= $myhref ?>"><img alt="<?= $myalt ?>" src="<?= $mypath ?>"></a></li>
    <?php
        }
    }
    ?>
</ul>

<a class="pagenav" id="gal_forward" href="<?= GAL_NEXT_PP . $nextpage ?>"><span></span></a>