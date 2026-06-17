<?php
$incfile = '';
$mykey = '';
$flush = false;
$myasset = $article->assets[0] ?? null;
//$myhead = preg_match('/^<h\d>.+/', $article->summary);
include '_accordion.html.php';//top of section

if (current($article->assets)) {//if article has assets;
  foreach ($article->assets as $k => $myasset) {
    $_pth = scanMultiDir([IMAGES, ASSETS, VIDEOS, RESOURCES], trim($myasset->path));
   if($_pth && fileExists($_pth)){
      $incfile = validate_extension(trim($myasset->path), VIDEO_EXT) ? 'video' : 'image';
      $flush = !isset($article->assets[$k + 1]);
      if(!isset($sectionhead)){
        echo $article->summary;
        $sectionhead = true;
      }
      include '_article.html.php';
    }
  }
} else {
  //dump($article->content, preg_match('/\w+\.html\.php$/', $article->content));
  //non database derived article !! ASSUMES NO ASSETS
  if (preg_match('/\w+\.html\.php/', $article->content)) {
    include $article->content;
  } else {
    include '_mdarticle.html.php';
  }
}
