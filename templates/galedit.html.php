<?php
include_once 'funcs.php';
$myhref = '';
$mypath = '';
$orient = '';
$mytitle = 0;
$myalt = '';
$max = count($gallery);

?>

<h3>Select pic to edit</h3>
<?php if (!empty($msg)) {  ?>
    <p><?= urldecode($msg) ?></p>
<?php } ?>
<p>Note that the images are arranged in a convenient grid that does not correspond exactly to the order they appear in the public facing gallery. The numbers refer to the position in the public page.</p>
<ul id="gal_edit">
    <?php
    foreach ($gallery as $gal) :
        $orient = $gal->orient && $gal->orient === 'portrait' ? 'portrait' : '';
        $nobox = !$gal->box;
        $myhref = GAL_EDIT . $gal->id;
        //$myhref = $gal->id ? GAL_ASSIGN . 0 . "/$gal->box" : '' (1);
        $mypath = $gal->path ? GALLERY_IMG . $gal->path : '';
        $myid = $gal->id;
        $myalt = $gal->alt;
        $mytitle = $gal->box ?? $gal->id; //display BOX id on hover
        if (fileExists($mypath)) {
            $mypath = GALLERY_THUMBS . $gal->path;
    ?>
            <li class="<?= $orient ?>"><a href="<?= $myhref ?>"><figure><img alt="<?= $myalt ?>" title="<?= $mypath ?>" src="<?= $mypath ?>"><figcaption><?= $mytitle; ?></figcaption></figure></a></li>
            <?php } else {
            $myhref = GAL_EDIT . "0/$gal->id";
            if (!$nobox) {
            ?>
                <li class="missing <?= $orient ?>">
                    <a title="<?= $mytitle ?>" href="<?= $myhref ?>">
                    </a>
                </li>
            <?php
            } else { ?>
                <li class="missing unassigned <?= $orient ?>"><a title="<?= $mytitle ?>" href="<?= $myhref ?>"></a></li>
    <?php
            }
        }
    endforeach;
    ?>
</ul>
<p class="remplacer"><a href="/gallery/upload" id="upload_link" title="upload a pic">upload new pic</a><a href="<?= BADMINTON ?>" id="ret" title="back to admin">exit</a></p>

<?php
/*(1) I used this to skip the dropdown version of the edit image form and straight to the freetext version when I had cleared the gallery database table by mistake and needed to input the path to the file*/