<?php
$options = $select['options'];
$id = $select['identity'];
$tgt = $select['target'];
$prop = $select['optval'];
$default = $select['default'] ?? '';
$required = isset($required) ? 'required' : '';
$disabled = $select['disabled'] ?? false;

if (!isset($selectname)) {
    $selectname = $id;
}
if (strtoupper($id) === $id) {
    $id = strtolower($id);
    $selectname = "data[$id]";
}

if (!empty($options)) : ?>
    <select id="<?= $id; ?>" name="<?= $selectname; ?>" <?= $required ?> <?= $disabled ?>>
        <option value="<?= $default ?>">Select One</option>
        <?php
        foreach ($options as $opt) :
            $selected = ($opt['id'] == $tgt) ? 'selected' : '';
        ?>
            <option <?= $selected; ?> value="<?= $opt['id']; ?>"><?= strtolower($opt[$prop]); ?></option>
        <?php endforeach; ?>
    </select>
<?php endif; ?>