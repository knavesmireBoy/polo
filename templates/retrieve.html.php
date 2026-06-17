<?php
include_once 'funcs.php';

?>
<h3>Retrieve files from the uploads folder</h3>
<p>Moving or copying files will allow you to reinstate the files into the database; click the filename to <strong>delete</strong> a file.</p>
<form class="filestore" action="<?= $action ?>" method="post" id="retriever">
    <?php
    $i = 1;
    foreach ($group as $item) : ?>
        <div>
        <div><label for='<?= "move$i"; ?>'>move </label><input type="radio" name='data[<?= "$item"; ?>]' id='<?= "move$i"; ?>' value="rename"></div>
            <div><input type="radio" name='data[<?= "$item"; ?>]' id='<?= "cancel$i"; ?>' value="cancel"><label for='<?= "cancel$i"; ?>'>
                <?= $item; ?></label></div>
            <div><input type="radio" name='data[<?= "$item"; ?>]' id='<?= "copy$i"; ?>' value="copy"><label for='<?= "copy$i"; ?>'> copy</label></div>
        </div>
        <?php $i++; ?>
    <?php endforeach; ?>
    <input type="submit" name="submit" value="submit">
</form>
<p class="remplacer"><a href="<?= $exit ?>">exit</a></p>