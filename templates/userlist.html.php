<?php
include_once 'funcs.php';
//$link = $editor ? USER_PERMIT : USER_EDIT;
$permit = $editor->hasPermission(\PoloAfrica\Entity\User::ADMIN);
?>
<ul class="adminlist dual">
  <?php foreach ($files as $file) :
    $klas = $file->id === $target ? 'active' : '';
    $link = $file->id === $target ? USER_EDIT : USER_PERMIT;

  ?>
    <li class="<?= $klas; ?>">
      <a href="<?= $link ?><?= $file->id ?>" title="click to edit"><?= html2($file->name); ?></a>
      <?php if ($permit) { ?>
        <a class="trash" href="<?= $action .  $file->id . '/delete'; ?>">delete</a>
      <?php } ?>
    </li>
  <?php endforeach; ?>
</ul>
<div class="remplacer">
  <a href="<?= $exit ?>">Exit</a>
</div>