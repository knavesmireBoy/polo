
<select title="Select One" id="<?= $id; ?>" name="<?= $selectname; ?>" <?= $disabled;?> <?= $required ?>>
<option value="<?= $default ?>">Select One</option>
    <?php
    foreach ($options as $opt) :
        $selected = ($opt->id == $tgt) ? 'selected' : '';
    ?>
        <option <?= $selected; ?> value="<?= $opt->id; ?>"><?= $opt->{$prop}; ?></option>
    <?php endforeach; ?>
</select>