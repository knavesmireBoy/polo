<?php
$id = $select['identity'];
$tgt = $select['target'];
$options = $select['options'];
$prop = $select['optval'];
$disabled = $select['disabled'] ?? false;
$required = isset($required) ? 'required' : '';
?>
<select title="Select One" id="<?= $id; ?>" name="<?= $id; ?>" <?= $disabled;?> <?= $required ?>>
    <option value="0">Select One</option>
    <?php
    foreach ($options as $opt) :
        $selected = ($opt->id == $tgt) ? 'selected' : '';
    ?>
        <option <?= $selected; ?> value="<?= $opt->id; ?>"><?= $opt->{$prop}; ?></option>
    <?php endforeach; ?>
</select>