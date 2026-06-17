<footer>
    <div id="footer_girl">
        <a href="#"><img src="<?= DEV ?>chantal.png"></a>
        <a href="#"><b>tel: </b>(+27) 84 290 0000</a>
        <a href="mailto:info@poloafrica.com"><b>email: </b>info@poloafrica.com</a>
    </div>
    <div id="footer_boy">
        <div> <a href="https://www.instagram.com/poloafrica/" id="InstagramIcon" target="_blank"><img alt="Instagram icon" src="<?= DEV ?>instagram_drop.png"></a> <a href="https://www.facebook.com/Poloafrica-Development-Trust-100069146448363/" id="FacebookBadge" target="_blank"><img alt="Facebook badge" src="<?= DEV ?>facebook.png"></a>
        </div>
        <a href="#"><img src="<?= DEV ?>helgard.png"></a>
    </div>

    <a href="mailto:photos@mwinsight.com">photos by <b>mark ward</b></a>
</footer>
</div>
<?php
$L = count($scripts);
for ($i = 0; $i < $L; $i++) {
    echo '<script src=' . JS . $scripts[$i] . '.js></script>';
}
//
?>