<div class="edit_asset">
    <h3>USING THE META-DATA FIELD</h3>
    <p>Because a link will be required to view a pdf, supplying the required portion of article text will achieve this, on the fly, rather than editing the article copy later. E.G. an input of <i>my link to the pdf</i> in the <i>meta_data</i> field will create that link: <a href='.'>my link to the pdf</a>.</p>
    <p>By default ALL matching words/phrases will have the link applied, and if no match can be found the upload will be aborted.</p>
    <p>Where a single word (or a particular phrase) is desirable, to avoid ambiguity, you may supply a bit of context but surround the target copy with a pair of pipe characters:<i> My |link| to the pdf:- </i> My <a href'.'>link</a> to the pdf; <i> My |link to the| pdf:- </i> My <a href'.'>link to the</a> pdf;</p>
    <p>Links can be APPENDED to an existing list by prefacing the text with either a hyphen (<strong>-</strong> for unordered lists) or a (<strong>1.</strong> ordered ) followed by ONE SPACE "- Target Item"; "1. Target Item". To target an existing list item omit the preface.</p>
    <p>Do note that removing a linked file only removes the link tags not the link copy.</p>
    <p>You may leave the field blank if (and only if) using the drop down menu to <strong>replace</strong> the target file using an existing link in the article text (the link reference will be updated). You may of course upload the SAME NAMED file with new link text, or use the <a href="<?= ASSET_ASSIGN . $articleId; ?>">edit asset form</a>, or simply manually edit the article text, which you may have to do if trailing commas et al are included/omitted.</p>
    <div class="remplacer"><a href="<?= $exit; ?>">Exit</a>
    </div>