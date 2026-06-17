<header>
    <a href="<?= BADMINTON ?>">
        <?php
        if (isset($_COOKIE['mobile']) && !empty($_COOKIE['mobile'])) {
            include '_logo_mobile.svg';
        } else {
            include '_logo_desktop.svg';
        }
        ?>
        <h1>POLOAFRICA!</h1>
        <?php
        if (isset($_COOKIE['mobile']) && !empty($_COOKIE['mobile'])) {
            include '_fancy_mobile.svg';
        } else {
            include '_fancy_desktop.svg';
        }
        ?>
    </a>
  <?php
    include 'pagenav.html.php';
    ?>
</header>