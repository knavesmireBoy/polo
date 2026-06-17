<?php
include_once 'funcs.php';
function queryMulti(&$classy)
{
       $classy = '';
}

foreach ($articles as $article) {
    if (!empty($article->getItem())) {
        $articles = $article->getItem();
        include '_multi_sections.html.php';
    } else {
        include '_single_section.html.php';
    }
}

/*
route from this page
_multi_sections.html.php' || '_single_section.html.php'

include '_classify.html.php';
include '_section_factory.php';
include __section.html.php';
__section.html.php';
include '_accordion.html.php'
include '_article.html.php' || '_mdarticle.html.php';
'_article.html.php'
'_image.html.php'; || '_video.html.php'
'_mdarticle.html.php' || '_video_article.html.php';
*/