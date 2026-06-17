<?php
include_once 'funcs.php';
include 'pagehead.html.php';

//user info not available until template loads, extract it to a layout variable
list($user, $output, $klas, $username) = postLogin($output, $klas ?? '', $user ?? '', $username ?? '');
$myheader = !empty($adminpage) && !empty($user) ?  "<h2>You are logged in as <strong>$user</strong></h2>" : "<h2><span>$title</span></h2>";
//$klas = preg_match('/gebruiker/', $_SERVER['REQUEST_URI']) ? 'gebruiker' : $klas;
$id = empty($pageid) ? 'admin' : strtolower($pageid);
$klas = strtolower($klas) . ' wrap';
$myId = $id === 'admin' ? 'id="admin"' : '';
$myId = $myId ? $myId : 'id="' . $id . '"';
?>
<body>
  <div <?= $myId ?> class="<?= trim($klas) ?>">
    <?php include 'pageheader.html.php'; 
    ?>
    <?= $myheader ?>
    <main id="content" role="main" class="override"><?= $output; ?> </main>
    <?php include 'pagefooter.html.php'; ?>
  </div>
</body>
</html>