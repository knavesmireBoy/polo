<?php

$_pdf = isset($page) ? '(or a title to a PDF)' : ''; ?>

<div id="upload_guide">
    <header>
        <h1>GUIDE</h1><a title="click to hide guide" href="<?= $exit_guide; ?>" id="exit_guide">X</a>
    </header>
    <?php if (!isset($page)) { ?>
        <label for="guide_box">BOX</label>
        <input id="guide_box" type="checkbox">
        <article>
            <p>Optionally assign the new image to an existing slot. The existing image will be retained in the target folder and can be reassigned to a new slot. Leave blank to assign the image at a later date.</p>
        </article>

    <?php } ?>
    <label for="guide_alt"> <?= $alt ?? 'ALT' ?> </label>
    <input id="guide_alt" type="checkbox">
    <article>
        <p>Assigns an alt description to an image <?= $_pdf ?>. The field can also be used to rename ANY type of file. Separate the alt/title from the file name with a forward slash: <em>a beautiful view/sunset_pic7</em>. The original extension will be applied eg. (targetfolder/sunset_7.jpg)</p>
    </article>
    <?php if (isset($page)) { ?>
        <label for="guide_attr">META_DATA</label><input id="guide_attr" type="checkbox">
        <article>
            <p>This can be used to set an id and/or class on the image, not strictly a <em>content management</em> task, as it infers a knowledge of the <a href="https://developer.mozilla.org/en-US/docs/Web/API/Document_Object_Model" target="_blank">DOM</a>, but - in consultation with your friendly web administrator - it can be used to apply some light styling.</p>
            <div style="text-align: left">Three options: <ol style="text-align: left; color: black">
                <ol><li title="id only"> <a href="" title="id only">id</a>: <em>applies a unique id</em></li>
                    <li>"<a href="" title="class only">.class</a>: <em>applies a single classname</em></li>
                    </li><a href="" title="id AND class">id.class</a>: <em>applies both</em></li>
                </ol>The use of the full stop is key.</div>
            <p>This field is properly useful when handling <a href="<?= ASSET_META . $articleId; ?>">PDF</a> uploads</p>
        </article>
    <?php } ?>
    <label for="guide_ratio">RATIO</label><input id="guide_ratio" type="checkbox">
    <article>
        <p>Numerical input: <strong>n &gt; 1 e.g.(1.5)</strong>  increases the ratio of the <em>larger</em> dimension <strong>n &lt; 1 e.g.(.75) </strong> decreases</p>
        <p> Setting n to 1 results in a square image regardless of orientation; leave at 0 to preserve the current ratio.</p>
        <p>The ratio can also be calculated on-the-fly using a slash or space delimiter eg 16/9 (or 16 9) : 1.777</p>
        <p>To convert the orientation divide 1 by the desired final ratio.</p>
    </article>
    <label for="guide_offset">OFFSET</label><input id="guide_offset" type="checkbox">
    <article>
        <p>Numerical input: Portrait: 0 crops from top, 1 crops from bottom. Landscape: 0 from left, 1 from right. default is .5 (from centre). </p>
        <p>An <strong>alternative</strong> use of this field is to provide an image with a background color if a picture must match a definite ratio and cropping doesn't provide the appropriate solution. The 'padding' is applied evenly on the relevent dimension.</p>
        <p>Provide a <strong>comma</strong> delimited string of numbers eg '222, 49, 99' that correspond to an <strong>RGB</strong> color; <strong>OR one</strong> of the following shortcuts: 'R,G,B,C,M,Y,K,W'</p>
    </article>
    <label for="guide_appearance">APPEARANCE</label> <input id="guide_appearance" type="checkbox">
    <article>
        <p>Numerical input: 100 for top quality, -1 (default) for optimum (about 75)</p>
        <p>Lesser values for resampling down, eg: thumbnails but if issues with tiny images leave at 0</p>
        <p>We can also use this field for ROTATING images, by using a number that represents degrees AFTER the quality numeral, slash OR space delimited:</p><p>eg: 75/270</p>
        <p> (or 50 180)</p>
        <p>To ROTATE ONLY use deg after the desired angle: 90deg</p>
    </article>
    <label for="guide_max">MAX</label><input id="guide_max" type="checkbox">
    <article>
        <p>Numerical input: The absolute dimension (in pixels) of the file's largest dimension for a portrait image that would be the height, for landscape the width</p>
        <p>As with ratio it can be calculated on the fly. For uploaded portrait images the desired max size of the width can be calculated by dividing the width by the ratio.</p>
        <p>You may have to <a href="https://fabricdigital.co.nz/blog/how-to-hard-refresh-your-browser-and-clear-cache" target="_blank" title="link to a page about cache clearing">clear the browser's cache </a>if repeated attempts to acheive the desired size/crop don't appear to deliver the right results.</p>
    </article>

</div>