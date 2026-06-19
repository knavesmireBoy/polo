<?php

namespace PoloAfrica\Controllers;

//include_once 'config.php';

use \Ninja\DatabaseTable;
use \Ninja\Loader\ImageLoader;
use \Ninja\Loader\VideoLoader;
use \Ninja\Loader\ApplicationLoader;
use \Ninja\Image;
use PDO;

class Asset extends Uploader
{
    private $ratio;
    private $storedfiles;

    public function __construct(protected DatabaseTable $table, private $accept, protected array $loaderArgs = [])
    {
        parent::__construct($table, $accept);
        $this->ApplicationLoader = new ApplicationLoader($this, ...$loaderArgs['application']);
        $this->imageLoader = new ImageLoader($this, ...$loaderArgs['image']);
        $this->videoLoader = new VideoLoader($this, ...$loaderArgs['video']);
        $this->ApplicationLoader->setNext($this->imageLoader);
        $this->imageLoader->setNext($this->videoLoader);
        $this->loader = $this->ApplicationLoader;
    }

    private function doTrim($record, $props)
    {
        foreach ($props as $p) {
            $lc = strtolower($p);
            if (isset($record[$lc])) {
                if ($p === strtoupper($p)) {
                    $record[$lc] = trimToLower($record[$lc]);
                } else {
                    $record[$lc] = trim($record[$lc]);
                }
                $txt = $record[$lc];
                //$record[$lc] = preg_replace('/\s\s/', ' ', $txt);
            } else { //ensure default alts (for inserting into db)
                $record[$lc] = '';
            }
        }
        return $record;
    }

    public function soTrim(...$args)
    {
        return $this->doTrim(...$args);
    }


    private function getPhantoms($all)
    {
        $artids = safeFilter(array_unique(array_column($all, 'article_id')), 'identity');
        $e = $this->table->getEntity();
        $liveids = $e->findArticles('id');
        $udiff = curry2(partial('array_udiff', $artids))('strcasecmp');
        $cb = composer($udiff, partial('array_map', 'identity'));
        $phantoms = array_map(partial([$this, 'fetch'], 'TABLE', 'article_id'), $cb($liveids));
        $cb = function ($o) {
            $o['article_id'] = null;
            return $o;
        };
        $phantoms = array_map($cb, $phantoms);
        foreach ($phantoms as $item) {
            $this->persist($item);
        }
    }

    private function doGetUntracked()
    {
        $images = safeScanDir(ARTICLE_IMG);
        $assets = safeFilter(safeScanDir(ASSETS), fn($o) => fileExists(ASSETS . $o));
        $files = array_merge($images, $assets);
        $all = $this->findAll(\PDO::FETCH_ASSOC);
        $udiff = curry2(partial('array_udiff', $files))('strcasecmp');
        $cb = composer($udiff, partial('array_map', fn($o) => $o['path']));
        //https://stackoverflow.com/questions/1875862/php-case-insensitive-array-diff
        $files = $cb($all);
        if ($files) {
            return array_map([$this, 'getDisplayPath'], $files);
        }
        return [];
    }

    public function getuntracked($articleId = 0)
    {
        /*
        $gallery = [];
        if (preg_match('/;/', $dir)) {
            $gallery = array_map(fn($o) => GALLERY_IMG . $o, explode(';', $dir));
        }

        if ($gallery) {
            $files = array_merge($files, $gallery);
        }
            */
        $files = $this->doGetUntracked();

        if (!empty($files)) {
            return [
                'template' => 'retriever.html.php',
                'title' => "Retrieved Files",
                'variables' => [
                    'files' => $files,
                    'exit' => ASSET_MANAGE . $articleId,
                    'op' => '',
                    'dir' => ASSET_UNTRACKED
                ]
            ];
        }
        reLocate(ASSET_MANAGE . $articleId . "/untracked");
    }

    private function getSubGroups($path)
    {
        $active = $this->table->find('article_id', null, 'path', 0, 0, 2, ' IS NOT NULL');
        $archived = $this->table->find('article_id', null, 'path', 0, 0, 2, ' IS NULL');
        if (is_bool($path)) {
            return [$active, $archived];
        }
        $paths = array_map(fn($item) => $item['path'], $active);
        $orphans = array_map(fn($item) => $item['path'], $archived);
        return [in_array($path, $paths), in_array($path, $orphans)];
    }

    private function getDirectoryPath(string $path)
    {
        $pathtofile = scanMultiDir([ASSETS, VIDEOS, IMAGES], $path);
        if ($pathtofile) {
            $file = strrchr($pathtofile, '/');
            $dir = preg_replace("|$file|", '/', $pathtofile);
        }
        return $dir;
    }

    private function getDirectory(\PoloAfrica\Entity\Asset $asset)
    {
        if (isset($asset)) {
            $video = array_map(fn($o) => substr($o, 1), VIDEO_EXT); // '.mp4' to 'mp4'..
            $pp = $asset ? $asset->getArticle($asset->id, 'page') : '';
            $ext = $asset ? $this->getExtension($asset->path) : '';
            $dir = in_array($ext, $video);
            if (!$dir) {
                $dir = $ext === 'pdf' ? ASSETS . "$pp/" : ARTICLE_IMG;
            } else {
                $dir =  VIDEOS . "$pp/";
            }
            return $dir;
        }
        return ARTICLE_IMG;
    }

    private function preflight($id)
    {
        $file = $this->fetch('table', 'id', $id);

        if ($file) {
            list($assigned, $archived) = $this->getSubGroups($file->path);

            if ($assigned && $archived) {
                list($assigned, $archived) = $this->getSubGroups(true);
                $players = array_filter($assigned, fn($o) => $o['id'] == $id);
                $orphans = array_filter($archived, fn($o) => $o['id'] == $id);
                if (!empty($players)) {
                    $files = $this->table->find('path', $file->path);
                    return count($files) > 1 ? [] : $files;
                }
                if (!empty($orphans)) {
                    foreach ($orphans as $file) {
                        return $this->delete($file['id']);
                    }
                    return [];
                }
            }
            if ($assigned) { //should not be required if we enforce unique filenames
                list($assigned, $archived) = $this->getSubGroups(true);
                $players = array_filter($assigned, fn($o) => $o['path'] == $file->path);
                if (count($players) > 1) {
                    return [];
                }
            }
            return [$file];
        }
    }

    private function getArticle($asset)
    {
        $asset = $asset ? $this->persist($asset) : null;
        return $asset ? $asset->getArticle($asset->id) : null;
    }

    private function forceGetPage($values)
    {
        $file = $this->persist($values);
        $pp = $file->getArticleDirect($values['article_id'], 'page');
        $this->table->delete('id', $file->id);
        return $pp;
    }

    private function getPage($values)
    {
        $pp = '';

        $id = $values['article_id'] ?? null;
        $id = is_numeric($id) && $id > 0;
        if ($id) {
            $file = $this->fetch('table', 'article_id', $id);
            if ($file) {
                $pp = $file->getArticleDirect($values['article_id'], 'page');
            }
            if (!$pp) {
                return $this->forceGetPage($values);
            }
        }
        return $pp;
    }

    private function doGetPage($posted, $assetId, $prop = 'page')
    {
        $pp = '';
        $assetId = $assetId ? $assetId : null;
        if (!empty($assetId)) {
            $file = $this->fetch('table', 'id', $assetId);
            $pp = $file->getArticle($assetId, $prop);
        }
        if (!$assetId || !$pp) {
            return $this->getPage(['article_id' => $posted['article_id']]);
        }
        return $pp;
    }

    protected function getVariables($articleId, $asset, $routes, $select, $key, $doreplace = '', $path = '')
    {
        $filename = preg_match('/^[-.\w]+\.\w+$/', $key) ? $key : $path;
        $ourkey = preg_match('/^\w+$/', $key) ? $key : '';
        $message = $ourkey ? '' : $key;
        $key = $message ? '' : $ourkey;
        $asset = $asset ? $asset : $this->fetch('table', 'article_id', $articleId);
        $title = $asset ? $asset->getArticle($asset->id, 'title') : '';
        $pp = $asset ? $asset->getArticle($asset->id, 'page') : '';
        $dir = $asset ? $this->getDirectory($asset) : ARTICLE_IMG;
        $untracked = false;

        $gallery = $articleId < 0 ? 'gallery' : '';
        $exit = $articleId ? ARTICLES_EDIT . $articleId : ARTICLES_LIST . $pp;
        $exit = $gallery ? GAL_EDIT : $exit;

        if (!$pp) {
            $asset = $this->table->getEntity();
            $pp = $asset->getArticleDirect($articleId, 'page');
            $title = $asset->getArticleDirect($articleId, 'title');
        }

        $storedfiles = $this->fromFileStore($this->getOrphans(\PDO::FETCH_ASSOC));
        $untracked = $this->doGetUntracked();
        $manage_text = $this->getArchiveCopy($articleId, $select['options'], $untracked, $storedfiles);

        $ret = [
            'template' => 'asset.html.php',
            'title' => "Asset",
            'variables' => [
                'action' => ASSET_EDIT,
                'exit' => $exit,
                'articleId' => $articleId,
                'title' => $title,
                'page' => $pp,
                'asset' => $asset,
                'message' => $message,
                'key' => $key,
                'dir' => $dir,
                'replace' => $doreplace,
                'routes' => $routes,
                'select' => $select,
                'filename' => $filename,
                'manage_text' => $manage_text,
                'untracked' => $untracked,
                'gallery' => $gallery
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
    protected function complete($path, $loopcallback = false)
    {
        if ($path && !$loopcallback) {
            reLocate($path, '../../');
        }
    }
    protected function doDelete($id = 0, $flag = false)
    {
        $file = $this->fetch('table', 'id', $id);
        if (isset($file)) {
            $pp = $file->getArticle($id, 'page');
            list($str) = $this->breakLink($file, $pp, true);
            $file->setContent($str);
            $this->table->delete('id', $id);
            $id = $file->article_id;
            $route = $id ? ARTICLES_EDIT . $id : ARTICLES_LIST;
            //$flag: if called in a loop defer relocate for looper
            $this->complete($route, $flag);
        } else {
            reLocate(ARTICLES_LIST);
        }
    }

    protected function deleteFiles($id = 0, $backup = false)
    {
        $files = $this->preflight($id);
        if (!empty($files)) {
            foreach ($files as $f) {
                $pp = $f->getArticle($id, 'page');
                $this->loader->doUnlink($f->path, $pp, $backup);
            }
        }
    }

    protected function breakLink($file, $pp, $attr_id)
    {
        $str = $file->getArticle($file->id, 'content');
        if (isset($str)) {
            return $this->loader->breakLink($str, $pp, $file->path, $attr_id);
        }
        return [$str, ''];
    }

    protected function archive($record, $ext, $newext = '', $attr = false)
    {
        $pass = is_string($ext) ? $ext === $newext : $ext;
        $str = '';
        if (!$pass) {
            $ext = preg_match('/^jpe?g$/i', $ext);
            $pass = $ext && preg_match('/^jpe?g$/i', $newext);
        }
        if (!empty($pass) && isset($record)) {
            $file = $this->persist($record);
            $pp = $file->getArticle($file->id, 'page');
            //$attr is a boolean to indicate whether to replace PDF link copy
            //$this->breakLink is an identity function for NON-PDF files
            list($str, $title) = $this->breakLink($file, $pp, $attr);
            if (isset($str)) {
                $file->setContent($str, 'arc');
            }
            $record['article_id'] = NULL; //archiving right here; 
            $record['alt'] = $record['alt'] ? $record['alt'] : $title;
            //note attr_id could be id.class or text copy this can be used to unarchive a file whose link copy fails validation (ie phrase does not exist)
            $this->loader->cleanup($record['path']);
            return $this->persist($record);
        }
        return null;
    }

    public function update($values, $arg = '')
    {
        $this->persist($values);
    }

    protected function save($values, $pp = '', $origin = '', $cb = null)
    {
        $record = $this->loader->handleAsset($values, $pp, $origin);
        $ext = 'accept';
        $articleId = $values['article_id'];
        $path = $values['path'] ?? '';
        $id = $record['id'] ?? '';
        $location = ASSET_UPLOAD;
        $attrid = isset($record['attr_id']) ? $record['attr_id'] : '';
        //$attrid = $record['attr_id'] ?? '';
        $untrack = preg_match('/^!/', $attrid);
        $aux = '';

        if (empty($record['path'])) {
            $_ext = !empty($record['ext']);
            if ($origin = 'library') {
                $ext = $_ext ? $record['ext'] : $ext;
                reLocate("$location$articleId/0/$ext", '../../');
            }
            if (!empty($id)) {
                if ($_ext) {
                    $location = ASSET_ASSIGN;
                    $ext = $record['ext'];
                    reLocate("$location$articleId/$id/edit/$ext", '../../');
                }
                reLocate(ASSET_CONFIRM . "$id/notfound/$articleId", '../../');
            }
            if ($path && !is_numeric($path)) {
                $path = trimToLower($path);
                $ext = "Cannot find the file '<span>$path</span>' in the target directory, please check the spelling.";
                return $this->add($articleId, '', $ext);
            }
        }

        $file = $this->persist($record);
        //doReplace on passing handleAsset test, return id of replacee or null/undefined
        $replacer = getResult($cb);
        //pdfs moving page perhaps
        $file->getStatus();

        if (isset($record['article_id'])) {
            $key = 1;
            $mystr = '';
            $pdf = preg_match("|\.pdf$|", $record['path']);
            $route = preg_match('/upload/i', $origin) ? ASSET_RELOAD . $file->id : ARTICLES_EDIT . $record['article_id'];
            //pdf...
            $str = $file->getArticle($file->id, 'content');
            $selection = $record['attr_id'] ?? '';
            $selection = $selection ? $selection : ($replacer ? false : '');
            $fId = $record['id'] ?? 0;
            //we allow $selection to be empty as we may just be replacing the path to a file, not the link text
            list($mystr, $key) = $this->loader->makeLink($str, $file->path, $file->alt, $selection, $replacer);

            if ($pdf && !$key) { //failed handleAsset
                $record['article_id'] = NULL;
                $this->retireSubmit($fId);
                reLocate($route, '../../');
            }

            $file->setContent($mystr);
            $up = ASSET_UPLOAD . "$articleId/0/$key";
            $ed = ASSET_EDIT . "$fId/edit/$key";
            $route =  preg_match('/upload/i', $origin) ? $up : $ed;
            if ($untrack) {
                $this->delete($file->id);
                reLocate($route, '../../');
            }
            //clear attr_id
            $record = $this->loader->exit($file->id, $record, $mystr !== $str);
            if (!empty($record) && !empty($record['id'])) {
                $this->persist($record);
            }
        }
        $aux = $file->getArticle($file->id, 'id');
        $aux = $aux ? ARTICLES_EDIT . "$aux" : ARTICLES_LIST;
        reLocate($aux, '../../');
    }

    protected function checkPath($path, $coll = [])
    {
        $coll = empty($coll) ? $this->table->findAll(null, 0, 0, \PDO::FETCH_ASSOC) : $coll;
        $paths = array_map(fn($o) => $o['path'], $coll);
        return in_array($path, $paths);
    }

    protected function fubar($filenames)
    {
        $all = array_map(fn($o) => $o['path'], $this->table->findAll('path', 0, 0, \PDO::FETCH_ASSOC));
        return array_udiff($filenames, $all, 'strcasecmp');
    }

    protected function getOrphans($mode = \PDO::FETCH_CLASS, $flag = true, $orderBy = null)
    {
        return $this->table->filterNull('article_id', $flag, $orderBy, 0, 0, $mode);
    }
    protected function prepTemplate($articleId, $assetId = null, $op = 'Edit', $key = '')
    {
        $archived = $this->table->find('article_id', null, 'path', 0, 0, \PDO::FETCH_CLASS, ' IS NULL');
        $asset = $assetId ? $this->fetch('table', 'id', $assetId) : null;
        $asset = $asset ? $asset : $this->fetch('table', 'article_id', $articleId);
        $mimetype = $asset ? getMimeType($asset->path, [ASSETS, IMAGES]) : null;
        $cb = function ($str) {
            return function ($o) use ($str) {
                return getMimeType($o->path, [ASSETS, IMAGES]) === $str;
            };
        };
        $doreplace = preg_match('/replace/i', $op) ? 'replace' : '';
        $routeargs = isset($asset) ? "$articleId/$asset->id" : $articleId;
        //avoid adding asset->id here as we only want that post-upload
        $uploadroute = ASSET_UPLOAD . $articleId;

        //filter out non matching mimetypes as replacement candidates
        if ($op == 'replace' && $mimetype) {
            $archived = array_values(array_filter($archived, $cb($mimetype)));
        }
        $select = ['options' => $archived, 'target' => $asset->id ?? null, 'identity' => 'PATH', 'optval' => 'path'];
        $routes = ['route' => 'edit', 'add' => ASSET_ADD . $routeargs, 'upload' => $uploadroute, 'assign' => ASSET_ASSIGN . $routeargs, 'edit' => ASSET_EDIT . $routeargs];

        return $this->getVariables($articleId, $asset, $routes, $select, $key, $doreplace);
    }

    //exempt same named file from active array
    function exemptFile($filename, $id, $flag = false)
    {
        $article_assets = $this->table->find('article_id', $id);
        $paths = array_map(fn($o) => $o->path, $article_assets);
        return exemptMember($filename, $paths, $flag);
    }

    protected function allowUpload($fileName)
    {
        $myarchived = $this->getOrphans(\PDO::FETCH_ASSOC, true);
        $myactive = $this->getOrphans(\PDO::FETCH_ASSOC, false);
        $active = $this->checkPath($fileName, $myactive);
        $archived = $this->checkPath($fileName, $myarchived);
        return [$active, $archived];
    }

    //public only because as we partial apply this to conditionally run on succesfull call to handleAsset
    public function doReplace($resident, $candidate, $route, $replace = 'replace')
    {
        if ($replace) {
            $resident = toObject($resident, true);
            //siganture expects a $resident as an assoc array ['id' => 1, path' => 'my.jpg', ...];
            //$candidate can be a wrapper around a filename : ['path' => 'my.jpg'];
            list($res, $cand) = array_map(fn($o) => $o['path'], [$resident, $candidate]);
            list($old, $neu) = array_map([$this, 'getExtension'], [$res, $cand]);
            $file = $this->archive($resident, $old, $neu, !isUpperCase($replace));
            if (!$file) {
                reLocate($route);
            }
            return $file;
        }
        return null;
    }

    public function prepareValues($fileName, $origin = 'upload')
    {
        if (empty($_POST)) {
            reLocate(REG);
        }
        $replace = false;
        $relocate = false;
        $updateId = null;
        $msg = '';
        $cb = null;
        $data = $_POST['data'];
        $id = intval($data['article_id']); //$id is ARTICLE id NOT asset id
        $_msg = "$id/0/";


        $checkMimeType = composer(partial('call_user_func_array', 'equals'), partial('array_map', 'getMimeType'));
        $getMsg = negate('identity');
        $exempt = partial([$this, 'exemptFile'], $fileName, $id, false);
        $exemptNot =  partial([$this, 'exemptFile'], $fileName, $id, true);
        $reducer = curry3('array_reduce')(true)(fn($agg, $curr) => $agg && getResult($curr));
        $assetpath = $data['path'] ?? 0;
        $assetpath = intval($assetpath);

        $alt = $this->setAlt($data['alt'] ?? explode('.', $fileName)[0]);
        $values = array_map('trim', ['alt' => $alt, 'article_id' => $id, 'attr_id' => $data['attr_id'], 'date' => date('Y-m-d'), 'path' => $fileName]);

        $untrack = preg_match('/^!/', $data['attr_id'] ?? '');
        $msg = !$id && !$untrack ? "$id/0/articlenull" : $msg;

        list($active, $archived) = $this->allowUpload($fileName);
        $upload = $this->fetch('table', 'path', $fileName);
        $article_assets = $this->table->find('article_id', $id);
        //filter out assets that belong to CURRENT article
        $active = $active && $exempt();

        $msg = $reducer([$active, partial($getMsg, $msg)]) ? $_msg . 'article' : $msg;
        //if an archived file has same name as upload then UPDATE rather than INSERT
        if ($archived && isset($_POST['overwrite'])) {
            $upload = $this->fetch('table', 'path', $fileName);
            $values['id'] = $archived && $upload ? $upload->id : $updateId;
        }
        $msg = $reducer([partial($getMsg, $msg), $archived, negate(partial('identity', isset($_POST['overwrite'])))]) ? $_msg . 'overwrite' : $msg;

        if (!$msg && $assetpath) {
            $asset = $this->fetch('table', 'id', $assetpath);
            $cb = negate(
                partial($checkMimeType, [$asset->path, $fileName]),
                true
            );
            $msg = $reducer([
                partial($getMsg, $msg),
                $cb
            ]) ? $_msg . 'ext' : $msg;

            $updateId = ($fileName == $asset->path ?? '') ? $asset->id : $updateId;
            //bail out it attempting to replace a fellow article asset of the prospective file
            //ie asset has one.jpg and two.jpg you attempt to re-upload one.jpg to replace two.jpg
            $msg = $reducer([partial($getMsg, $msg), negate(partial('identity', $updateId)), partial($exemptNot, $fileName, $id)]) ? $_msg . 'sibling' : $msg;
            $replace = $exempt($fileName, $id) ? intval($assetpath) : null;
        } else {
            $asset = $this->filter($article_assets, fn($o) => $o->path == $fileName);
            $updateId = $asset ? $asset->id : $updateId;
        }
        $this->validateInsert($fileName, $replace, $id);

        if ($replace) {
            //'upload(ed)' is a flag used by pdf to enforce INITIAL link copy, not required if REPLACING the target asset;
            //ignored by none pdf files
            $origin = 'UPLOADED';
            $flag = $values['attr_id'] ? 'replace' : 'REPLACE';
            //defer function call to handleAsset has succesfully run (PDF files)
            $cb = partial([$this, 'doReplace'], $asset, ['path' => $fileName], ASSET_UPLOAD . "$id/0/ext",  $flag);
        }
        $msg = $reducer([partial($getMsg, $msg), $relocate]) ? $_msg . 'allowed' : $msg;
        if ($msg) {
            $this->preserveValues();
            reLocate(ASSET_UPLOAD . $msg, '../../');
        }
        $pp = !empty($asset) ? $asset->getArticleDirect($id, 'page') : '';
        if (empty($pp)) {
            $pp = $this->getPage($values);
        }
        $values['id'] = $updateId;
        $values['attr_id'] = preg_replace('/([\[\]\(\){}#="]+)/', '', $values['attr_id']);
        $this->save($values, $pp, $origin, $cb);
    } //prepareValues

    public function reload($assetId = 0, $key = 'reloaded')
    {
        $file = $this->fetch('table', 'id', $assetId);
        if ($file) {
            return $this->upload($file->article_id, $file->id, $key);
        } else {
            reLocate(BADMINTON);
        }
    }

    public function preserveValues()
    {
        $set = doSetCookie(true);
        $set('attrid', $_POST['data']['attr_id'] ?? '');
        $set('alt', $_POST['data']['alt'] ?? ' ');
        $set('ratio', $_POST['floats']['ratio'] ?? 0);
        $set('offset', $_POST['floats']['offset'] ?? 0.5);
        $set('appearance', $_POST['ints']['appearance'] ?? -1);
        $set('maxsize', $_POST['ints']['maxsize'] ?? 0);
    }

    public function upload($articleId = 0, $assetId = 0, $key = '')
    {
        //potentially rerouted from gallery/manage
        if ($articleId <= 0) {
            reLocate(ARTICLES_LIST, '../../');
        }
        //'$key = reloaded on upload, and 'reload' on viewing a preview image
        $reloaded = preg_match('/reload/', $key);
        $file = $this->fetch('table', 'id', $assetId); //array

        if ($file && $reloaded) {
            /*ensure only LATEST file on reload otherwise filter $info below so that the info array matches $file above*/
            $files = [$file];
        } else {
            $files = $this->table->find('article_id', $articleId/*, null, 0, 0, \PDO::FETCH_ASSOC*/);
        }
        $file = empty($file) ? (empty($files[0]) ? [] : $files[0]) : $file;

        $proxy = $this->table->getEntity();
        $title = $proxy->getArticleDirect($articleId, 'title');

        $info = array_map(function ($o) {
            list(, $max, $ratio) = $this->getOrientation($o->path);
            return ['ratio' => $ratio, 'max' => $max];
        }, $files);

        //force call omitguide to hide upload_guide href=asset/upload/$articleId/omitguide
        $omit = $assetId === 'omitguide' ? ' sansguide' : null;
        $omit = $assetId && is_numeric($assetId) ? ' sansguide' : $omit;
        $omit = $omit ? $omit : (isset($_COOKIE['upload_guide']) ?  ' sansguide' : '');

        $previewklas = !empty($file->article_id) ? 'pic' : '';
        $previewklas .= $omit;
        if ($reloaded) {
            $previewklas .= ' slide';
        }
        $file = $file ? toObject($file, true) : [];
        $archived = $this->getOrphans();
        if ($key === 'assetfree' && !empty($archived)) {
            reLocate(ASSET_ASSIGN . $articleId . "///assetfreearchive");
        }

        $all = $this->findAll(\PDO::FETCH_ASSOC);
        $manage_text = $this->getArchiveCopy($articleId, $archived);
        $_alt = $_COOKIE['alt'] ?? '';
        $_attrid = $_COOKIE['attrid'] ?? '';
        $a = $_COOKIE['appearance'] ?? '';
        $b = $_COOKIE['maxsize'] ?? '';
        $c = $_COOKIE['offset'] ?? '';
        $d = $_COOKIE['ratio'] ?? '';
        $map = doSetCookie(false);
        array_map($map, ['alt', 'attrid', 'maxsize', 'offset', 'ratio', 'appearance']);
        $ret = [
            'template' => 'upload.html.php',
            'variables' => [
                'action' => ASSET_ON_UPLOAD . $articleId,
                'exit' => $articleId ? ARTICLES_EDIT . $articleId : ARTICLES_LIST,
                'accept' => $this->accept,
                'fileinputname' => 'uploadfile', //id of file input
                'warning' => '',
                'key' => $key,
                '_alt' => $_alt,
                '_attrid' => $_attrid,
                '_appearance' => $a,
                '_maxsize' => $b,
                '_offset' => $c,
                '_ratio' => $d,
                'articleId' => $articleId,
                'previewklas' => $previewklas,
                'files' => $files,
                'info' => $info,
                'mytitle' => $title,
                'omitguide' => $omit,
                'reloaded' => $reloaded,
                'page' => $this->getPage($file), //values required!!
                'archived' => $archived,
                'manage_text' => $manage_text,
                'assetId' => $assetId && is_numeric($assetId) ? $assetId : null,
                'ratio' => $this->ratio,
                'exit_guide' => ASSET_UPLOAD . $articleId . '/omitguide',
                'routes' => ['add' => ASSET_ADD . $articleId, 'assign' => ASSET_ASSIGN . $articleId, 'edit' => ASSET_EDIT . $articleId, 'upload' => ASSET_UPLOAD . $articleId, 'route' => 'upload'],
                'select' => ['options' => $files, 'identity' => 'PATH', 'optval' => 'path', 'target' => null, 'disabled' => false]
            ]
        ];
        if (isset($_SESSION['filestore'])) {
            $untracked = $this->doGetUntracked();
            $storedfiles = $this->fromFileStore($all);
            $ret['variables'] = array_merge($ret['variables'], [
                'super' => true,
                'untracked' => $untracked,
                'filestore' => $storedfiles,
                'manage_text' => $this->getArchiveCopy($articleId, $archived, $untracked, $storedfiles)
            ]);
        }
        return $ret;
    }
    public function add($articleId = 0, $origin = 'add', $key = '', $path = '')
    {
        /*
          obtain $asset in order to obtain $article info; default is the first (usually only) $asset after which it can be set to null UNLESS the intention is to replace in which case it is a SIGNAL to provide the primary key in the form
          */
        $articleId = intval($articleId);
        $asset = $this->fetch('TABLE', 'article_id', $articleId);
        /*
          $origin can be a string corresponding to a db modification (add,edit) or an asset_id
          we reach this point either by directly clicking on an edit link or redirected from the upload form in which case we cannot know which asset we are dealing with
          */
        $asset = is_numeric($origin) ? $this->fetch('table', 'id', $origin) : null;
        $freetext = $articleId ? ASSET_ADD . $articleId : ASSET_ADD;
        $origin = $asset ?  "/$asset->id" : '';
        $doreplace = preg_match('/replace/i', $origin) ? 'replace' : '';
        $select = ['options' => [], 'target' => null, 'identity' => 'PATH'];
        $routes = ['route' => 'add', 'upload' => ASSET_UPLOAD . $articleId, 'add' => "$freetext$origin"];
        return $this->getVariables($articleId, $asset, $routes, $select, $key, $doreplace, $path);
    }

    public function assign($articleId, $assetId = null, $op = "edit", $key = '')
    {
        if (!$articleId || $op === 'free') {
            return $this->add($articleId, 'add');
        }
        return $this->prepTemplate($articleId, $assetId, $op, $key);
    }

    public function confirm($id = 0, $perform = 'archive', $articleId = 0)
    {
        $file = $this->fetch('table', 'id', $id);
        $fId = $file->article_id ?? 0;
        $exit = $perform === 'notfound' ? ARTICLES_EDIT . $articleId : ASSETS_EDIT . $fId;
        list($_, $archived) = $this->getSubGroups(true);
        $lookup = ['archive' => ASSET_RETIRE, 'delete' => ASSET_DESTROY, 'replace' => ASSET_REPLACE, 'notfound' => ASSET_DESTROY];
        if (isset($file)) {
            return [
                'template' => 'archive.html.php',
                'variables' => [
                    'action' => $lookup[$perform] ?? '',
                    'exit' => $exit,
                    'submit' => $perform === 'notfound' ? 'delete' : $perform,
                    'identity' => 'archive',
                    'replace' => empty($archived) ? '' : ASSET_REPLACE,
                    'confirm' => ASSET_CONFIRM,
                    'perform' => $perform,
                    'file' => $file
                ]
            ];
        } else {
            reLocate(ARTICLES_LIST, '../../');
        }
    }
    /*
    replace can be invoked through a one button form (see immediately above), "action=/asset/replace/id"
    we can't use "action=/asset/edit/id" else it would invoke editSubmit
    so this function is just a bridge/adpater
    */
    public function replace($id = 0)
    {
        list($_, $archived) = $this->getSubGroups(true);
        $file = $this->fetch('table', 'id', $id);
        $ext = $this->getExtension($file->path);

        $cb = function ($item) use ($ext) {
            $current = $this->getExtension($item['path']);
            $res = $current === $ext;
            return $res ? $res : preg_match('/jpe?g/', $current);
        };
        $_archived = safeFilter($archived, $cb);
        $msg = count($archived) !== count($_archived) ? 'edit/exts' : '';
        if (empty($_archived)) {
            reLocate(ASSET_EDIT . "$id/$msg", '../../');
        }
        return $this->edit($id, 'replace');
    }

    public function edit($id = 0, $op = 'edit', $key = 0)
    {
        $asset = $this->fetch('table', 'id', $id);
        if (!empty($asset) && $asset->article_id) {
            return $this->prepTemplate($asset->article_id, $id, $op, $key);
        } else {
            reLocate(BADMINTON);
        }
    }
    /*
    it is an edge case but it is possible to end up at the upload form without an articleId
    so several safeguards must be in place, in any event an image must be associated with an article
    unless designating as an inline image by putting a ! at the begiining of the meta_data field
    */
    private function validateInsert($filename, $replace, $articleId = 0)
    {
        $x = getMimeType($filename);
        $teststring = curry3('strrchr')(true)('/');
        $x = $x ? $teststring($x) : '';
        $y = '';
        $assets = [];
        $asset = null;
        $klas = 'landscape';
        $res = null;
        $path = normalizePath('identity', ARTICLE_IMG . $filename);

        if (fileExists($path)) {
            try {
                $image = new Image();
                list($w, $h) = $image->getDims($path);
                $klas = $h > $w ? 'portrait' : 'landscape';
            } catch (\Exception $e) {
            }
        }

        if ($x === 'application') { //no limit to pdf files
            return false;
        }
        $e = $this->table->getEntity();
        $e = $e->getArticleDirect($articleId);

        if ($e) {
            $assets = $e->getAssets($articleId);
            $asset = $assets[0] ?? null;
            $y = $asset ? getMimeType($asset->path) : '';
            $y = $y ? $teststring($y) : '';
        }

        if ($y && $x !== $y) {
            reLocate(ARTICLES_EDIT . "$articleId/ext", '../../');
        }
        if ($e && !$replace) {
            if (count($assets) === $e->validateInsert($klas)) {
                reLocate(ARTICLES_EDIT . "$articleId/insert", '../../');
            }
            $res = findMatch('/^(\w+)\.section$/', $e->attr_id, 1);
        }

        if ($res) {
            $res = array_map(fn($item) => $item->title, $e->find('table', 'attr_id', $e->attr_id));
        }
        if ($res && !$e->isLeadingArticle($e->page, $e->title, $res)) {
            reLocate(ARTICLES_EDIT . "$articleId/allowed", '../../');
        }
        return false;
    }

    protected function sortAttributes($subject, $posted, $resident)
    {
        $subject = $subject ?? $resident;
        unset($posted['path']);
        if (!empty($resident)) {
            unset($resident['article_id']);
            //will set attr_id/alt to empty if same values are present           
            $posted = array_diff($posted, $resident);
        }
        $record = array_merge($subject, $posted);
        return $this->doTrim($record, ['alt', 'attr_id', 'article_id']);
    }

    protected function fetchCandidate($path)
    {
        if ($path) {
            $candidate = $path ? $this->fetch('TABLE', 'path', $path) : [];
            if (empty($candidate)) {
                $path = $this->swapJpeg($path);
                return $path ? $this->fetch('TABLE', 'path', $path) : null;
            } else {
                return $candidate;
            }
            return null;
        }
    }
    //https://stackoverflow.com/questions/9684600/unlink-files-with-a-case-insensitive-glob-like-pattern
    protected function wipe($path, $article, $alt)
    {
        if (preg_match("/^photo/i", $article) || preg_match("/^gallery/i", $article)) {
            reLocate(GAL_TRANSIT . $path . "/$alt");
        }
        if ($path !== $article) {
            return false;
        }
        //issue deleting untracked gallery files, should never be a thing
        $pathtofile = fileExists(scanMultiDir([ASSETS, IMAGES, VIDEOS, GALLERY, RESOURCES], $path));
        $e = $this->table->getEntity();
        $articles = $e->findArticles();
        $cb = function ($agg, $curr) use ($path) {
            return $agg ? $agg : (preg_match("/$path/", $curr->content) ? $curr->id : '');
        };
        $articleId = array_reduce($articles, $cb, false);
        $path = strtolower($path);
        if ($articleId) {
            reLocate(ARTICLES_EDIT . "$articleId/referenced_$path/");
        }

        if ($pathtofile) {
            $dirPath = $this->loader->getDirFromPath($path);
            if (is_numeric(stripos($pathtofile, $dirPath))) {
                $files = scandir($dirPath);
                $file = current(preg_grep("/$path/i", $files));
                $unlink = doWhen('fileExists', 'unlink');
                $unlink("$dirPath$file");
            } else {
                unlink($pathtofile);
            }
        }
        return true;
    }

    public function editSubmit()
    {
        if (empty($_POST)) {
            reLocate(REG);
        }

        $record = null;
        $noarchived = ($_POST['orphans'] == 0);
        $candidate = [];
        $cb = always(NULL);
        $id = $_POST['pk'] ?? null;
        $data = $_POST['data'];
        $assign = isset($_POST['assign']) ? trim($_POST['assign']) : NULL;
        $articleId = $data['article_id'] ?? null;
        $article = ['article_id' => $articleId];
        $resident = $this->fetch('TABLE', 'id', $id) ?? [];
        $path = isset($data['path']) ? $data['path'] : '';
        $orphan = $this->fetch('TABLE', 'id', $path);
        $candidate = $this->fetchCandidate($path);
        $archived = $orphan ? 'archived' : 'library';
        $replace = isset($_POST['replace']) && $orphan ? intval($id) : false;
        $replace = isset($_POST['replace']) && $orphan ? 'replace' : false;
        $record = $this->sortAttributes($orphan ?? $candidate, $data, $resident);

        if ($articleId && isset($record['path'])) {
            $this->validateInsert($record['path'], $replace, $articleId);
        }
        $archived = 'uploaded';
        if ($orphan) {
            $id = isset($resident['id']) ? $resident['id'] : 0;
            $candidate = null;
            $replace = $replace && empty($record['attr_id']) ? 'REPLACE' : $replace;
            $cb = partial([$this, 'doReplace'], $resident, $record, ASSET_EDIT . "$id/edit/ext", $replace);
        }

        if (isset($candidate)) {
            $id = isset($resident['id']) ? $resident['id'] : 0;

            if ($candidate['id'] !== $id) {
                $articleId = isset($resident['article_id']) ?  $resident['article_id'] : 0;
                $articleId =  $articleId ? $articleId : $candidate['article_id'];
                $id = $id ? $id : $candidate['id'];
                $key = $articleId === $candidate['article_id'] ? 'articleself' : 'articleother';
                /*may have an article_id that no longer belongs to an article,
                and (edge case) may have a file with the same name as that belonging to an existing article which we are attempting to RETRACK to gallery
                */
                if ($candidate['article_id'] && $assign !== 'gallery') {
                    reLocate(ASSET_EDIT . "$id/edit/$key", '../../');
                }
            }
            $article = isset($assign) ? $article : [];
            $record['article_id'] = isset($assign) ? $articleId : null;
        }

        if ($assign) {
            if (!is_numeric($assign)) {
                //no orphan; candidate or resident in this scenario where we are reinstating a file
                if (isset($data['path'])) { //super only
                    if (!$this->wipe($data['path'], $assign, $data['alt'])) {
                        $file = $this->table->getEntity();
                        $id = $file->getArticleDirect($assign, 'id');
                        $path = $data['path'];
                        if (!$id) {
                            reLocate(ASSET_ADD . "0/add/fetch/$path", '../../');
                        }
                        $article['article_id'] = $id ? $id : null;
                        $this->validateInsert($path, null, $id);
                        $record = $this->sortAttributes($orphan ?? $candidate, $data, $resident);
                        $record['path'] = $data['path'] ?? '';
                        $record['article_id'] = $id;
                        $archived = 'assigned';
                    } else {
                        reLocate(ASSET_MANAGE . $articleId);
                    }
                }
            }
        }

        if ($noarchived) {
            $article = isset($assign) ? $article : [];
            $record['article_id'] = isset($assign) ? $articleId : null;
        }
        $record['date'] = $record['date'] ?? date('Y-m-d');
        $values = array_merge($record, $article);
        $pp = $this->doGetPage($values, $values['id'] ?? '');
        if ($pp) {
            return $this->save($values, $pp, $archived, $cb);
        } else {
            reLocate(BADMINTON);
        }
    } //editSubmit
    //not used??
    public function getAsset($id = 0)
    {
        return $this->fetch('table', 'id', $id);
    }
    //keep asset in db remove ref to article; action ("asset/retire/") passed to delete form
    public function retireSubmit($id = 0)
    {
        $asset = $this->fetch('TABLE', 'id', $id);
        if ($asset) {
            $this->archive($asset, true, null, true);
            reLocate(ARTICLES_EDIT . $asset['article_id'], '../../');
        }
        reLocate(ARTICLES_LIST, '../../');
    }

    public function manage($articleId = 0, $key = '')
    {
        $all = $this->findAll(\PDO::FETCH_ASSOC);
        $articleId = intval($articleId);
        $this->getPhantoms($all);
        $paths = array_map(fn($o) => $o['path'], $all);
        $route = ASSET_UPLOAD . $articleId;
        //$key would be an asset id and indicates to redirect to article not upload form
        if (is_numeric($key)) {
            $route = ARTICLES_EDIT . $articleId;
            $key = '';
        }

        $ret = [
            'template' => 'orphans.html.php',
            'variables' => [
                'group' => $this->getOrphans(),
                //'id' => 'article_id',
                'articleId' => $articleId,
                'exit' => ['href' => $route, 'txt' => 'Back To Upload'],
                'dir' => ARTICLE_IMG,
                'action' =>  $this->getClassName('/'),
                'allpaths' => $paths,
                'key' => $key
            ]
        ];

        if (isset($_SESSION['filestore'])) {
            $ret['variables'] = array_merge($ret['variables'], [
                'super' => true,
                'untracked' => $this->doGetUntracked(),
                'filestore' => $this->fromFileStore($all, true)
            ]);
        }
        return $ret;
    }
    public function metadata($articleId = 0)
    {
        return [
            'template' => 'meta_data.html.php',
            'variables' => [
                'exit' => ASSET_UPLOAD . $articleId,
                'articleId' => $articleId
            ]
        ];
    }
    public function manageSubmit()
    {
        if (!empty($_POST)) {
            $backup = isset($_POST['backup']);
            $archived = isset($_POST['all']) ? $this->getOrphans() : [];
            $mapped = array_map(fn($o) => $o->id, $archived);
            $pics = isset($_POST['pics']) ? $_POST['pics'] : $mapped;
            //remove all but selected...
            if (isset($_POST['all']) && isset($_POST['pics'])) {
                $pics = array_diff($mapped, $pics);
            }
            foreach ($pics as $k) {
                $pic = $this->fetch('table', 'id', $k);
                if ($pic && isset($pic->path)) {
                    $this->destroy($k, true, $backup);
                }
            }
            if (empty($pics) && $backup) {
                $filepaths = $this->fromFileStore($this->getOrphans(\PDO::FETCH_ASSOC), true);
                $doRemove = doWhen('fileExists', 'unlink');
                $filepaths = array_map($doRemove, $filepaths);
            }
        }
        reLocate(ARTICLES_LIST, '../../');
    }
}
