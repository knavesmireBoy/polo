<h5>Editing <span><?= ucwords($user->name); ?>'s</span> Permissions</h5>
<ul class="adminlist dual">
  <form action="<?= $action ?? '' ?>" method="post">
    <?php foreach ($permissions as $name => $value) : ?>
      <li>
        <label><?= $name; ?></label>
          <input type="hidden" name="id" value="<?= $user->id; ?>">
          <input name="permissions[]" type="checkbox" value="<?= $value ?>" <?php if ($user->checkPermission($value)) : echo 'checked';
                                                                            endif; ?> >
      </li>
    <?php endforeach; ?>
    <li class="submit">
      <input name="confirm" type="submit" value="submit"></li>
</ul>
</form>
<p class="remplacer"><a href="<?= USER_LIST ?>">exit</a></p>