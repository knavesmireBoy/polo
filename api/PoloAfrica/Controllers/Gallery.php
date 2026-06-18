<?php

namespace PoloAfrica\Controllers;

//include_once '../config.php';

use \Ninja\DatabaseTable;
use \Ninja\Loader\ImageLoader;
use \Ninja\Image;

class Gallery extends Uploader
{
    protected $loader;
    public function __construct(protected DatabaseTable $table, protected DatabaseTable $slotTable, private $pp, private $accept, protected array $loaderArgs)
    {
        //parent::__construct($table);
        $this->loader = new ImageLoader($this, ...$loaderArgs['image']);
        $this->slotTable = $slotTable;
        $this->pp = $pp; //pagination
    }

    private function fixOrientation($orient)
    {
        $res = $this->fetch('slotTable', 'orient', $orient, null, 1);
        $res = $this->fetch('table', 'id', $res->id, null, 1);
        return $this->getOrientation($res->path);
    }

    private function findByPath($path)
    {
        $img = $this->fetch('table', 'path', $path);
        $id = $img ? $img->id : null;
        $picid = true;
        return [$id, $picid];
    }
    private function getEntity($id)
    {
        $data = $this->table->find('id', intval($id), null, 1, 0, \PDO::FETCH_ASSOC);
        if (!empty($data)) {
            return $this->persist($data[0]);
        } else {
            retour();
        }
    }

    private function getBoxId($id)
    {
        $img = $this->getEntity($id);
        return [$img, $img->getSlot(true)->id];
    }

    private function doLoadPic($id)
    {
        $pic = $this->fetch('table', 'id', $id);
        if ($pic) {
            return $this->loadpic($pic->id);
        }
    }

    private function getLimit($i)
    {
        $o = isset($this->pp[$i]) ? $this->pp[$i] : null;
        $o = isset($o) && isset($o[0]) ? $o[0] : null;
        return $o ?? $this->pp[0][0];
    }
    private function getOffset($i)
    {
        $o = isset($this->pp[$i]) ? $this->pp[$i] : null;
        $o = isset($o) && isset($o[1]) ? $o[1] : null;
        return $o ?? $this->pp[0][1];
    }

    private function resolvePath($path)
    {
        $pass = true;
        $subset = array_map(fn($o) => $o->id, $this->table->find('path', $path));
        $slotset = array_map(fn($o) => $o->pic_id, $this->slotTable->findAll());
        foreach ($subset as $sub) {
            $pass = $pass && !in_array($sub, $slotset);
        }
        return $pass;
    }

    protected function getOrphans($mode = \PDO::FETCH_CLASS, $flag = true, $orderby = null)
    {
        $cb = function ($a) {
            return $a ? $a['id'] . '/' . $a['path'] : '';
        };
        $live = $this->table->findAll(null, 0, 0, \PDO::FETCH_ASSOC);
        $mylist = $this->getList(null, 0, 0);
        $list = toObject($mylist, true);
        $inter = array_diff(array_map($cb, $live), array_map($cb, $list));
        $orphans = [];
        foreach ($inter as $k => $v) {
            $arr = explode('/', $v);
            $tmp = [];
            $tmp['id'] = $arr[0];
            $tmp['path'] = $arr[1];
            $orphans[] = (object) $tmp;
        }
        return $orphans;
    }

    protected function deleteFiles($id = 0, $backup = false)
    {
        $file = $this->fetch('table', 'id', $id);
        $pp = '';
        if ($file) {
            $path = $file->path;
            $this->loader->doUnlink($path, $pp, $backup);
            return true;
        }
        return false;
    }
    private function getList($orderby = null, $limit = 14, $offset = 0, $mode = \PDO::FETCH_CLASS)
    {
        $slots = $this->slotTable->findAll($orderby, $limit, $offset, $mode);
        $output = [];
        foreach ($slots as $slot) {
            $gal = $this->fetch('table', 'id', $slot->pic_id);
            //$slot->pic_id maybe null
            if ($gal) {
                $gal->box = $slot->id;
                $gal->orient = $slot->orient;
            } else {
                $gal = $slot;
                $gal->box = null;
                // file_exists will pass if "/path/to/file/" but not "/path/to/file/ ",
                //use a space
                $gal->path = ' ';
                $gal->alt = '';
            }
            $output[] = $gal;
        }
        return $output;
    }

    protected function doDelete($id = 0, $flag = false)
    {
        $this->table->delete('id', $id);
        //if called in a loop (from removing multiple archived files) defer header to looper
        $this->complete(GAL_REVIEW, $flag);
    }

    protected function checkRatio($orient, $ratio)
    {
        return round($ratio, 1) === 1.5;
    }

    private function validatePicCookiePath($path)
    {
        return preg_match('/\.jpe?g$/', $path);
    }
    private function getLoadPicCookie($id)
    {
        if (isset($_COOKIE['loadpic'])) {
            list($duff, $name, $action, $id, $path) = explode('/', $_COOKIE['loadpic']);
            if ($this->validatePicCookiePath($path)) {
                unset($_COOKIE['loadpic']);
                setcookie('loadpic', '', -1, '/');
                return $this->findByPath($path);
            }
        }
        return [$id, false];
    }

    protected function reroute($id, $slotid = 0, $key = '')
    {
        $route = isset($_POST['list']) ? GAL_EDIT : GAL_ASSIGN;
        $route = isset($_POST['floats']) ? GAL_UP : $route;
        reLocate($route . "$id/$slotid/$key", '../../');
    }
    protected function save($payload, $route = 'upload')
    {
        $orientMatch = null;
        $orient = '';
        $myratio = 1.5;
        $boxid = $_POST['box'] ?? 0;
        $shuffle = $_POST['shuffle'] ?? null;
        $record = $this->loader->handleAsset($payload);

        if (!empty($record['path'])) {
            $dbImg = $this->fetch('table', 'path', $record['path']);
            $slot = $this->fetch('slotTable', 'id', $boxid);
            $picId = $slot->pic_id ?? 0;
            $path = $dbImg->path ?? $record['path'];
            $slotOrient = $slot->orient ?? '';
            list($orient, $max, $ratio) = $this->getOrientation($path);
            $orientMatch = $picId && ($orient === $slotOrient);
            $myratio = $this->checkRatio($orient, $ratio);

            if ($picId) {
                $id = $record['id'] ?? $boxid;
                if (!$orientMatch) {
                    list($key) = $this->fixOrientation($orient);
                    $this->reroute($id, $boxid, $key);
                }
                if (!$myratio) {
                    $this->reroute($id, $boxid, 'ratio');
                }
            }
            $record['id'] = $dbImg->id ?? ''; //update or insert

            if (empty($record['id']) && $dbImg && ($dbImg->path === $record['path'])) {
                reLocate(GAL_ASSIGN . "$dbImg->id/$boxid/name", '../../');
            }
            //save to gallery
            //$img = $this->table->save($record);
            $img = $this->persist($record, $route);
            $active = $img->getSlot($img->id);
            if ($active && !empty($boxid) && $picId) {
                $img->reAssign($boxid, $orient, $shuffle);
            } else if (!$active) {
                //guard against further inserts... on upload, unless we need to update pic_id
                if (isset($slot)) {
                    $slot->pic_id = $img->id ?? null;
                    if ($slot->id && $slot->pic_id) { //UPDATE ONLY
                        //save to slot
                        $this->slotTable->save(toObject($slot, true));
                    }
                }
            }
            return $img;
        } else { //no path
            $key = 'exist';
            if (!empty($_POST['list'])) {
                $key = 'existed';
                $this->delete($_POST['list'], true);
            }
            reLocate(GAL_ASSIGN . "0/$boxid/$key", '../../');
        }
    }
    /// PUBLIC ////// PUBLIC ////// PUBLIC ////// PUBLIC ////// PUBLIC ////// PUBLIC ////// PUBLIC ///

    public function prepareValues($fileName, $route = 'upload')
    {
        /*
        scenario where $fileName is delivered by ::transit to upgrade from untracked to archived, assigning to a slot should be a separate operation
        $values will match declared $values below
        */
        if (is_array($fileName)) {
            $values = $fileName;
        }
        if (preg_match('/^upload/i', $route) && !empty($fileName)) {
            if (!isset($values)) {
                $alt = isset($_POST['data']) ? $this->setAlt($_POST['data']['alt']) : '';
                $values = ['path' => $fileName, 'alt' => $alt, 'date' => date('Y-m-d')];
            }
            $img = $this->save($values, 'upload');
            if ($img) {
                $this->id = $img->id;
                reLocate(GAL_RELOAD . $img->id, '../../');
            }
        }
    }

    public function nextpage($int = 1)
    {
        $i = is_numeric($int) ?  $int += 1 : 0;
        return $this->display($i);
    }

    public function prevpage($int = 0)
    {
        $i = is_numeric($int) ?  $int -= 1 : 0;
        if ($i < 0) {
            $i = count($this->pp) - 1;
        }
        return $this->display($i);
    }

    public function display($int = 0)
    {
        if (is_numeric($int)) {
            $int = (abs($int) % count($this->pp));
            $limit = $this->getLimit($int);
            $offset = $this->getOffset($int);
            $output = $this->getList(null, intval($limit), intval($offset));
            return [
                'template' => 'gallery.html.php',
                'variables' => [
                    'gallery' => $output,
                    'prevpage' => $int,
                    'nextpage' => $int,
                    'layout' => $limit == 12 ? 'alt' : ''
                ]
            ];
        } else {
            retour();
        }
    }
    public function loadpic($id = 0, $path = '')
    {
        $img = $this->fetch('table', 'id',  $id);
        if (!$img) {
            reLocate(GAL_LIST, '../../');
        }
        $pics = $this->getList(null, 0);
        $getDetails = fn($o) => $o->path . ',' . $o->orient . ',' . (!empty($o->alt) ? $o->alt : substr($o->path, 0, 3));

        $mapped = implode(';', array_map($getDetails, $pics));
        $pic = $this->fetch('slotTable', 'pic_id',  $id);
        $orient = $pic->orient === 'portrait' ? $pic->orient : '';
        return [
            'template' => 'galcontrols.html.php',
            'variables' => [
                'img' => $img,
                'action' => GAL_NEXT,
                'paths' => $mapped,
                'klas' => $orient
            ]
        ];
    }

    public function next($id = 1)
    {
        list($id, $picid) = $this->getLoadPicCookie($id);
        list($img, $slotid) = $this->getBoxId($id);
        $picid = empty($picid) ? $img->getNext($slotid) : $img->getCurrent($slotid);
        return $this->doLoadPic($picid);
    }

    public function prev($id = 1)
    {
        list($id, $picid) = $this->getLoadPicCookie($id);
        list($img, $slotid) = $this->getBoxId($id);
        list($img, $slotid) = $this->getBoxId($id);
        $picid = empty($picid) ? $img->getPrev($slotid) : $img->getCurrent($slotid);
        return $this->doLoadPic($picid);
    }

    public function preserveValues()
    {
        $set = doSetCookie(true);
        $set('alt', $_POST['data']['alt'] ?? ' ');
        $set('ratio', $_POST['floats']['ratio'] ?? 1.5);
        $set('offset', $_POST['floats']['offset'] ?? 0.5);
        $set('appearance', $_POST['ints']['appearance'] ?? -1);
        $set('maxsize', $_POST['ints']['maxsize'] ?? 0);
    }

    //flag from reload to indicate post-uploaded state
    public function upload($id = 0, $flag = null, $key = '')
    {
        $pic = $id ? $this->fetch('table', 'id', $id) : null;
        $slot = $pic ? $this->fetch('slotTable', 'pic_id', $id) : null;
        $slotid = $slot ? $slot->id : (is_numeric($flag) ? $flag : 0);
        //allows for messaging on validation failure (orientation etc..)
        if (!$pic && !$slot && $slotid) {
            $slot = $this->fetch('slotTable', 'id', $slotid);
            if ($slot) {
                $pic = $this->fetch('table', 'id', $slot->pic_id);
            }
        }
        $orphans = $this->getOrphans();
        $exit_guide = '';
        $message = empty($this->message) ? $key : $this->message;
        $message = is_bool($flag) ? 'reloaded' : $message;

        if ($pic) {
            $fileinfo = array_map(function ($mypic) {
                list(, $mx, $ro) = $this->getOrientation($mypic->path);
                return ['ratio' => $ro, 'max' => $mx];
            }, [$pic]);
            $exit_guide = GAL_UP . $pic->id . '/omitguide';
        }

        $previewklas = isset($pic->path) ? 'pic' : '';
        $reloaded = is_bool($flag) ? ' reloaded' : '';
        $omit = ($flag === 'omitguide') ? ' sansguide' : '';
        $previewklas .= $reloaded;
        $previewklas .= $omit;
        //use the slide class to display full size image (ie hide the form and the uploadguide)
        $slide = (($id === $flag) && empty($key)) ? ' slide' : '';
        $previewklas .= $slide;
        //$orphans need to be object for form but array for fromFileStore
        $_orphans = array_map(curry2('toObject')(true), $this->getOrphans(\PDO::FETCH_ASSOC));
        $storedfiles = $this->fromFileStore($_orphans);

        $_alt = $_COOKIE['alt'] ?? '';
        $_attrid = $_COOKIE['attrid'] ?? '';
        $a = $_COOKIE['appearance'] ?? '';
        $b = $_COOKIE['maxsize'] ?? '';
        $c = $_COOKIE['offset'] ?? '';
        $d = $_COOKIE['ratio'] ?? '';
        $map = doSetCookie(false);
        array_map($map, ['alt', 'attrid', 'maxsize', 'offset', 'ratio', 'appearance']);

        $ret = [
            'template' => 'galupload.html.php',
            'variables' => [
                'action' => GAL_ON_UPLOAD . $id,
                'filename' => 'uploadfile',
                'accept' => $this->accept,
                'warning' => '', //default
                'omitguide' => $omit, //flag
                'previewklas' => $previewklas,
                'key' => $message,
                '_alt' => $_alt,
                '_attrid' => $_attrid,
                '_appearance' => $a,
                '_maxsize' => $b,
                '_offset' => $c,
                '_ratio' => $d,
                'img' => $pic,
                'box' => $slotid,
                'fileinfo' => isset($fileinfo) ? $fileinfo[0] : [],
                //_params.html.php
                'ratio' => isset($fileinfo) ? $fileinfo[0]['ratio'] : 1.5,
                //_select.html.php
                'routes' => ['route' => 'upload'],
                'select' => ['options' => $orphans, 'identity' => 'PATH', 'optval' => 'path', 'target' => null, 'disabled' => false],
                //_uploadguide.html
                'exit_guide' => $exit_guide ? $exit_guide : '/gallery/upload/0/omitguide'
            ]
        ];
        if (isset($_SESSION['filestore'])) {
            $ret['variables'] = array_merge($ret['variables'], [
                'super' => true,
                'untracked' => $this->doGetUntracked(),
                'filestore' => $storedfiles
            ]);
        }
        return $ret;
    }

    public function reload($picid = 0)
    {
        $file = $this->fetch('table', 'id', $picid);
        if ($file) {
            return $this->upload($file->id, true);
        } else {
            reLocate(GAL_REVIEW, '../../');
        }
    }
    public function review($key = '')
    {
        $output = $this->getList(null, 0, 0);
        return [
            'template' => 'galedit.html.php',
            'variables' => [
                'gallery' => $output,
                'routes' => [],
                'key' => is_numeric($key) ? '' : $key
            ]
        ];
    }

    //$boxid: pass $boxid from dropdown version of image edit form to freetext version
    public function assign($id = 0, $slotid = 0, $key = '')
    {
        if (isset($_SESSION['filestore'])) {
            if ($id) {
                $img = $this->fetch('table', 'id', $id);
                $slot = isset($img) ? $this->fetch('slotTable', 'pic_id', $id) : null;
                $slot = $slot ? $slot : $this->fetch('slotTable', 'id', $slotid);
            }
            $mybox = isset($slot) ? $slot->id : $slotid ?? '';
            $myid = $id ?? '';
            $link = $myid ? GAL_UP . $myid : GAL_UP;
            $edit = $myid ? GAL_EDIT . $myid : GAL_EDIT;

            if ($mybox && !$myid) {
                $edit .= "0/$mybox/menu";
            }

            $freetext = '<p>You may simply assign a <strong>library</strong>* image to a placeholder (box). However, the file must conform to a 1.5 aspect ratio (be it portrait or landscape) the path must be spelt exactly, and the file must obviously exist - and with the correct permissions - in the target directory.</p>';
            $freetext = isset($_SESSION['filestore']) ? $freetext : '';

            $orphans = $this->getOrphans(\PDO::FETCH_ASSOC);
            $dropdowntext = $orphans === [] ? '<p><strong>*</strong><dfn>the file resides in a folder but is not referenced in the database.</p>' : '<p>Alternatively use the <a href="' . $edit . '">dropdown menu</a> to assign a currently <strong>archived</strong>† image to the selected location.</p><p><strong>*</strong><dfn>the file resides in a folder but is not referenced in the database. </dfn><strong>†</strong><dfn>the file is referenced in the database but not assigned to a slot.</dfn></p>';
           

            return [
                'template' => 'galimage.html.php',
                'variables' => [
                    'action' => GAL_ASSIGN,
                    'box' => $mybox,
                    'img' => isset($img->id) ? $img : null,
                    'para' =>  '<p>We strongly recommend <a href="' . $link . '">uploading</a> a file again, not least as this is the only way to enforce the correct <strong>aspect ratio</strong> required by the gallery. Uploading also provides the opportunity to crop/rename/resize the file.</p>'
                        . $freetext . $dropdowntext,
                    //_picvalidation.html.php
                    'key' => $key,
                    'optpara' => '',
                    'super' => true
                    //'routes' => ['route' => 'assign']
                    //'disabled' => false,
                    //'orient' => null,
                ]
            ];
        } else {
            reLocate(GAL_EDIT . "$id/$slotid/freetext");
        }
    }
    /*
    in the edit function $slotid and $key would be present only on a redirect usually because of an issue missing pic, wrong orientation or wrong ratio
    */
    public function edit($id = 0, $slotid = 0, $key = '')
    {
        $locate = !$id ? '../' : '../../';
        $data = $this->fetch('table', 'id', $id, null, 0, 0, \PDO::FETCH_ASSOC);
        if (!$data && !$key) {
            // $data = $this->fetch('slotTable', 'id', $slotid, null, 1);
            reLocate(GAL_ASSIGN . "$id/$slotid");
            //reLocate(GAL_REVIEW, $locate);
        }
        $img = $data ? $this->persist($data) : null;
        $slot = $img ? $img->getSlot(true) : $this->fetch('slotTable', 'id', $slotid, null, 0, 0, \PDO::FETCH_ASSOC);

        if ($img && $slot) {
            $img->box = $img->getSlot(true)->id;
            $img->orient = $img->getSlot(true)->orient;
        }
        $myboxid = $img->box ?? $slotid;
        //$myorient = $img->orient ?? '';
        $data = $this->table->findAll();

        $cb = function ($o) {
            return fileExists(GALLERY_IMG . $o->path);
        };

        $active = array_map(
            function ($o) {
                $ret = [];
                $ret['id'] = $o->id;
                $ret['alt'] = $o->alt;
                $ret['path'] = $o->path;
                return (object) $ret;
            },
            array_filter($data, fn($o) => $o->getStatus(true))
        );
        $archived = array_map(
            function ($o) {
                $ret = [];
                $ret['id'] = $o->id;
                $ret['alt'] = $o->alt;
                $ret['path'] = $o->path;
                return (object) $ret;
            },
            array_filter($data, fn($o) => $o->getStatus(false))
        );

        if (!$img) {
            $img = $this->table->getEntity();
            //to access $img->orderById
            $nullify = true;
        }
        $active = $img->orderById($active);
        $archived = $img->orderById($archived);
        $assign = GAL_ASSIGN . "$id/$myboxid";
        $target = empty($key) ? $id : $slotid;
        $myid = $id ?? '';
        $link = $myid ? GAL_UP . '-1/' . $myid : GAL_UP;
        $active = safeFilter($active, $cb);
        $archived = safeFilter($archived, $cb);
        $ret = [
            'template' => 'galimage.html.php',
        ];
        $_variables = [
            'action' => GAL_EDIT,
            'img' => isset($nullify) ? null : $img,
            'box' => $myboxid,
            'select' => ['target' => $target, 'identity' => 'list', 'options' => $active ?? [], 'orphans' => $archived ?? [], 'optval' => 'path'],
            'para' => '<p>In this form you can assign a new NUMERICAL location to your SELECTED image using the BOX drop-down or REPLACE your selected picture at the CURRENT location using the PATH drop-down (far left). By default the two pictures will be swapped. Checking the checkbox will SHUFFLE pictures forward/backward. Portrait and Landscape pictures will not be swapped.</p>',
            //_picvalidation.html.php
            'key' => $key,
            'slotid' => $slotid,
            //'routes' => ['route' => 'edit']
            //'orient' => $myorient,
            //'disabled' => false,
        ];

        if (isset($_SESSION['filestore'])) {
            $optional = [
                'optpara' => '<p>Click <a href="' . $assign . '">here</a> to use free text input as opposed to the dropdown menu provided, you may also <a href="' . $link . '">upload</a> a new file to replace your selection.</p><p>You may of course ignore all of the above and simply update the alt attribute.</p>',
                'super' => true
            ];
            $ret['variables'] = array_merge($_variables, $optional);
        } else {
            $optional = ['optpara' => '<p>You may also <a href="' . $link . '">upload</a> a new file to replace your selection.</p><p>You may of course ignore all of the above and simply update the alt attribute.</p>'];
            $ret['variables'] = array_merge($_variables, $optional);
        }
        return $ret;
    }
    public function assignSubmit($id = 0)
    {
        if (isset($_POST['alt'])) {
            $alt = trim($_POST['alt']);
            $payload = ['alt' => $alt];
            $payload['path'] = trimToLower($_POST['path']);
            $img = $this->fetch('table', 'path', $payload['path']);
            if ($img) {
                $payload['alt'] = $img->alt != $alt ? $alt : $img->alt;
            }
            $date = !empty($_POST['date']) ? $_POST['date'] :  date('Y-m-d');
            $payload['date'] =  $this->checkdate($date) ? NULL : $date;
            $this->save($payload);
        }
        reLocate(GAL_REVIEW, '../../');
    }

    public function editSubmit()
    {
        $route = 'assign';
        if (isset($_POST['pk'])) {
            $route = 'edit';
            $date = !empty($_POST['date']) ? $_POST['date'] :  date('Y-m-d');
            $alt = trim($_POST['alt']);
            $payload['date'] =  $this->checkdate($date) ? NULL : $date;
            $payload['id'] = $_POST['pk'];
            $payload['alt'] = $alt;
            //would be set on NORMAL execution
            if (!empty($_POST['list'])) {
                $payload['id'] = $_POST['list'];
                $img = $this->fetch('table', 'id', $payload['id']);
                $payload['path'] = $img->path ?? '';
                $payload['alt'] = $img->alt ?? '';
                if ($_POST['pk'] == $_POST['list']) { //editing 
                    $_alt = $img->alt ?? '';
                    $payload['alt'] = $_alt != $alt ? $alt : $_alt;
                }
            }
            $this->save($payload, $route);
        }
        reLocate(GAL_REVIEW, '../../');
    }

    private function doGetUntracked($flag = false)
    {
        if (isset($_SESSION['filestore'])) {
            $paths = $this->table->findAll();
            $active = array_map(fn($o) => $o->path, $paths);
            $scanned = safeScanDir(GALLERY_IMG);
            $untracked = arrayDiff($scanned, $active);
            if ($flag) {
                reLocate(ASSET_UNTRACKED . implode(';', $untracked));
            }
            return $untracked;
        }
        return [];
    }

    /*emergency bypassing of uploads in which we imagine a scenario where an image can no longer be found but FILESTORE_IMG still holds the original upload
    files are copied FROM FILESTORE_IMG to ARTICLE_IMG then processed into GALLERY_IMG with the default ratio
    */
    public function transit($filename = '', $alt = '')
    {
        if ($filename) {
            $pathtofile = fileExists(ARTICLE_IMG . $filename);
            if ($pathtofile ||  fileExists(GALLERY_IMG . $filename)) {
                $bg = $this->getBackgroundColor();
                $img = new Image(60, 90, $bg);
                //route only used for relocating
                $img->setRoute($this->getClassName() . '/upload', $_POST['article'] ?? 0);
                //if just moved from uploads...
                $galpath = normalize(GALLERY_IMG . $filename);
                if ($pathtofile) {
                    rename($pathtofile, $galpath);
                    list($pathtofile) = findfile(GALLERY_IMG . $filename);
                } else {
                    $pathtofile = fileExists(GALLERY_IMG . $filename);
                }

                if ($pathtofile) {
                    $img->thumbs($pathtofile, normalize(GALLERY_THUMBS . $filename), 1.5);
                    $img->build($pathtofile, normalize(GALLERY_IMG . $filename), 1.5);
                }
                list($w, $h) = $img->retrieveDims();
                if (getRatio($w, $h) !== 1.5) {
                    copy($pathtofile, FILESTORE_DIR . $filename);
                }
            }
            $this->prepareValues(['path' => $filename, 'alt' => $alt, 'date' => date('Y-m-d')], 'uploaded');
        }
        else {
            reLocate(GAL_REVIEW);
        }
    }

    public function getuntracked($articleId = -1)
    {
        $files = array_map(fn($o) => GALLERY_IMG . $o, $this->doGetUntracked());
            
        if (!empty($files)) {
            return [
                'template' => 'retriever.html.php',
                'title' => "Retrieved Files",
                'variables' => [
                    'files' => $files,
                    'exit' => GAL_MANAGE . '-1',
                    'op' => '',
                    'dir' => GAL_UNTRACKED
                ]
            ];
        } else {
            reLocate(ASSET_MANAGE . '-1/filestory');
        }
    }

    public function manage($id = -1)
    {
        //$id should be -1 to indicate request is NOT from an article
        //$orphans need to be object for form but array for fromFileStore
        $_orphans = array_map(curry2('toObject')(true), $this->getOrphans(\PDO::FETCH_ASSOC));
        $storedfiles = $this->fromFileStore($_orphans);
        $route = $id < 0 ? '/gallery' : '/asset';
        $ret = [
            'template' => 'orphans.html.php',
            'variables' => [
                'group' => $this->getOrphans(\PDO::FETCH_ASSOC),
                'id' => $id,
                'action' =>   $route,
                'dir' => GALLERY_IMG,
                'exit' => ['href' => GAL_REVIEW, 'txt' => 'Back To Gallery'],
                'key' => '',
                'allpaths' => [], //assetController
                'articleId' => $id //required to indicate
            ]
        ];
        if (isset($_SESSION['filestore'])) {
            $ret['variables'] = array_merge($ret['variables'], [
                'super' => true,
                'untracked' => $this->doGetUntracked(),
                'filestore' => $storedfiles
            ]);
        }
        return $ret;
    }

    public function manageSubmit()
    {
        if (!empty($_POST)) {
            $backup = isset($_POST['backup']);
            $archived = [];
            $pics = [];
            if (isset($_POST['all'])) {
                $archived = array_map(fn($a) => $a->id, $this->getOrphans());
            } 

            $pics = $_POST['pics'] ?? [];

            if (!empty($archived) && !empty($pics)) {
               $pics = array_diff($archived, $pics);
            }
            $pics = empty($pics) ? $archived : $pics;

            foreach ($pics as $k) {
                $pic = $this->fetch('table', 'id', $k);
                if ($pic && isset($pic->path)) {
                    if ($this->resolvePath($pic->path)) {
                       
                        $this->destroy($k, true, $backup);
                    } else {
                        $this->delete($k, true);
                    }
                }
            }

            if (empty($pics) && $backup) {
                $filepaths = $this->fromFileStore($this->getOrphans(\PDO::FETCH_ASSOC), true);
                $doRemove = doWhen('fileExists', 'unlink');
                $filepaths = array_map($doRemove, $filepaths);
            }
        }
        reLocate(GAL_REVIEW, '../../');
    }

    /*
    public function getBackgroundColor()
    {
        //default background is black [0,0,0], any other value [1,0,0] needs to be returned;
        $pass = array_filter($this->bg, fn($i) => $i);
        return empty($pass) ? [] : $this->bg;
    }
        */
}
