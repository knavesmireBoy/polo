<?php
//dump(get_defined_vars());
?>
<h3>ASSET LIST</h3>
<ul class="adminlist dual asset">
  <?php
  include_once 'funcs.php';

  $pdfs = [];

  if (isset($files[0]->id)) {
    foreach ($files as $file) :
  ?>
      <?php
      $pdf = false;
      $_klas = 'thumb';
      $path = $file->getStatus(true);
      $isimage = true;
      //https://stackoverflow.com/questions/10881678/play-a-video-tag-inside-an-img-tag
      $video = in_array(strtolower(strrchr($file->path, '.')), VIDEO_EXT);
      if ($video) {
        $poster = preparePoster($file->path, 'jpg');
        $videodata = prepareVideo($file->path, $page);
        $path = VIDEOS . $page . "/$file->path";
        $isimage = false;
      }
      if (preg_match('/\.pdf$/', $file->path)) {
        if (isset($page)) {
          $path = trim(scanMultiDir([ASSETS, RESOURCES], $file->path));
        }
        $pdfs[] = ['path' => $path, 'id' => $file->id];
        continue;
      }
      if (fileExists($path)) {

        if ($isimage) {
      ?>
          <li><a class="<?= $_klas ?>" href="<?= $routes['edit'] . $file->id ?>" title="<?= $path ?>"><img src="<?= $path; ?>"></a>
            <a class="trash" title="delete" href="<?= $routes['action'] .  $file->id . '/delete'; ?>">delete</a>
          </li>
        <?php
        } else { ?>
          <li><a class="<?= $_klas ?>" href="<?= $routes['edit'] . $file->id ?>" title="<?= $path ?>">
              <?php
              include '_video.html.php';
              ?>
            </a>
            <a class="trash" title="delete" href="<?= $routes['action'] .  $file->id . '/delete'; ?>">delete</a>
          </li>
        <?php
        }
      } else {
        if ($isimage || $video) { ?>
          <li class="notfound">
            <a class="<?= $_klas ?>" href="<?= ASSET_EDIT ?><?= $file->id ?>" title="<?= $path; ?>"><img src="<?= FILENOTFOUND ?>"></a>
            <a class="trash" title="delete from database" href="<?= $routes['action'] .  $file->id . '/delete'; ?>">delete</a>
          </li>
  <?php  }
      }
    endforeach;
    //aggregate output of pdf files
    //MAY have to do the same with videos but unlikely

    if (!empty($pdfs)) {
      include '_pdfasset.html.php';
    }
  }
  ?>
</ul>
<p class="remplacer">
<a href="<?= $routes['manage'] . '/' . $file->id; ?>" class="archived" title="manage archived files">Manage</a>
  <a class="add" href="<?= $routes['add'] ?>">Add Asset</a>
  <a href="<?= $exit ?>">Exit</a>
</p>