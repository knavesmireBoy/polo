<?php
$sid = $slotid ?? 0;
/*! gallery::upload expects EITHER a numerical or boolean as 2nd arg. true implies post upload success (reload) we don't want that here as we have an orientation failure and we need to send the slotid as the 1st arg and something that doesn't resolve to a boolean as the 2nd; we're sending the ratio required to correct the orientation issue
*/
$up1 = "<a href='gallery/upload/$sid/0.666'>UPLOADING</a> the file with the ratio set to approximately 0.666 <em>will</em> produce a file with the correct ratio, albeit with some serious cropping.";
$up2 = 'You can <strong>RE-UPLOAD</strong> the file with the ratio set to approximately 0.666<br>Some experimentation may be required and you may also need to adjust the offset<br>See the <strong>GUIDE</strong> for details';
$up = '';
if (isset($routes)) {
    $up = $routes['route'] === 'upload' ? $up2 : $up1;
    $up = $routes['route'] === 'edit' ? '' : $up;
}
$myaccept = 'Upload failed. The file type may not be acceptable.';
$types = ['webp'];
$arg = '';
$maxmb = preg_replace('/M$/', 'mb', ini_get('post_max_size'));

$k = explode('_', $key);
if (isset($k[1])) {
    $key = $k[0];
    $arg = urldecode($k[1]);
}

$lookup = [
    'accept' => 'Upload failed. The file type may not be acceptable.',
    'access' =>  'An error occurred while uploading the file to the destination directory. Ensure that the web server has access to write in the path directory.',
    'allowed' => 'Additional assets are not allowed in this section. Please contact the System Administrator.',
    'article' => "The selected file cannot be uploaded as it is use by another article.",
    'articlenull' => "Asset must be assigned to an article.",
    'articleother' => "The asset details cannot be edited as they are in use by another article, please select the correct asset for editing",
    'pdfother' => "The article content has a path to an asset that does not belong to this article",
    'articleself' => "The asset details cannot be edited as they are already in use by this article, please select the correct asset for editing",
    'headsup' => "The article title does not match the heading; submit again to accept or correct",
    'fetch' => "Unable to find the requested article, please check the spelling of the title.",
    'asset' => "Please select an asset from the dropdown menu",
    'assetfree' => "You have been redirected to the upload form as the article has no assets to edit",
    'assetfreearchive' => "The article has no assets to edit but one can be assigned either using the dropdown menu or the upload form",
    'attack' => "Possible file upload attack",
    'attrid' => "The meta_data field must be empty when replacing a link to a pdf; link text can be updated after replacement",
    'baddpage' => "That page name is already taken!",
    'choose' => "Did you forget to choose a file for upload?",
    'copy' =>  'The file uploaded to the remote location but failed to copy to the destination directory. Ensure that the web server has access to write in the path directory.',
    'exist' => "The file does not exist in the target folder - please check the spelling",
    'exceeds' => "You attempted to upload a file of <span>$arg</span> which exceeds the maximum upload limit of <span>$maxmb</span>",
    'existed' => "The file does not exist in the target folder; the reference to it has been removed from the database.",
    'ext' => "Asset cannot be assigned as the file is the wrong mimetype",
    'exts' => "Asset cannot be assigned as no replacement candidates exist for this mime type",
    'empty' => 'Article has no content',
    'heading' => 'Article must have a heading; also ensure link/label content is all lowercase',
    'inline' => "No file can be found with the path provided",
    'inlineyes' => "The path provided cannot be used as the file it refers to is deployed in an article",
    'inlineno' => "The path provided cannot be used as the file it refers to is archived not untracked",
    'insert' => "The operation would breach the image limit for this article, you may replace an existing image",
    'filestory' => "The upload directory is empty",
    'freetext' => "Only senior managmenent have access to that page",
    'landscape' => "You are attempting to load a landscape image into a portrait slot<br> $up",
    'missing' => "File could not be uploaded",
    'name' => 'You are attempting to insert a record that already exists in the database.</br>To UPDATE the record please enter a number into the box field.',
    'orphan' => 'A file with this name exists in the target folder. To preserve that file rename your upload file or select a candidate for replacing from the dropdown menu',
    'order' => 'Move not allowed',
    'overwrite' => "The chosen file would overwite an archived file, to retain the old file rename the incoming file, otherwise submit again",
    'pdf' => 'Please provide qualifying link copy in the <em>meta_data</em> field',
    'pdfuntrack' => 'Upload aborted: pdf files cannot be untracked',
    'phrase' => 'The copy provided is in use by another link, please edit manually',
    'pukka' => 'Please provide the existing article copy in the meta_data field',
    'portrait' => "You are attempting to load a portrait image into a landscape slot<br>$up",
    'prelink' => "Aborted upload as at least some of the supplied copy is in use by an exisiting link",
    'postlink' => "Aborted update as part of the supplied copy is already in use by this exisiting link. Please edit article copy directly.",
    'ratio' => "The orientation is correct but the only permitted ratio must round to 1.5 ",
    'doppel' => "Multiple occurences of the supplied text exists within the article, refine the selection by providing context, or submit again to apply the link to all occurences.",
    'reloaded' => "Your file was successfully uploaded.",
    'replace' => 'Only currently archived files are replacement candidates. Use the form to edit or assign as normal.',
    'sibling' => 'Upload aborted. Cannot replace an asset with a active asset.',
    'untracked' => "No unreferenced files found",
    'referenced' => "Failed attempt to delete the file: '$arg'; referenced in this article",
    'linkcount' => "Content Reverted: There was a discrepancy between the number of active links and the corresponding references",
    'linkindex' => "Content Reverted: There was a mismatch between the link index and their references",
    'linkcontent' => "Content Reverted: link must match either: #; http(s)://; path/to/file.ext",
    'headlink' => "Content Reverted: the link in the header may only start with a #",
    'headformat' => "Content Reverted: heading needs to be formatted as a hyperlink",
    'nolinkindex' => 'Content Reverted: There was a missing link index',
    'noendlink' => 'Content Reverted: A link had no end bracket',
    'noendindex' => 'Content Reverted: An index had no end bracket',
    'nostartindex' => 'Content Reverted: An index had no start bracket',
    'nostartlink' => 'Content Reverted: An link had no start bracket',
    'noendparanth' => 'Content Reverted: An inline link is missing the end bracket',
    'noreflinkend' => 'Content Reverted: A reference link is missing the end bracket',
    'noreflinkstart' => 'Content Reverted: An reference link is missing the start bracket',
    'noimagestartlink' => 'Content Reverted: An file link is missing the start bracket',
    'linktitle' => "Expecting a title attribute OR an attribute block.",
    'missingrefindex' => 'Content Reverted: An reference link is missing an index',
    'para' => 'Content Reverted: mismatched parentheses',
    'bracket' => 'Content Reverted: mismatched square brackets',
    'brace' => 'Content Reverted: mismatched curly brackets',
    'quote' => 'Content Reverted: mismatched quotation marks',
    'doublebrace' => 'Content Reverted: excess curly brackets',
    'doublebracket' => 'Content Reverted: excess square brackets',
    'doublecolon' => 'Content Reverted: excess colons',
    'doublesemicolon' => 'Content Reverted: excess semicolons',
    'doubleequals' => 'Content Reverted: excess equal signs',
    'doublehyphens' => 'Content Reverted: excess hyphens',
    'doublecommas' => 'Content Reverted: excess commas',
    'doubleperiod' => 'Content Reverted: excess periods',
    'attr' => 'Content Reverted: stray characters within an inline link',
    'postattr' => 'Content Reverted: no characters should precede inline attribute blocks',
    'label' => 'Content Reverted: no characters allowed here',
    'label2' => 'Content Reverted: no characters allowed here innit',
    'mismatch' => 'There is a mismatch in your local formatting',
    'tagmatch' => 'Some start and end tags do not match',
    'spanspace' => 'Unnecessary trailing or leading spaces between start and end tags',
    'attrspace' => 'Unnecessary trailing or leading spaces within tag',
    'illegal' => 'Illegal characters within tag',
    'endtag' => 'Some inline formatted text is bookended by two start tags',
    'badendtag' => 'Illegal end tag',
    'starttag' => 'Some inline formatted text is bookended by two end tags',
    'swaptag' => 'Some inline formatted text has the wrong tags at either end',
    'suspect' => 'The article has less words than nonwords!?',
    'nonword' => 'Consecutive non word characters',
    'superbold' => 'Bold/Italic formatting cannot exceed three consecutive asterisks',
    'brokentag' => 'Incomplete inline tag',
    'nosuchtag' => 'Unrecognised inline tag',
    'maxword' => 'An article is limited to 400 words',
    'minword' => 'An article must have at least ten words',
    'bigword' => 'More than one occurence of a very long word',
    'smallword' => 'An unusual proponderance of very small words',
    'repeat' => 'A word has more than three or more consecutive characters, not allowed in English.',
    'dollar' => 'Numbers limited to nine digits',
    'dirty' => 'Content Reverted: Suspect copy discovered',
    'linebreak' => 'Links cannot contain line breaks',
    'wordcount' => 'Links copy exceeds seven words',
    'bracecontent' => 'An attribute block has invalid content',
    'nofloat' => 'Unrecognised value for target attribute, or an extraneous float attribute',
    'noattrs' => 'an attribute block is not allowed on an image nested in a link',
    'onlyfloat' => 'only the float values of left, right or none allowed',
    'dofloat' => 'a linked image requires a float value (left|right|none)',
    'mdimageformat' => 'Bang: suspected missing !; prefix link to indicate ![an image][1]',
    'notsrc' => 'path is a form of hyperlink, or a path to a pdf, but should be a path to an image',
    'nothref' => 'path is to a file and should be some form of hyperlink',
    'notimg' => 'The path content is not an image',
    'multitype' => "A reference label is being used in two different contexts",
    'bond' => 'Article editing restricted to the bond pages',
    'indexnum' => 'The label of a reference link is limited to 2 digits',
    'indexlimit' => 'The label of a reference link is limited to 12 characters',
    'spandigits' => 'No digits allowed',
    'star' => 'Improper use of formatting token',
    'span' => 'Inline formatting should be applied to whole words only',
    'emptytag' => 'Empty tag',
    'unlink' => 'The current link refers to a pdf file, delete or archive the file to clear the link',
    'reinstated' => 'The article contains a vaild path that was not recorded in the database, this has been rectified, please use the edit asset form to perform any edits.',
    'uniq' => 'The article has redundant references to the same file, use a common label and reference style links',
    'missingref' => 'The article has active pdf files that have no reference in the article copy, either archive unwanted files or correct',
];
/*
$vids = [
    'naledi' => 'naledi_nkopane.jpg',
    'news24' => 'poloafricadevelopmenttrust.jpg',
    'sport1' => 'poloafricasüdafrika.jpg',
];
*/
if (in_array($key, $types)) {
    $message = "Upload failed. The file type '$key' is not supported";
}


if (empty($message) && isset($lookup[$key])) {
    $message = $lookup[$key];
}
