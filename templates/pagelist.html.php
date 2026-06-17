<?php include_once 'funcs.php'; ?>

<ul class="adminlist dual <?= $perform ?>">
  <?php
  foreach ($files as $file) :
  ?>
    <li>
      <a href="<?= PAGES_EDIT . $file->id ?>" title="click to edit">
        <?= html2($file->title); ?></a>
      <a class="trash" title="<?= $perform ?>" href="<?= $action . $file->id; ?>">delete</a>
    </li>
  <?php endforeach; ?>
</ul>

<div class="remplacer">
  <a class="add" title="Add Page" href="<?= PAGES_ADD ?>">Add Page</a>
  <a href="<?= $exit ?>">Exit</a>
</div>