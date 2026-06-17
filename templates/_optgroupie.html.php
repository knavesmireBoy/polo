<?php
$id = $select['identity'];
$tgt = $select['target'];
$options = $select['options'];
$orphans = $select['orphans'];
$prop = $select['optval'];
$default = $select['default'] ?? '';
?>
<select id="<?= $id; ?>" name="<?= $id; ?>">
    <option value="<?= $default ?>">Select One</option>
    <optgroup label="gallery">
    <?php
    foreach ($options as $opt) :
        $selected = ($opt->id == $tgt) ? 'selected' : '';
    ?>
        <option <?= $selected; ?> value="<?= $opt->id; ?>"><?= $opt->{$prop}; ?></option>
    <?php endforeach; ?>
    </optgroup>
    <optgroup label="archived">
    <?php foreach ($orphans as $opt) : ?>
        <option value="<?= $opt->id; ?>"><?= $opt->{$prop}; ?></option>
    <?php endforeach; ?>
    </optgroup>
</select>
