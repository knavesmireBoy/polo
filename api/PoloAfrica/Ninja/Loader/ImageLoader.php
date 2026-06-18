<?php

namespace Ninja\Loader;

use \PoloAfrica\Controllers\Uploader;
use \Ninja\Image;

class ImageLoader extends Loader
{

  public function __construct(Uploader $controller, protected string $local, protected string $thumbs = '', protected $ratio = 0, protected $pp = '')
  {
    parent::__construct($controller, $local, $thumbs, $ratio, $pp);
    $this->local = normalize($this->local);
    $this->thumbs = normalize($this->thumbs);
    $this->dir = $this->local;
  }

  protected $extensions = ['jpg', 'jpeg', 'gif', 'png'];
  protected $blacklist = ['webp'];
  protected $dir = '';

  /*
  public function getDirFromPath($path)
  {
      $pathtofile = scanMultiDir([FILESTORE_DIR, ASSETS, IMAGES, GALLERY, RESOURCES], $path, ['thumb']);
      $pathtofile = $pathtofile ? fileExists($pathtofile) : null;
      $next = true;
      if ($pathtofile && $this->checkMimeType($pathtofile)) {
          $next = false;
          return $this->local;
      } else if ($next && isset($this->next)) {
          return $this->next->getDirFromPath($path);
      }

      return '';
  }
*/
  protected function doRotate($data) {}

  protected function setBg($str)
  {
    $lib = ['r' => '255,0,0', 'g' => '0,255,0', 'b' => '0,0,255', 'c' => '0,255,255', 'm' => '255,0, 255', 'y' => '255,255,0', 'k' => '0,0,0', 'w' => '255,255,255'];
    $pass = preg_match('/\d{1,3},\s?\d{1,3},\s?\d{1,3}/', $str);
    if ($pass) {
      return $str;
    } else {
      $str = strtolower($str);
      return isset($lib[$str]) ? $lib[$str] : null;
    }
  }
  //init is only used by imageLoader on INITIAL upload and used by others AFTER handleAsset is called
  //handleAsset is the handler that calls next in chain

  public function init($filename, $articleId = 0, $metadata = '')
  {
    try {
      $mime = getMimeType($filename, [FILESTORE_DIR]);
      list($type) = explode('/', $mime);
    } catch (\Exception $e) {
      $e;
    }

    $ext = $this->verifyExtension($this->getFileExtension($filename));
    $bg = [];
    if ($ext && $type === $this->getNameOfClass()) {
      $floats = $_POST['floats'];
      $ints = $_POST['ints'];
      //splitOn returns either or an array of two members, or one value wrapped in array
      $ratio = splitOn($floats['ratio']); //
      $int = splitOn($ints['maxsize']);

      $bg = $this->setBg($floats['offset']);

      if ($bg) {
        $min = curry2('min')(255);
        $bg = explode(',', $bg);
        $bg = array_map($min, $bg);
        $floats['offset'] = 0.5;
      }
      //actual division takes place here
      $floats['ratio'] = !empty($ratio[1]) ? round($ratio[0] / $ratio[1], 2) : $floats['ratio'];
      $ints['maxsize'] = !empty($int[1]) ? round($int[0] / $int[1]) : $ints['maxsize'];
      //or two values are obtained
      if (!isset($ints['rotate'])) {
        if (preg_match('/deg$/', $ints['appearance'])) {
          $ints['rotate'] = intval($ints['appearance']);
          $ints['appearance'] = 0;
        } else {
          $appearance = splitOn($ints['appearance']);
          $ints['appearance'] = $appearance[0];
          $ints['rotate'] = isset($appearance[1]) ? $appearance[1] : 0;
        }
      }
      $floats = array_map('floatval', array_values($floats));
      $ints = array_map('intval', array_values($ints));
      //$bg = array_map('intval', array_values($bg));
      $bg = empty($bg) ? $this->controller->getBackgroundColor() : $bg;
      $id = $_POST['data']['article_id'] ?? 0;
      $img = new Image(60, 90, $bg, $this->controller->getClassName(), $id);
      $img->setRoute($this->controller->getClassName() . '/upload', $id);

      $img->build(FILESTORE_DIR . $filename, $this->local . $filename, ...$floats, ...$ints);
      $img->thumbs(FILESTORE_DIR . $filename, $this->thumbs . $filename, ...$floats, ...$ints);
    }
  }

  public function resolvePath($filename, $sep) {}

  public function process($values, $pp = '', $flag = false) {}

  public function getOrientation($path, $orient = 'landscape')
  {
    if ($path) {
      $ext = $this->getFileExtension($path);
      if (!in_array($ext, $this->extensions)) {
        return ['', 0, 0];
      }

      $path = normalizePath('identity', $this->local . $path);

      if (fileExists($path)) {
        try {
          $image = new Image();
          list($w, $h) = $image->getDims($path);
          $klas = $h > $w ? 'portrait' : 'landscape';
        } catch (\Exception $e) {
          $klas = '';
        }
        $max = $w;
        if ($klas === $orient) {
          $max = $klas === 'portrait' ? $h : $w;
          //$res = $image->doResolution($image);
        }
        $ratio = $h > $w ? ($h / $w) : ($w / $h);
        // $klas = ($orient && $klas != $orient) ? $orient : $klas;
        // dump($klas);
        return [$klas, $max, round($ratio, 1)];
      }
    }
    //some sensible defaults on missing path
    return [$orient, 300, 1.5];
  }

  public function validatePath($path = '')
  {
    $path = normalize($path);
    $kls = $this->controller->getClassName();
    $scan = $kls === 'gallery' ? [GALLERY] : [IMAGES, ASSETS, VIDEOS];
    $hi_res = scanMultiDir($scan, $path, ['thumb']);
    $path = strrchr($hi_res, '/');
    //allow for hyphens in file name MAY need to add additional characters, strips path of directories
    $this->local = preg_replace('|[\w-]+\.\w+|', '', $hi_res);
    $hi_res = $path ? strtolower(substr($path, 1)) : '';
    $thumbs = isDir($this->thumbs);
    $thumb = normalize($thumbs . $hi_res);

    $lo_res = $hi_res && $thumb ? $thumbs . $hi_res : '';
    $imgpth = fileExists($this->local . $hi_res);
    $mime = '';
    if ($hi_res && fileExists($imgpth)) {
      $pth = getMimeType($imgpth, $scan);
      $mime = $pth ? explode('/', $pth)[1] : null;
    }
    return $mime && in_array($mime, $this->extensions) ? [$hi_res, $lo_res] : [null, null];
  }

  public function handleAsset($record, $dir = '', $flag = false)
  {
    $path = $record['path'] ?? trimToLower($_POST['path']);
    $ext = $this->getFileExtension($path);
    list($hi_res, $lo_res) = $this->validatePath($path);
    if ($hi_res) {
      //assign $hi-res NOT $_POST['path'] because of jpe?g
      $record['path'] = $hi_res;
      if (!$lo_res) {
        $this->generateThumbNail($record['path']);
      }
    } else {
      if (in_array($ext, $this->blacklist)) {
        return ['id' => $record['id'] ?? 0, 'path' => '', 'ext' => $ext];
      } else if (isset($this->next)) {
        $this->controller->setLoader($this->next);
        return $this->next->handleAsset($record, $dir, $flag);
      } else {
        return ['id' => $record['id'] ?? 0, 'path' => '', 'ext' => $ext];
      }
    }
    return $record;
  }
}
