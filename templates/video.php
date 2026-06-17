<?php
$cur = 'post' . $count;
$vpm;
$head = null;
if($count == 7){ ?>
<section id="tvcoverage">
<input class="read-more-state" id="<?php echo $cur?>" type="checkbox">
<label class="read-more-trigger" for="<?php echo $cur?>"></label>
    <?php 
    $head = '<h3><a href="#" id="TV">TV and video coverage</a></h3>';      
               }
$video = $article->getFilePath()[0];
    if($video) { ?>
    <article id="<?php htmlout($video['dom_id']); ?>">
        <?php if(isset($head)){  echo $head; } ?>
    <video width="320" height="180" controls auto preload="metadata" poster="../video/medley/<?php echo $poster; ?>.jpg">
    <source src="<?php htmlout($video['src']) ?>" type='video/mp4; codecs="avc1.42E01E, mp4a.40.2"'>
        <source src="<?php htmlout(substr($video['src'], -3) . 'webmhd.webm') ?>" type='video/webm; codecs="vp8, vorbis"'>
        <source src="<?php htmlout(substr($video['src'], -3) . 'oggtheora.ogv') ?>" type='video/ogg; codecs="theora, vorbis"'>
        </video>
    <?php }
echo $article->mdcontent; ?>
</article>
    <?php
    if($count == 9){ ?>
    </section>
<?php }