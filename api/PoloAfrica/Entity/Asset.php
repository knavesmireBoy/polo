<?php

namespace PoloAfrica\Entity;

class Asset
{
  public $attr_id;
  public $id;
  public $alt;
  public $path;
  public $article_id;
  public $date;

  public function __construct(private \Ninja\DatabaseTable $table, private \Ninja\DatabaseTable $articleTable) {}

  protected function fetch($t, $prop, $val, ...$rest)
  {
    $ret = [];
    if ($val) { //safeguard against missing values
      $ret = $this->{$t}->find($prop, $val, ...$rest);
    }
    return empty($ret) ? null : $ret[0];
  }

  protected function persist($record, $route = 'edit')
    {
        $unset = doSetCookie(false);
        $set = doSetCookie(true);
        try {
            $unset('error');
            $e = $this->articleTable->save($record);
        } catch (\Exception $e) {
            $msg = 'Error saving record: ' . $e->getMessage();
            $set('error', $msg);
            reLocate(ARTICLES_EDIT . $record['id'] ?? '');
        }
        return $e;
    }

  public function getArticle($id = 0, $prop = null)
  {
    $id ? $id : $this->id;
    $asset = $this->fetch('table', 'id', $id);
    $id = $asset ? $asset->article_id : null;
    if ($id) {
      return $this->getArticleDirect($id, $prop);
    }
    return null;
  }

  public function getAssets($id){
    $e = $this->articleTable->getEntity();
    return $e->getAssets($id);
  }

  public function findArticles($list = '')
  {
    $all = $this->articleTable->findAll();
    return $list ? array_column($all, $list) : $all;
  }

  public function getArticleByTitle($id = 0, $prop = null) {}
  public function getArticleDirect($arg = 0, $prop = null)
  {
    $k = intval($arg) ? 'id' : 'title';
    $article = $this->fetch('articleTable', $k, $arg);
    if (!empty($article)) {
      return $prop ? $article->{$prop} : $article;
    }
  }

  public function validate($articleId, $assetId, $regX, $flag = false)
  {
    $res = $this->getArticleDirect($articleId, 'page');
    $articles = $this->articleTable->find('page', $res);
    $articles = array_filter($articles, fn($o) => preg_match($regX, $o->attr_id));
    $articleIds = array_map(fn($o) => $o->id, $articles);
    $ret = [];
    foreach ($articleIds as $i) {
      $res = $this->fetch('table', 'article_id', $i);
      if ($res && !$flag) {
        $ret[] = $res->id;
      } else if ($res && $flag && $res->id != $assetId) {
        $ret[] = $res->id;
      }
    }
    return empty($ret) || in_array($assetId, $ret);
  }

  public function setContent($str, $aId = null)
  {
    if (isset($str)) {
      $page = preg_match('|^!page\/(\w+$)|', $str, $m);
      $page = $m ? $m[1] : null;
      if ($page) {
        $content = $this->getArticle($this->id, 'content');
        $content = preg_replace("|assets\/\w+\/|", "assets/$page/", $content);
        $id = $this->getArticle($this->id, 'id');
        $this->persist(['id' => $id, 'content' => $content]);
      } else {
        $id = $this->getArticle($this->id, 'id');
        /*!! path to file (PDF) needs a leading slash for ROUTING purposes but this will fail the file_exists() test
        fix it when saving the article; ensure leading slash does not already exist
        NOTE USING | instead of / as delimiter to avoid confusion*/
        $str = preg_replace('|(?<!\/)resources\/assets|', '/resources/assets', $str);
        $id = $id ? $id : $aId;
        $values = ['id' => $id, 'content' => trim($str), 'pubDate' => date('Y-m-d')];
        if ($id) {
          $this->persist($values);
        }
      }
    }
  }

  public function getStatus($arg = '')
  {
    $path = $this->path;
    if (!$path) {
      return;
    }

    $pathtofile = scanMultiDir([ASSETS, IMAGES, RESOURCES], $path, ['thumb']);
    $pathtofile = fileExists($pathtofile);
    if (!$pathtofile) {
      return $this->path;
    }
    if (is_bool($arg)) {
      return "/$pathtofile";
    }
    $makemove = partial('makeMove', ASSETS, $pathtofile);
    $move = composer(partial('rename', $pathtofile), 'normalize');
    $pdf = queryPath($pathtofile, 'ext') === 'pdf';
    $bg = preg_match('/#/', $this->attr_id ?? '');
    $assetdir = preg_match("|assets|", $pathtofile);
    $page = $this->getArticle($this->id, 'page');

    if ($arg && ($bg || $pdf)) {
      $makemove($arg);
      if ($pdf) {
        return $this->setContent("!page/$arg");
      }
    }
    if (!$arg && $bg && !$assetdir && $page) {
      $makemove($page);
    }
    if (!$arg && !$bg && $assetdir && !$pdf) {
      $move(ARTICLE_IMG . $path);
    }
  }

  public function preparePoster($ext = 'jpg')
  {
    $video = validate_extension(trim($this->path), VIDEO_EXT);
    if ($video) {
      $subpath = substr($this->path, 0, -3);
      $path = scanMultiDir([ASSETS, IMAGES], $subpath . $ext, ['thumb']);
      return fileExists($path) ? $path : DEV . 'steamboat_willie.jpg';
    }
    return '';
  }

  public function prepareVideo()
  {
    $video = validate_extension(trim($this->path), VIDEO_EXT);
    $pp = $this->getArticle($this->id, 'page');
    if ($video) {
      $subpath = substr($this->path, 0, -4);
      $i = 0;
      $grp = [];
      $pp = $pp ? $pp : 'medley';
      $pp = preg_replace('|\/|', '', $pp);
      //'video/webm; codecs="av01.2.19H.12.0.000.09.16.09.1, flac"'
      while (isset(VIDEO_CODECS[$i])) {
        if ($pp) {
          $path = normalizePath('identity', VIDEOS . $pp . "/$subpath" . VIDEO_EXT[$i]);
        }
        if (isset($path) && fileExists($path)) {
          $grp[] = ['vsrc' => "/$path", 'vtype' => VIDEO_CODECS[$i]];
        }
        $i++;
      }
      return $grp;
    }
    return [];
  }

  public function getHref()
  {
    return '';
  }

  public function getAlt()
  {
    return '';
  }
}
