<?php
include '_process_errors.html.php';
?>
<?php 
echo $error_html;
?>
<h3>Password Recovery</h3>
<p style="text-align: center;">This feature is yet to be properly implemented. When it does I'll be referring to this <a href="https://itnext.io/how-to-implement-password-recovery-securely-in-php-db2275ab3560" class="noajax" target="_blank">article</a> by Nicholas Far.</p>
<!--<p><a href="" class="noajax">Please contact us to change your email address</a></p> -->
<div class="reset"><p style="text-align: center;">Clicking the reset button will re-register you with your existing details but with a with a modified password of <em>&lsquo;qwerty&rsquo;</em> which we would encourage you to change once you log back in. Please provide your email address.</p>
</div>
<form action="<?= USER_RECOVER ?>" method="post" class="<?= $klas ?>">
<fieldset>
  <?php include '_email.html.php'; ?>
    <input type="hidden" id="pk" name="pk" value="<?= $id ?? ''; ?>">
    </fieldset>
        <input type="submit" value="reset">
      </form>
<p class="remplacer"><a href="<?=  BADMINTON ?>" title="Back to Admin">Back to Admin</a></p>