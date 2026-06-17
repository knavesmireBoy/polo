<?php
/*
it's a bit of a doozy
used for registering, logging in and editing
*/
include_once 'funcs.php';
$registered = !empty($userid);
$imemine = $owner ? 'Edit My Permissions' : 'Edit User Permissions';
$loginstatus = trim2lower($submit) === 'login';
$_replace = false;

if (!$registered) { ?>
    <h3>Please <?= $route ?></h3>
    <?php echo $error_html ?? ''; ?>
<?php } else {
    //merely class for form
    $route = 'registered';
?>
    <?php
    if (!empty($error_html)) {
        echo $error_html;
    } else {

       if (!empty($permissions)) { ?>
            <a href="<?= USER_PERMIT ?><?= $userid ?>"><?= $imemine ?></a>
<?php }
    }
}
if ($owner): ?>
    <p style="text-align: center;">You may edit the username below, or change your <a href="<?= USER_PWD ?><?= $userid; ?>">password</a> or <a href="<?= USER_MAIL ?><?= $userid; ?>">email address</a>.</p>
    <?php
    endif;

if ($registered && $owner || !$registered) { 
    if($owner){
        $route = appendClassName($route, 'noajax');
    }
    ?>
<form action="<?= $action; ?><?= $userid; ?>" method="post" class="edit <?= $route ?>">
<fieldset>
    <?php
    //show name for editing/registering
    if ($route === 'register' || $registered ) {
        include '_username.html.php';
    }
    //show email/pwd for logging in
    if (!$registered) {
        include '_emailpwd.html.php';
    }
    ?>
    </fieldset>
    <input type="submit" name="submit" value="<?= strtolower($submit); ?>">
</form>
<?php }

if ($registered && !empty($permissions)) {
    if (!$loginstatus) { 
        $_replace = true;
        ?>
        <div class="remplacer"><a href="<?= USER_LIST ?>">exit</a></div>
    <?php
    }
} else if (!$registered && $loginstatus) { ?>
    <!--<p style="text-align: center;">You can register <a href="<?= USER_REG ?>">here</a>.</p>
    <p style="text-align: center;">Please <a href="<?= USER_RECOVER ?>" class="noajax">contact us</a> if you have forgotten your password.</p>-->
<?php
} if(!$_replace){ ?>
<p class="remplacer"><a href="<?= BADMINTON ?>">exit</a></p>
<?php
}

