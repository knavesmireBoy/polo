<span class="affirm"><p>Do you really want to move the article: <a href=".">"<?= $title ?>"</a> from <strong><?= $source ?></strong> to <strong><?= $target ?></strong> ?</span></p>

<form class="<?= $submit ?>" action="<?= $action ?>" method="post">
    <input type="submit" value="<?= $submit; ?>">
    <input id="page" type="hidden" name="page" value="<?= $target; ?>">
</form>

<p class="remplacer"><a href="<?= $exit ?>" id="ret" title="back to review">cancel</a></p></div>