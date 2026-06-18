<?php

namespace PoloAfrica\Controllers;

use \Ninja\DatabaseTable;
use \Ninja\Loader\Loader;

abstract class Uploader
{
    protected $message = '';
    protected $id = 0;
    protected $ApplicationLoader;
    protected $imageLoader;
    protected $videoLoader;
    protected $loader;

    public function __construct(protected DatabaseTable $table, private $accept) {}

    abstract protected function getOrphans($mode = \PDO::FETCH_CLASS, $flag = true, $orderBy = null);
    abstract protected function save($payload);
    abstract protected function doDelete($id = 0, $flag = false);
    abstract protected function deleteFiles($id = 0);
    abstract public function preserveValues();

    protected function getRatio()
    {
        return $this->loader->getRatio();
    }

    protected function getNameOfClass()
    {
        return static::class;
    }

    protected function persist($record, $route = 'edit')
    {
        $unset = doSetCookie(false);
        $set = doSetCookie(true);
        $name = $this->getClassName();
        $id = $record['id'] ?? null;
        $gal = ['edit' => GAL_EDIT, 'upload' => GAL_UP, 'assign' => GAL_ASSIGN];
        $asset = ['edit' => ASSET_EDIT, 'upload' => ASSET_UPLOAD, 'assign' => ASSET_ASSIGN];
        $lookup = ['gallery' => $gal, 'asset' => $asset];
        $lib = $lookup[$name];
        $route = $lib[$route];
        $route = "$route" . $id ?? '';
        try {
            $unset('error');
            $e = $this->table->save($record);
        } catch (\Exception $e) {
            $msg = 'Error saving record: ' . $e->getMessage();
            $set('error', $msg);
            reLocate($route);
        }
        return $e;
    }


    protected function getExtension($path)
    {
        $path = basename($path);
        return strtolower(substr(strrchr($path, "."), 1));
    }
    protected function swapJpeg($path)
    {
        $jpg = findMatch('/jpe?g$/i', $path, 0);
        if ($jpg) {
            $jpg = strlen($jpg) == 4 ? 'jpg' : 'jpeg';
            return preg_replace("/(\w+)(\.)(\w+)/",  "$1$2$jpg", $path);
        }
        return $path;
    }

    protected function getFileExtension($path)
    {
        $fileNameCmps = explode(".", $path);
        return strtolower(end($fileNameCmps));
    }
    protected function validatePath($path = '')
    {
        return $this->loader->validatePath($path);
    }
    protected function setAlt($str = '')
    {
        if (strpos($str, '/')) {
            return explode('/', $str)[0];
        }
        return $str;
    }

    protected function getAccess($i)
    {
        //2 'Content Editors' //4 'Photo Editors' 
        $lib = [1 => 'Registered Users', 2 => 'Content Editors', 4 => 'Photo Editors'];
        return isset($lib[$i]) ? $lib[$i] : 'Account Administrators';
    }

    protected function getOrientation($path, $klas = 'landscape')
    {
        return $this->loader->getOrientation($path, $klas);
    }

    protected function getArchiveCopy($articleId, $archived, $untracked = [], $filestore = [])
    {
        $manage = ASSET_MANAGE . $articleId ?? '';
        $t = 'files that are referenced in the database but not assigned to an article';
        $link = '<a href="' . $manage . '" title="' . $t . '">ARCHIVED</a> files';
        $unlink = '<strong title="' . $t . '">ARCHIVED</strong> files';
        if (!isset($_SESSION['filestore'])) {
            if ($archived) {
                $span = $link;
            } else {
                $span = $unlink;
            }
        } else {
            if (empty($filestore) && empty($untracked) && empty($archived)) {
                $span = $unlink;
            } else {
                $span = $link;
            }
        }
        return $span;
    }

    protected function fromFileStore(array $tracked, bool $flag = false)
    {   //issue with files starting with zero 01-JAN etc..
        $files = safeFilter(scandir(FILESTORE_DIR), partial('findMatch', '/^(?:0?\d?|\w|\d)\w/i'));
        $udiff = curry2(partial('array_udiff', $files))('strcasecmp');
        $extract = partial('array_map', fn($o) => $o['path']);
        $cb = composer($udiff, $extract);
        $untracked = $cb($tracked);

        if ($flag) { //provide full path for deletion
            // $untracked = array_map('toObject', $untracked);??
            return array_map(fn($str) => FILESTORE_DIR . $str, $untracked);
        }
        //array_merge a file MAY not be present in the untracked array but we may want the option of removing it from the filestore
        return array_unique(array_merge($untracked, $files));
    }

    protected function checkdate($date = null)
    {
        $y = date('Y');
        $date = $date ?? $_POST['date'];
        $d = explode('-', date($date))[0];
        return $d < $y;
    }

    public function fetch($t, $prop, $val, ...$rest)
    {
        $ret = [];
        $mod = false;
        if ($val) { //safeguard against missing values
            if (is_numeric(strpos($t, '_'))) {
                $t = substr($t, 1);
                $mod = true;
            }
            if (strtoupper($t) === $t) { //PDO::FETCH_ASSOC
                $t = strtolower($t);
                $ret = $this->{$t}->find($prop, $val, null, 0, 0, \PDO::FETCH_ASSOC);
            } else {
                $ret = $this->{$t}->find($prop, $val, ...$rest);
            }
        }
        return empty($ret) ? null : ($mod ? $ret : $ret[0]);
    }

    public function update($values, $arg = '')
    {
        $this->persist($values, $arg);
    }

    protected function filter($array, $cb, $flag = false)
    {
        $res = safeFilter($array, $cb);
        $item = $res[0] ?? null;
        return $item ? ($flag ? $res : $item) : null;
    }

    protected function complete($path, $loopcallback = false)
    {
        if ($path && !$loopcallback) {
            reLocate($path, '../../');
        }
    }

    protected function period($name, $ext)
    {
        $tmp = explode('.', $name);
        if (isset($tmp[2])) {
            array_pop($tmp);
            $name = implode('_', $tmp);
            $name = "$name.$ext";
        }
        return preg_replace('/\s/', '', $name);
    }

    protected function encrypt($name, $ext)
    {
        return  md5(time() . $name) . '.' . $ext;
    }

    protected function setName($encrypt = false)
    {
        $txt = $_POST['data']['description'] ?? $_POST['data']['alt'] ?? ''; //subclass??
        $fileName = trimToLower($_FILES['uploadfile']['name']);
        $ext = $this->getFileExtension($fileName);
        if (strpos($txt, '/')) {
            $f = explode('/', $txt);
            if (!empty($f[1])) {
                $name = preg_replace('/\.\w+$/', '', $f[1]); //in case extension was included
                $fileName = $name . '.' . $ext;
            }
        }
        //$fileName = $this->period($fileName, $ext);
        $fileName = preg_replace('/\s+/', '_', $fileName);
        $newFileName = empty($encrypt) ? $fileName : $this->encrypt($fileName, $ext);
        return strtolower($newFileName);
    }

    abstract public function edit();
    abstract public function editSubmit();
    abstract public function upload($id = 0, $flag = true);
    abstract public function manage($col);
    //used by Loader
    abstract public function prepareValues($fileName, $arg = '');

    public function getClassName($prefix = '')
    {
        return $prefix . strtolower(substr(strrchr($this->getNameOfClass(), '\\'), 1));
    }

    public function destroy($id = 0, $flag = false, $backup = false)
    {
        //check if file in active array
        $this->deleteFiles($id, $backup);
        $this->doDelete($id, $flag);
    }

    public function findAll($mode, ...$args)
    {
        switch (count($args)) {
            case 0:
                $ret = [null, 0, 0, $mode];
                break;
            case 1:
                $ret = [...$args, 0, 0, $mode];
                break;
            case 2:
                $ret = [...$args, 0, $mode];
                break;
            case 3:
                $ret = [...$args, $mode];
                break;
            default:
                $ret = [];
        }
        return $this->table->findAll(...$ret);
    }

    public function delete($id = 0, $flag = false)
    {
        $this->doDelete($id, $flag);
    }

    public function setLoader(Loader $loader)
    {
        if ($loader) $this->loader = $loader;
    }

    public function retrieve($articleId = 0)
    {
        $route = $articleId < 0 ? '/gallery' : '/asset';
        $all = $this->findAll(\PDO::FETCH_ASSOC);
        $storefiles = $this->fromFileStore($all);
        $action = "$route/retrieve/$articleId";
        $exit = "$route/manage/$articleId";
    
        if (!empty($storefiles)) {
            return [
                'template' => 'retrieve.html.php',
                'title' => "Filestore",
                'variables' => [
                    'arguments' => '',
                    'action' => $action,
                    'exit' => $exit,
                    'group' => $storefiles,
                    'dir' => ''
                ]
            ];
        } else {
            reLocate(ASSET_MANAGE . $articleId . "/filestory");
        }
    }

    public function retrieveSubmit($articleId = 0)
    {
        $results = $_POST['data'] ?? [];
        $route = $articleId < 0 ? 'gallery' : 'asset';
        $filtered = [];
        $trash = [];
        foreach ($results as $k => $v) {
            if ($v !== 'cancel') {
                $filtered[$k] = $v;
            } else {
                $trash[] = $k;
            }
        }

        if (!empty($trash)) {
            $cb = composer(partial('array_map', doWhen('fileExists', 'unlink')), partial('array_map', fn($o) => FILESTORE_DIR . $o));
            $cb($trash);
        }

        if (!empty($filtered)) {
            foreach ($filtered as $k => $v) {
                //$v is either copy or rename
                //$v(FILESTORE_DIR . $k, TRANSIT . $k);can't use string as function so...
                curry2($v)(TRANSIT . $k)(FILESTORE_DIR . $k);
            }
            reLocate("/$route/retriever/$articleId/$v", '../../');
        }
        reLocate("/$route/manage/$articleId", '../../');
    }

  
    public function onupload($myid = 0)
    {
        $mycontroller = $this->getClassName('/');
        $metadata = $_POST['data']['attr_id'] ?? '';
        if (isset($_FILES['uploadfile']) && $_FILES['uploadfile']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['uploadfile']['tmp_name'];
            if (!is_uploaded_file($fileTmpPath)) {
                $this->message = "Possible file upload attack: ";
                reLocate("$mycontroller/upload/$myid/_/attack", '../../');
            }
            $newFileName = $this->setName();
            $remotePath = FILESTORE_DIR . $newFileName;
            if (!move_uploaded_file($fileTmpPath, $remotePath)) {
                reLocate("$mycontroller/upload/$myid/_/access", '../../');
            } else {
                $id = $_POST['data']['article_id'] ?? 0;
                $this->loader->init($newFileName, $id, $metadata);
                return $this->prepareValues($newFileName, 'upload');
                exit;
            }
        } //ok
        else {
            $u = strval(floor(intval($_SERVER['CONTENT_LENGTH']) / 1000000));
            $u .= 'mb';
            $msg = empty($_FILES) ? "exceeds_$u" : 'choose';
            $this->preserveValues();
            reLocate("$mycontroller/upload/$myid/0/$msg/$u", '../../');
        }
    }

    protected function queryTransit(array $pathtofiles)
    {
        //exclude files that are referenced in the database
        $all = array_map(fn($o) => $o['path'], $this->table->findAll('path', 0, 0, \PDO::FETCH_ASSOC));
        $getPortion = doWhen('identity', 'basename');
        $filenames = arrayDiff(array_map($getPortion, $pathtofiles), $all);
        $ret = [];
        foreach ($pathtofiles as $path) {
            if (in_array($getPortion($path), $filenames)) {
                $ret[] = $path;
            }
        }
        return safeFilter($ret, 'identity');
    }

    function doQueryTransit(array $pathtofiles)
    {
        return $this->queryTransit($pathtofiles);
    }

    public function getDisplayPath($file)
    {
        if (is_numeric(strpos($file, '/'))) {
            $pathtofile = $file;
        } else {
            $dir = $this->loader->getDirFromPath($file);
            $pathtofile = "$dir$file";
        }
        if (fileExists("$pathtofile")) {
            $i = strpos($pathtofile, '/') === 0;
            return $i ? "$pathtofile" : "/$pathtofile";
        }
    }

    public function retriever($articleId = -1, $op = '')
    {
        $transitfiles = [];
        $dir = ASSET_UNTRACKED;
        $transit = safeFilter(scandir(TRANSIT), fn($str) => findMatch('/^(?:0?\d?|\w|\d)\w/i', $str));
        $mapDisplay = partial('array_map', [$this, 'getDisplayPath']);
        $queryTransit = composer([$this, 'doQueryTransit'], $mapDisplay);
        $exit = $articleId < 0 ? GAL_MANAGE : ASSET_MANAGE;

        if (!empty($transit)) {
            $transitfiles = safeFilter(array_map(function ($file) {
                $tgt = $this->loader->getDirFromPath($file);
                if (!$tgt || !isDir($tgt)) {
                    return '';
                }
                chmod($tgt, 0755);
                $src = TRANSIT . $file;
                if (fileExists($src)) {
                    $dest = $tgt . $file;
                    try {
                        chmod($src, 0755);
                        rename($src, $dest);
                    } catch (\Exception $e) {
                        $e;
                    }
                    return $dest;
                } else {
                    return '';
                }
            }, $transit), 'identity');
            $files = $queryTransit($transitfiles);

            if($articleId < 0){
                $dir = GAL_UNTRACKED;
                $dir .= 'gallery_img';
            }
            else {
                $dir .= 'article_img/';
            }
 
            if (!empty($files)) {
                return [
                    'template' => 'retriever.html.php',
                    'title' => "Retrieved Files",
                    'variables' => [
                        'files' => $files,
                        'exit' => $exit . $articleId,
                        'op' => $op,
                        'dir' => $dir
                    ]
                ];
            }
        } else {
            dump(99);
            reLocate(ASSET_MANAGE . $articleId);
        }
    }

    public function message($str = '', $i = 0)
    {
        $str = exclaim($str);
        if ($str) {
            return [
                'template' => 'accessdenied.html.php',
                'variables' => [
                    'str' => $str,
                    'accesslevel' => $this->getAccess($i),
                    'submitted' => false
                ]
            ];
        } else {
            retour();
        }
    }

    public function getBackgroundColor($rgb = [])
    {
        return $rgb;
    }
}
