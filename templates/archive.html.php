<?php
include_once 'funcs.php';
$id = $file->id ?? '';
$name = $file->name ?? '';
$name = empty($name) ? $file->path ?? '' : $name;
$record = $name ? ' the record: ' : ' this record';
$article = isset($assets) && !empty($assets);
//$_actions = ['delete', 'destroy', 'archive', 'replace'];
if (preg_match('/^de/', $perform)) { ?>
  <div class="affirm">
    <p>Are you sure you want to <strong>delete</strong><?= $record; ?><strong><em><?= $name; ?></em></strong>?</p>
    <?php
    if ($perform === 'delete') { ?>
      <p>You may prefer to <a href="<?= $confirm . $id . '/archive' ?>">archive</a> this record for future deployment.</p>
    <?php
    }
    if (empty($replace)) { ?>
      <p>By default related assets will be archived rather than deleted, so they can be potentially deployed in a future article.</p>
  </div>
<?php } else { ?>
  <p>You can also choose to <a href="<?= $replace . $id ?>">replace</a> this record with another.</p>
  </div>
<?php }
  } else { ?>
<div class="affirm">
  <?php if ($perform === 'notfound') { ?>
    <p>As this file cannot be found, we recommend you remove this record from the database, <strong>proceed?</strong></p>
  <?php } else { ?>
    <p>Do you really want to <strong><?= $submit; ?></strong> this record?</p>
  <?php } ?>
  <?php if ($article) { ?>
    <p>An active checkbox will archive any related assets making them available to other articles.</p>
  <?php } ?>
</div>
<?php } ?>
<form class="<?= $submit ?>" action="<?= $action . $id ?? '' ?>" method="post">
  <?php if ($article) { ?>
    <input type="checkbox" name="child" id="child" />
  <?php } ?>
  <input type="submit" value="submit" name="submit">
  <input id="pk" type="hidden" name="pk" value="<?= $id ?? '' ?>">
</form>
<?php

?>
<p class="remplacer"><a href="<?= $exit ?>">exit</a></p>