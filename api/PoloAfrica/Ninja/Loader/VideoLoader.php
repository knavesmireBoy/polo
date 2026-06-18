<?php

namespace Ninja\Loader;

use \PoloAfrica\Controllers\Uploader;

class VideoLoader extends Loader
{
    protected $dir = '';
    protected $filename;
    protected $extensions = ['mp4', 'ogv', 'webm'];

    public function __construct(protected Uploader $controller, protected string $local, protected string $thumbs = '', protected $ratio = 0, protected $pp = '')
    {
        $this->local = normalizePath('identity', $local);
    }

    protected function doAssign($path, $arg = 'upload')
    {
        if ($arg === 'archive') {
            $this->assign($this->dir . $path, $this->local . $path);
        }
    }

    protected function doCopy($filename, $flag = false)
    {
        $pathtofile = fileExists($this->dir . $filename);
        $local = fileExists($this->local . $filename);
        //$assigned = $flag === 'assigned';
        if ($this->checkMimeType($pathtofile)) {
            rename($pathtofile, $this->dir . $filename);
            return true;
        } else if ($this->checkMimeType($local)) {
            rename($local, $this->dir . $filename);
            return true;
        } else if (preg_match('/upload/i', $flag)) {
            if (fileExists(FILESTORE_DIR . $filename)) {
                copy(FILESTORE_DIR . $filename, $this->dir . $filename);
                return true;
            }
        }
    }

    protected function copyFromStore($filename, $flag = false, $sep = '')
    {
        $pathtofile = $this->dir . $sep . $filename;
        if (fileExists($pathtofile) && $this->checkMimeType($pathtofile)) {
            return true;
        } else if (preg_match('/upload/', $flag)) {
            if (fileExists(FILESTORE_DIR . $filename)) {
                copy(FILESTORE_DIR . $filename, $pathtofile);
                return true;
            }
        }
    }

    public function validatePath($filename, $flag = false)
    {
        $pass = isDir($this->dir);
        $assigned = $pass && $flag === 'assigned' && !empty($this->pp);

        if ($assigned || (!$pass && !empty($this->pp))) {
            if ($assigned) {
                $this->dir = $this->setDir($this->pp);
            }
            /*
            $pass = isDir($this->dir);
            if (!$pass) {
                var_dump(999);
                $pass = mkdir($this->dir, 0777, true);
            }
                */
        }
        return $pass && $this->doCopy($filename, $flag);
    }

    public function handleAsset($values, $pp = '', $flag = false)
    {
        $pass = null;
        $this->pp = $pp;

        if ($flag === 'assigned') {
            $this->dir = $this->local;
            $this->validatePath($values['path'], $flag);
            $flag = isUpperCase($flag) ? $flag : 'uploaded'; //to pass validation
        } else if ($this->pp) {
            $this->dir = $this->setDir($pp);
        }
        $ext = $this->getFileExtension($values['path']);
        //only run validatePath IF extension matches; permissions error
        if (in_array($ext, $this->extensions)) {
            //run BEFORE checkMimeType, to complete path
            $pass = $this->validatePath($values['path'], $flag);
            $pass = $pass && $this->checkMimeType($this->dir . $values['path']);
        }
        if ($pass) {
            if (!$this->validateLink($values, $flag)) {
                return ['id' => $values['id'] ?? 0, 'path' => '', 'ext' => $ext];
            }
            return $values;
        } else if (isset($this->next)) {
            $this->controller->setLoader($this->next);
            return $this->next->handleAsset($values, $pp, $flag);
        } else {
            return ['id' => $values['id'] ?? 0, 'path' => '', 'ext' => ''];
        }
    }

}
