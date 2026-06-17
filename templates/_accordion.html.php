<?php
//https://dev.to/whitep4nth3r/how-to-build-an-html-only-accordion-no-javascript-required-4jc4
//old-fashioned way
?>
<input class='read-more-state' id="<?= $article->id ?>"  type='checkbox'>
<label class='read-more-trigger' for="<?= $article->id ?>" ><?= $article->title ?></label>