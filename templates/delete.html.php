<?php
include_once 'funcs.php';
$id = $file->id ?? 0;

if (isset($perform)) {
  if ($perform === 'delete') { ?>
    <div class="affirm">
      <p>Are you sure you want to <strong>delete</strong> this record?</p>
    </div>
  <?php } else if (isset($para)) {
    echo $para;
  }
  ?>
  <form class="delete" action="<?= $action ?>" method="post">
    <input type="submit" value="cancel" name="cancel">
    <input type="submit" value="submit" name="submit">
    <input id="pk" type="hidden" name="pk" value="<?= $id ?>">
  </form>
<?php } ?>
<p class="remplacer"><a href="<?= $exit ?>">exit</a></p>