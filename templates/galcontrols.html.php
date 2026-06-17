
<p id="enable-js" style="text-align: center;">Javascript needs to be enabled to AUTOPLAY the slideshow, but you can use the forward and back buttons to view the static images.</p>

<a id="exit" href="<?= GAL_LIST ?>"></a>
<div id="hollywood"  class="<?= $klas ?>">
    <a id="base" href="<?= GALLERY_IMG; ?><?= $img->path?>"><img src="<?=  GALLERY_IMG; ?><?= $img->path ?>" alt="<?= $img->alt ?>"></a>
</div>
<div id="controls" class="static">
    <form action="<?= GAL_PREV . $img->id ?>" class="button">
        <button id="backbutton"></button>
    </form>
    <form action="<?= GAL_NEXT . $img->id ?>" id="play" class="button">
    <input type="hidden" name="paths" value="<?= $paths ?>" >
        <button id="playbutton"></button>
    </form>
    <form action="<?= GAL_NEXT . $img->id ?>" class="button">
        <button id="forwardbutton"></button>
    </form>
</div>