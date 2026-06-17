<?php
$text = isset($submitted) && $submitted ? ' are allowed to edit those details' : ' have access to the page you requested';
$editxt='.</p>';
?>
<p style="text-align: center;">Only <strong><?= $accesslevel ?></strong><?= $text;
if ($str === 'user' && isset($id)) {
    $editxt=', but you may edit your own <a href="' . USER_EDIT .  $id . '">details</a>.</p>';
}
echo $editxt;
/* USER_DENIED:
access is a bridge function to call user::admin but the path user/accces won't load js
we ONLY want this if the users next action was to log out 
*/
?>
<div class="remplacer"><a href="<?= USER_DENIED ?>" class="noajax">exit</a></div>