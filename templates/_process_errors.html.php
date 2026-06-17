<?php
$error_html = '';
if (!empty($errors) && is_array($errors)) :
    ob_start();
?>
    <div class="errors">
        <p><?= $msg ?></p>
        <ul>
         
        </ul>
    </div>
<?php
$error_html = ob_get_clean();
endif;
/*
foreach ($errors as $error) :
?>
    <li><?= $error ?></li>
<?php
endforeach; ?>
*/
?>