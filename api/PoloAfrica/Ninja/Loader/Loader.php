<?php

namespace Ninja\Loader;

use \PoloAfrica\Controllers\Uploader;
use \Ninja\Image;

abstract class Loader
{
    protected $message = '';
    protected $id = 0;
    protected $dir = '';
    protected $next = null;
    protected $cache = '';
    protected $extensions = [];
    protected $blacklist = [];
    protected $secret = [];
    protected $heading = [];

    public function __construct(protected Uploader $controller, protected string $local, protected string $thumbs = '', private $ratio = 0, private $pp = '')
    {
        $this->local = normalizePath('identity', $local);
    }

    abstract public function handleAsset($record, $dir = '', $flag = false);
    abstract public function validatePath($path);

    public function getAsset($record, $dir = '', $flag = false)
    {
        $record = $this->getAsset($record, $dir, $flag);
        return [$record, $this->dir];
    }

    protected function getNameOfClass()
    {
        $name = strtolower(get_class($this));
        return substr(str_replace('loader', '', $name), 7);
    }

    public function getClassName($flag = false)
    {
        return $flag ? $this->getNameOfClass() : static::class;
    }

    protected function checkMimeType($pathtofile, $ubers = [])
    {
        $find = partial('findMatch', '|\/|');
        $classname = $this->getNameOfClass();
        $exists = partial('identity', $pathtofile);
        $go = doWhen($exists, $find);
        $pass = $go($pathtofile);
        if (!$pass && $this->dir) {
            $path = $this->dir . $pathtofile;
        }
        $path = fileExists($pathtofile);
        $cb = partial('equals', $classname);
        if ($pass && $path) {
            list($type) = explode('/', getMimeType($path, $ubers));
            //  $cb = compose(partial('identity', $type), partial('equals', $classname, $type));
            //  $cb();
            return $type === $classname;
        }
        return false;
    }

    function doCheckMimeType($path)
    {
        return $this->checkMimeType($path);
    }

    protected function setAlt($str = '')
    {
        if (strpos($str, '/')) {
            return explode('/', $str)[0];
        }
        return $str;
    }

    protected function checkDir($filename)
    {
        $dir = normalizePath('identity', $this->dir);
        $pass = fileExists($dir);
        if (!$pass) {
            $pass = mkdir($this->dir, 0777, true);
        }
        if ($pass) {
            copy(FILESTORE_DIR . $filename, $dir . '/' . $filename);
        }
        return $pass;
    }

    protected function changeDir($filename, $source = FILESTORE_DIR, $flag = false)
    {
        $pass = fileExists($this->dir);

        if (!$pass) {
            $pass = mkdir($this->dir, 0777, true);
        }
        if ($pass) {
            $src = $source . $filename;
            $tgt = $this->dir . $filename;
            if ($flag) {
                rename($src, $tgt);
            } else {
                copy($src, $tgt);
            }
        }
        return $pass;
    }

    protected function issetDir()
    {
        return $this->dir === $this->local . $this->pp . '/';
    }

    protected function setDir($pp, $arg = "")
    {
        if (empty($pp)) {
            return $this->local;
        }
        $this->pp = $pp;
        $dir = isDir($this->local . $pp);
        if (!$dir) {
            mkdir($this->local . $pp, 0777, true);
        }
        return  $this->local . $pp . '/';
    }

    protected function assign($from, $to, $action = 'rename')
    {
        $f = partial($action, $from, $to);
        $f();
    }

    public function cleanup($path = '')
    {
        if ($path) {
            $from = fileExists($this->dir . $path);
            $to = fileExists($this->local . $path);
            if ($from && $to) {
                $this->assign($from, $to);
            }
        }
        if (dir_is_empty($this->dir)) {
            $handle = opendir($this->dir);
            closedir($handle);
            rmdir($this->dir);
        }
    }

    protected function validateLink($values, $arg)
    {
        return true;
    }

    public function getRatio()
    {
        return $this->ratio;
    }

    public function setNext($next)
    {
        $this->next = $next;
        return $this;
    }

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

    //assumes initial handler is application/pdf which delegates to more relevant class
    public function getOrientation($path, $klas = 'landscape')
    {
        if (isset($this->next)) {
            return $this->next->getOrientation($path, $klas);
        }
    }

    public function init($filename, $articleId = 0, $metadata = '')
    {
        if (!$this->checkMimeType(FILESTORE_DIR . $filename) && isset($this->next)) {
            return $this->next->init($filename);
        }
    }

    //this is to allow pdf records to remove the attr_id which is used to provide the link copy to the pdf; it's intended to be ephemeral 
    public function exit($id, $record, $flag = true)
    {
        $mime = $this->checkMimeType($this->dir . $record['path']);
        if ($mime) {
            return $record;
        } else if (isset($this->next)) {
            return $this->next->exit($id, $record, $flag);
        }
        return [];
    }

    public function verifyMimeTypes($a, $b)
    {
        if (!empty($a) && !empty($b)) {
            return $this->checkMimeType($a) === $this->checkMimeType($b);
        }
        return false;
    }
    public function verifyExtension($ext, $newext = '')
    {
        $pass = in_array($ext, $this->extensions);
        $pass = !empty($newext) ? in_array($newext, $this->extensions) : $pass;
        if ($pass) {
            return $ext;
        } else if (isset($this->next)) {
            return $this->next->verifyExtension($ext, $newext);
        }
        return '';
    }

    public function makeLink($str, $path, $flag = false)
    {
        return [$str, null];//null is critical, not 0
    }

    public function breakLink($str, $pp, $path, $attr_id)
    {
        return $str;
    }

    public function pathToFile($path)
    {
        return !empty($this->dir) ? $this->dir . $path : '';
    }

    private function scanDir($dir, $path)
    {
        //Extracts files and directories that match a pattern
        $directory = isDir($dir);
        if ($directory) {
            $items = scandir($directory);
            $found = null;
            if ($items) {
                $items = array_filter($items, fn($str) => findMatch('/^\w/', $str));
                foreach ($items as $item) {
                    $found = "$directory$item/$path";
                    if ($found && fileExists($found)) {
                        break;
                    };
                }
                return $found;
            }
        }
    }

    function doScanDir(...$args)
    {
        return $this->scanDir(...$args);
    }

    public function doUnlink($path, $pp = '', $backup = false)
    {
        $doRemove = doWhen('fileExists', 'unlink');
        //$doRemove = doWhen('file_exists', 'unlink');
        $kls = $this->controller->getClassName();
        //focus search in case same named files exist in two destinations
        $scan = $kls === 'gallery' ? [GALLERY] : [ASSETS, IMAGES, RESOURCES];
        $pathtofile = normalize(scanMultiDir($scan, $path));
        $pass = $this->checkMimeType($pathtofile, $scan);

        if ($pass) {
            $paths = [$pathtofile];
            if (isset($this->thumbs)) {
                $paths[] = normalize($this->thumbs . basename($pathtofile));
            }
            if ($backup) {
                $paths[] = FILESTORE_DIR . $path;
            }
            array_map($doRemove, $paths);
        } else if (isset($this->next)) {
            return $this->next->doUnlink($path, $pp, $backup);
        }
    }

    public function prepareValues($filename, $ext = 'upload')
    {
        return $this->controller->prepareValues($filename, $ext);
    }

    public function getFileExtension($path)
    {
        $res = explode(".", $path);
        return strtolower(end($res));
    }

    public function getExtensions()
    {
        return $this->extensions;
    }

    public function generateThumbNail($path)
    {
        $img = new Image(50, 90, [], $this->controller->getClassName());
        $img->thumbs($this->local . $path, $this->thumbs . $path);
    }
}
