<?php
/*Don’t 1: Don’t store the plaintext passwords in the database
Don’t 2: Don’t use public information as a password recovery token
Don’t 3: Don’t use sequential id numbers as password recovery tokens.
Don’t 4: Don’t make your security depend on the fact that your code is secret. It won’t be.
Don’t 5: Don’t generate tokens in a way that can also be generated offline by someone with knowledge of the system
Don’t 6: Don’t use encryption if you can avoid it. It causes more problems that it solves. And you probably don’t know how to implement it securely anyway
Don’t 7: Don’t generate your tokens based on time, they are guessable.
Don’t 8: Don’t use rand, mt_rand or lcg_value as a random number source for anything security related.

Do 1: Generate tokens that don’t depend on the user data.
Do 2: Use random_int or random_bytes for secure random numbers.
Do 3: Set a lifetime for your reset tokens, the shorter the better. 1hr is probably a sensible default.
Do 4: Discard the reset tokens after use.
*/

include '_process_errors.html.php';

?>

<h3><?= $title; ?></h3>
<?php
/*neu is the default type eg: email as opposed to old : oldemail
was going to just use $type but we want the input type of password to be plain text
when changing passwords for obvious reasons so $type is email or text
*/
if (!empty($error_html)) {
    echo $error_html;
} else { ?>
    <p><em>Changing your <?= $neu; ?> will require you to login again.</em>
        <?php
        if ($neu === 'email') { ?>
            Change your <a href="<?= USER_PWD ?><?= $id; ?>">password</a>.
        <?php } else { ?>
            Change your <a href="<?= USER_MAIL ?><?= $id; ?>">email address</a>.
    <?php }
    } ?>
    </p>
    <form action="<?= $action; ?><?= $id; ?>" method="post" class="edit details">
        <fieldset>
            <label for="<?= $old ?>">old</label>
            <input name="user[<?= $old ?>]" id="<?= $old ?>" type="<?= $type ?>" value="<?= $email ?? ''; ?>">
            <label for="<?= $neu ?>">new</label>
            <input name="user[<?= $neu ?>]" id="<?= $neu ?>" type="<?= $type ?>">
            <input name="user[pk]" type="hidden" value="<?= $id ?>">
        </fieldset>
        <input type="submit" name="submit" value="<?= $submit; ?>">
    </form>
    <p></p>
    <div class="remplacer"><a href="<?= USER_EDIT . $id; ?>">exit</a></div>