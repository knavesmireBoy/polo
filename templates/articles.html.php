<?php
include_once 'funcs.php';
$archived = empty($archived) ? '' : ' archived';
list($min, $max) = $minmax;
$addpage = !empty($archived);
$addpage = true;

function myvalidate($arr)
{
  return !empty($arr);
}

if ($archived) : ?>
  <ul class="adminlist">
    <li><a href="<?= ARTICLES_LIST . '-1' ?>">Archived</a></li>
  </ul>
<?php endif;
if (!empty($select['options'])) : ?>
  <form action="<?= ARTICLES_LIST ?>" method="post" id="pageselect" class="autosub">
    <label for="pp">pages:</label>
    <?php
    include_once '_select.html.php';
    ?>
    <input type="hidden" name="pk" value="<?= $select['target'] ?>">
    <input type="submit" value="submit">
  </form>
  <form action="<?= ARTICLES_LIST ?>" id="setrecords" method="post" class="autosub">
    <input name="records" min="<?= $min; ?>" max="<?= $max; ?>" step="1" type="number" id="records" value="<?= $increment; ?>">
    <div>set extent of records to display from <span id="min"><?= $min; ?></span> to <span id="max"><?= $max; ?></span></div>
    <input type="submit" value="submit">
  </form>
<?php endif; ?>
<ul class="adminlist dual <?= $perform ?>">
  <?php
  foreach ($files as $file) :
  ?>
    <li>
      <a href="<?= ARTICLES_EDIT . $file->id ?>" title="click to edit">
        <?= html2($file->title); ?></a>
      <a class="trash" title="<?= $perform ?>" href="<?= $action .  $file->id . '/' . $perform; ?>">delete</a>
    </li>
  <?php endforeach; ?>
</ul>
<?php

if (myvalidate($paginate)) { ?>
  <ul class="pp">
    <?php
    if (isset($prev)) { ?>
      <li>
        <a href="<?= ARTICLES_LIST . $prev ?>">Previous</a>
      </li>
    <?php }

    foreach ($paginate as $p) {
      $txt = $p[0];
      $o = $p[1];
      $klas = $o === $offset ? 'active' : '';
    ?>
      <li class="<?= $klas; ?>">
        <?php
        if ($klas) {  ?>
          <span><?= $txt ?></span>
        <?php
        } else { ?>
          <a href="<?= ARTICLES_LIST . $txt ?>"><?= $txt ?></a>
        <?php
        }
        ?>

      </li>
    <?php }
  }
  if (isset($next)) { ?>
    <li>
      <a href="<?= ARTICLES_LIST . $next ?>">Next</a>
    </li>
  <?php } ?>
  </ul>
  <?php
  ?>
  <div class="remplacer">
    <?php if ($addpage) { ?>
      <a class="add" title="Add Article" href="<?= ARTICLES_EDIT ?>">Add Article</a>
    <?php
    }
    if (isset($_COOKIE['page'])) { ?>
      <a id="clear_page_list" href="<?= ARTICLES_LIST . 'clear' ?>" title="clear page list">Clear</a>
    <?php } else { ?>
      <a href="<?= $exit ?>">Exit</a>
  </div>
<?php }
