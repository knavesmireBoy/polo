<?php
include '_email.html.php';
?>
<label for="password">password</label>
<input name="user[password]" id="password" type="password" value="<?= $user->password ?? '' ?>">