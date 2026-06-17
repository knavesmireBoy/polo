<div class="edit_asset backup">
  <h3>Backup Files</h3>

  <?php
  if ($op): ?>
    <p>Files succesfully retrieved, click <a href="<?= $dir ?>">here</a> for a full list of the current untracked files</p>

  <?php else: ?>

    <p>These are the currently untracked files, they exist in the target folders and can be reinstated to the database by clicking the file which will enter the name in the <strong>path</strong> field of the <strong><em>edit asset form</em></strong>. Enter the <strong><em>article title</em></strong> in the <strong>assign</strong> field to assign the file to that article. You can re-upload the file if you wish to make any modifications, right/control click in the browser to save the file locally then use the upload form to perform the desired modifications; ie crop/rename/resize etc..</p>
    <p>To <strong><em>delete</em></strong> a file enter the <strong>file name</strong> in the assign field instead of the article title.</p>
  <?php endif; ?>
  <div class="transit">
    <?php
    foreach ($files as $f) :
      $isImage = doWhen('identity', partial('findMatch', '/image/'));
      $path = fileExists($f);
      $mime = doWhen('fileExists', 'getMimeType');
      $type = $mime($path);
      if (!$type) {
        break;
      }
      $image = $isImage($type);
      $kls = '';
      $getBasename = composer('strtolower', doWhen('identity', 'basename'));
      if (!$image && $path) {
        $t = $getBasename($path);
        $f = PDF_FILE;
        $kls = 'pdf';
      } else {
        $t = $getBasename($f);
      }
      $route = preg_match('/gallery/', $path) ? ASSET_ADD . "-1//$t" : ASSET_ADD . "///$t";

    ?>
      <figure class="<?= $kls; ?>">
        <a title="<?= $f; ?>" href="<?= $route; ?>"><img src=<?= $f; ?> /></a>
        <figcaption><?= $t; ?></figcaption>
      </figure>
    <?php
    endforeach;
    ?>
  </div>
</div>
<p class="remplacer"><a href="<?= $exit ?>">exit</a></p>