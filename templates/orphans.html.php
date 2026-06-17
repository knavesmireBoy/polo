<?php include_once 'funcs.php';

$pdfs = [];
$videos = [];
$id = intval($articleId); //empty string is less than zero

$untrackedpath = $id < 0 ? "/gallery/getuntracked/$id" : "/asset/getuntracked/$id";
$untrackedtext1 = $id < 0 ? 'gallery' : 'article';
$untrackedtext2 = $id < 0 ? 'a slot.' : 'any articles.';
$route = $id < 0 ? "/gallery/retrieve/$id" : "/asset/retrieve/$id";
$archivedtext = "Listed below are the current ARCHIVED files. The files are referenced in the DATABASE but are not assigned to $untrackedtext2";
$unarchivedtext = "There are no currently archived files to view.";
include '_picvalidation.html.php';
$head = isset($message) ? $message : "Manage Archived Files";

if (isset($allpaths)) {
    $pdfs = safeFilter($allpaths, partial('preg_match', "/\.pdf$/"));
    $videos = safeFilter($allpaths, partial('preg_match', "/\.mp4$/"));
    $images = arrayDiff($allpaths, $pdfs, $videos);
}

$backup1 = "<li>Checking the <strong>purple trash can</strong> box will include any corresponding - same named - files that may still reside in the initial <strong>upload directory</strong> as well as the active public directory</li>";
$backup2 = "<li>Select the <strong>purple trash can ONLY</strong> to clear the <strong>upload directory.</strong></li>";
$backup = (isset($super) && !empty($filestore)) ? "$backup1$backup2" : '';

$_incfile = '_orphans.html.php';
?>
<h3><?= $head; ?></h3>
<ul class="manage">
    <?php
    if ((!empty($untracked) || !empty($filestore)) && isset($super)) {
        if (!empty($filestore)) : ?>
            <li>Click <a title="retrieve files from uploads directory" href="<?= $route; ?>"><strong>here</strong></a> to copy/move files from the <strong>upload directory</strong> to an active public directory for reinstating</li>
        <?php
        endif;
        if (!empty($untracked)) :
        ?>
            <li>View all <a title="retrieved files from uploads directory" href="<?= $untrackedpath; ?>"><strong>untracked <?= $untrackedtext1; ?> </strong></a>files.</li>
</ul>
<?php
        endif;

    }
    if (isset($action)) {
        if (!empty($group)) { ?>
    <li>Click on the file to select for <strong>removal</strong></li>
    <?php if (count($group) > 1) : ?>
        <li>Check the <strong>blue trash can</strong> box to select all files</li>
        <li>Checking the blue trash can <strong>AND THEN</strong> selecting individual files will PRESERVE ONLY those files</li>
    <?php endif; ?>
    <?= $backup; ?>
    </ul>
    <p><?= $archivedtext; ?></p>
<?php
        } else if (empty($group)) { ?>
    <p><?= $unarchivedtext; ?></p>

<?php
//$_incfile = null;
        } else if (!empty($filestore) && isset($super)) { ?>
    <li>Select the <strong>backup box</strong> to clear the <strong>upload directory.</strong></li>
<?php }
        if($_incfile) include $_incfile;
    }
?>
<p class="remplacer">
    <?php if (empty($exit)) { ?>
        <a href="<?= BADMINTON ?>">Back to Admin</a>
    <?php } else { ?>
        <a href="<?= $exit['href'] ?>"><?= $exit['txt'] ?></a>
    <?php } ?>
</p>

<?php  ?>