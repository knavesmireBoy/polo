<?php

//$email = 'fred';
//$mycomments = 'This cuts out 25 minutes of the journey from Natal or Joburg. This top road is explained in the directions. The GPS coordinates for the farm are lat -28.768, lon 28.008 (28 46 05S, 28 00 30E)';
?>

<div id="response">
    <figure class="dogs bottom"><img alt="" src="<?= ASSETS . 'enquiries/dog_gone.jpg';?>"></figure>
    <figure class="dogs top"><img alt="" src="<?= ASSETS . 'enquiries/016.jpg';?>"></figure>
    <div>
        <h1>Thankyou for your enquiry</h1>
        <p>An email has been sent to <a href="mailto:<?php htmlout($myemail); ?>"><?php htmlout($email); ?></a></p>
        <p><strong>Here is your message:</strong></p>
        <p class="msg"><?php htmlout($mycomments); ?></p>
    </div>
    <figure class="bottom cat"><img alt="cat" src="<?= ASSETS . 'enquiries/cat_real_gone.jpg';?>"></figure>
    <figure class="top cat"><img alt="cat" src="<?= ASSETS . 'enquiries/cat_gone.jpg';?>"></figure>
</div>