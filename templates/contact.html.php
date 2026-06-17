<?php 
$href = !empty($mailsent) ? ENQUIRIES : '#';
?>

<article id="contactarea" class="alt">
      <h3><a href="<?= $href ?>" id="contact_form">Poloafrica Contact Form</a></h3>
      <!--<form action="http://www.poloafrica.com/cgi-bin/nmsfmpa.pl" id="contactform" method="post" name="contactform">-->
      <?php
      if (!empty($mailnotsent)) { ?>
            <div id="response" class="warning">
                  <?= $mailnotsent; ?></div>
      <?php
      } elseif (!empty($mailsent)) {
            //setcookie('submit', 'success', 0, '/');
            include 'success.html.php';
      } else { 
           //dump($klas);
            ?>
            <div class="<?= $klas ?>">
                  <form novalidate action="enquiries" id="poloafricacontactform" method="post">
                        <fieldset>
                              <legend><?= $fieldset ?? 'Required fields are highlighted' ?></legend>
                              <p id="web"><label for="url"></label><input id="url" name="url"></p>
                              <label title="required field" for="name">name</label><input id="name" name="details[name]" tabindex="1" required="" maxlength="40" value="<?= $name ?? '' ?>"><label for="phone">phone</label><input id="phone" name="details[phone]" tabindex="2" type="tel" pattern="\d{4,}\s?\d{4,}" maxlength="20" value="<?= $phone ?? '' ?>"><label for="email" title="required field">email</label><input id="email" name="details[email]" tabindex="3" required="" type="email" maxlength="254" value="<?= $myemail ?? '' ?>"><label for="addr1">address</label><input id="addr1" name="details[addr1]" tabindex="4" maxlength="40" value="<?= $addr1 ?? '' ?>"><label for="addr2">address</label><input id="addr2" name="details[addr2]" tabindex="5" maxlength="40" value="<?= $addr2 ?? '' ?>"><label for="addr3">address</label><input id="addr3" name="details[addr3]" tabindex="6" maxlength="40" value="<?= $addr3 ?? '' ?>"><label for="addr4">address</label><input id="addr4" name="details[addr4]" tabindex="7" maxlength="40" value="<?= $addr4 ?? '' ?>"><label for="country">country</label><input id="country" name="details[country]" tabindex="8" maxlength="30" value="<?= $country ?? '' ?>"><label for="postcode">postcode</label><input id="postcode" name="details[postcode]" tabindex="9" maxlength="10" value="<?= $postcode ?? '' ?>">
                        </fieldset>
                        <fieldset>
                              <textarea id="comments" name="details[comments]" tabindex="9" cols=50><?= $mycomments ?? '' ?></textarea>
                              <input alt="submit" id="dogs" name="dogs" src="<?= ASSETS . 'enquiries/dogsform.png'; ?>" tabindex="10" type="image" alt="Submit">
                        </fieldset>
                        <input type="submit" value="submit">
                  </form>
                  <figure><img alt="cat" src="<?= ASSETS . 'enquiries/cat.jpg'; ?>" id="cat"></figure>
            </div>

      <?php } ?>
</article>