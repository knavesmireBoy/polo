<?php

if ($incfile === 'video') {
    $poster = $myasset->preparePoster();
    $videodata = $myasset->prepareVideo();
    //remove title from body text
    $article->content = preg_replace('/^.+/', '', $article->content);
    if ($type === 'singular') {
        $inc = '_video.html.php';
    } else {
        include '_video_article.html.php';
        $inc = false;
    }
} else {
    $inc = '_image.html.php';
}

if ($inc) {
    include $inc;
    if ($flush) {
        include '_mdarticle.html.php';
    }
}
