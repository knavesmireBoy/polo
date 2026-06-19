<?php

namespace PoloAfrica\Controllers;

//include_once 'config.php';

use \Ninja\DatabaseTable;

class Article
{
    private $count = 0;
    private $paginate = [];
    public $mdcontent = '';
    private $assets = '';

    protected function fetch($table, $prop, $val, ...$rest)
    {
        $ret = [];
        if ($val) { //safeguard against missing values
            if (strtoupper($table) === $table) {
                $table = strtolower($table);
                $ret = $this->{$table}->find($prop, $val, null, 0, 0, \PDO::FETCH_ASSOC);
            } else {
                $ret = $this->{$table}->find($prop, $val, ...$rest);
            }
        }
        return empty($ret) ? null : $ret[0];
    }

    protected function maxArticleByPage($agg, $col, $mode)
    {
        $res = $this->table->count($agg, $col, $mode);
        $res = array_map(function ($a) {
            return $a[0];
        }, $res);
        return max($res);
    }

    protected function persist($record, $route = 'edit')
    {
        $unset = doSetCookie(false);
        $set = doSetCookie(true);
        try {
            $unset('error');
            $e = $this->table->save($record);
        } catch (\Exception $e) {
            $msg = 'Error saving record: ' . $e->getMessage();
            $set('error', $msg);
            $id = $record['id'] ?? '';
            reLocate(ARTICLES_EDIT . $id);
        }
        return $e;
    }

    protected function setCount($files)
    {
        $this->count = is_array($files) ? count($files) : 0;
    }

    protected function setInc($inc = 10)
    {
        $this->inc = $inc;
    }

    protected function validateFolio()
    {
        return ceil($this->count / $this->inc);
    }

    protected function listByPage($str, $limit = 0, $offset = 0)
    {
        return $this->table->find('page', $str, 'title', $limit, $offset);
    }

    protected function confirmAction($perform)
    {
        if ($perform === 'unarchive') {
            return ARTICLES_RESTORE;
        }
        $action = $perform === 'archive' ? ARTICLES_RETIRE : ARTICLES_DEL;
        return $perform === 'destroy' ? ARTICLES_DESTROY : $action;
    }

    protected function getInlineWordLimit($copy)
    {
        $limit = substr($copy, 0, 1) === '!' ? 12 : 9;
        //first char will be a space for most phrases, trim
        $res = preg_match_all('/\s/', trim($copy));
        return $res > $limit;
    }

    protected function getInlineContent($content, $id, $regex)
    {
        $i = 0;
        $fail = false;
        while (isset($regex[$i]) && !$fail) {
            $reg = $regex[$i];
            preg_match_all($reg, $content, $m);
            $j = 0;
            $k = 0;
            $limit = false;
            $m =  $m ? $m[0] : [];
            while (isset($m[$j])) {
                $k = preg_match_all('/\n/', $m[$j]);
                $limit = $this->getInlineWordLimit($m[$j]);
                if ($k || $limit) {
                    $fail = $k ? 'linebreak' : 'wordcount';
                    break;
                }
                $j++;
            }
            $i++;
        }

        if ($fail) {
            reLocate(ARTICLES_EDIT . $id . '/' . $fail);
        }
        //preserves two space followed by a new line, but zaps multiple spaces
        $content = trim(preg_replace('/(?<=\s) +(?=\S)/', '', $content));
        $content = preg_replace('/(?<=\n)\n+(?=\n)/', '', $content);
        return $content;
    }

    protected function wordsmith($grp, $words)
    {
        $i = 0;
        $msg = '';
        $msgs = [];
        $predicates = [];
        $root = [];
        $branch = [];
        $count = count($words);
        while (isset($grp[$i])) {
            if (is_numeric($grp[$i][2])) {
                $branch[] = $grp[$i];
            } else {
                $root[] = $grp[$i];
            }
            $i++;
        }
        $i = 0;
        while (isset($root[$i])) {
            $msgs[] = $root[$i][0];
            $predicates[] = $root[$i][1];
            $i++;
        }
        $i = 0;
        while (isset($msgs[$i])) {
            if ($predicates[$i]($count)) {
                $msg = $msgs[$i];
                break;
            }
            $i++;
        }
        if ($msg) {
            return $msg;
        }
        $i = 0;
        $msgs = [];
        $predicates = [];
        $limits = [];
        while (isset($branch[$i])) {
            $msgs[] = $branch[$i][0];
            $predicates[] = $branch[$i][1];
            $limits[] = $branch[$i][2];
            $i++;
        }
        $i = 0;
        $msg = '';
        while (isset($msgs[$i])) {
            $j = $limits[$i];
            if (!$msg) {
                foreach ($words as $w) {
                    if ($predicates[$i]($w)) {
                        $j--;
                    }
                    if (!$j) {
                        $msg = $msgs[$i];
                        break;
                    }
                }
            }
            $i++;
        }
        if ($msg) {
            return $msg;
        }
        $content = implode(' ', $words);
        $doFindMatch = curry3('findMatch')(-11)($content);
        list($word) = $doFindMatch("/[\w\s]/");
        list($noword) = $doFindMatch("/\W/");
        $counted = array_map('count', [$noword, $word]);
        $doCount = partial('greaterThan', ...$counted);
        if ($doCount()) {
            return '/suspect';
        }
        $res = $this->checkRepeating($words, 2, ['#', '\n']);
        if ($res !== []) {
            return is_bool(current($res)) ? '/repeat' : '/dollar';
        }
    }

    protected function checkSuspectCopy($content, $id, $preds)
    {
        $msg = '';
        $content = spam_scrubber($content);
        $_content = '';
        if (!$content) {
            $msg = '/dirty';
        }
        if (!$msg) {
            $_content = preg_replace('/\([^)]+\)/', '', $content);
            $_content = preg_replace('/{[^}]+}/', '', $_content);
            $_content = preg_replace('/\s\s(\s)/', '$1', $_content);
            $words = explode(' ', $_content);
            $words = array_map('trim', $words);
            $msg = $this->checkRepeating($words, 7); //#

            if ($msg !== []) {
                $msg = is_bool(current($msg)) ? '/repeat' : '/dollar';
            } else {
                $words = array_filter($words, function ($w) {
                    $a = strpos($w, '/');
                    $b = strpos($w, '[');
                    $c = strpos($w, ';');
                    return !is_int($a) && !is_int($b) && !is_int($c);
                });
                $msg = $msg ? $msg : $this->wordsmith($preds, $words);
            }
        }
        if ($msg) {
            reLocate(ARTICLES_EDIT . $id . $msg);
        }
        /*
        checks for suspicious consecutive nonwords; but forgot about file names
        $reg = '/\w(?:\])?+(?(1)(\W{8,})\w|(\W{4,})\w)/';
        $res = findMatch($reg, $content, -11);
        $res = !empty($res[0]) ? $res[0] : [];
        $res = array_map(fn($str) => preg_replace('/[\w\n\r\s\][}()#\.]/', '', $str), $res);
        $res = safeFilter($res, 'identity');
        $res = isset($res[0]) && strlen($res[0]) > 1;
        if ($res) {
            reLocate(ARTICLES_EDIT . $id . '/nonword');
        }
            */
        return $content;
    }

    protected function checkTitleAttribute($str, $id)
    {
        $suspect = findMatch('~\][:(]?\s?[\w+/+]+ [^"{]+~i', $str, -11);
        $strict = findMatch('~/(\w[^./]+\.pdf)(?:\s"(\w[^"]+)"|)(?:\s{[^}]+}|)~i', $str, -11);
        $loose = findMatch('~/(\w[^./]+\.pdf)(?:\s("[^"]*")|)(?:\s{[^}]+}|)~i', $str, -11);
        $keys = array_keys(array_filter($loose[2], fn($str) => preg_match('/^"\s?"$/', $str)));
        $caught = [];
        $i = 0;
        if ($suspect[0] !== []) {
            // reLocate(ARTICLES_EDIT . $id . '/linktitle');
        }
        //guard against empty titles "" | " " and reset asset accordingly
        while (isset($keys[$i])) {
            $caught[] = $strict[1][$keys[$i]];
            $i++;
        }
        $i = 0;
        while (isset($caught[$i])) {
            $str = preg_replace("/($caught[$i])[^{]+/", "$1 ", $str);
            $i++;
        }

        $strict = findMatch('~/(\w[^./]+\.pdf)(?:\s"(\w[^"]+)"|)(?:\s{[^}]+}|)~i', $str, -11);
        $links = safeFilter($strict[1], fn($path) => !in_array($path, $caught));
        $titles = safeFilter($strict[2], fn($path) => !in_array($path, $caught));

        $links = $strict[1] ?? [];
        $titles = $strict[2] ?? [];
        $titles = safeFilter($titles, fn($title) => strlen($title) < 64);
        $article = $this->fetch('table', 'id', $id);
        $cb = function ($asset) use ($links, $titles, &$str) {
            $i = '';
            if ($links !== []) {
                $i = array_find_key($links, function ($path) use ($asset) {
                    return $path === $asset->path;
                });
            }

            $t = !empty($titles[$i]);
            if (isset($i) && is_int($i)) {
                //if there is a current title we are allowed to update it update but not create
                if ($asset->alt) {
                    $asset->alt = $t ? $titles[$i] : '';
                } else if ($t) { //else to maintain sync with the file remove title
                    $t = $titles[$i];
                    $path = $asset->path;
                    $reg = "/($path) \"$t\"/";
                    $str = preg_replace($reg, '$1', $str);
                }
            }
            return toObject($asset, true);
        };
        //new article won't be found
        if ($article) {
            $article->setAsset($id, partial('array_map', $cb), 'alt');
        }
        return $str;
    }

    protected function checkInlineFormatting($content, $id)
    {
        $live = [];
        $build = function (&$ret, $arg) {
            $ret[] = $arg;
        };
        $soShiftDefer = function (&$a) {
            return function () use (&$a) {
                return array_shift($a);
            };
        };

        //https://stackoverflow.com/questions/4489551/what-is-double-plus-in-regular-expressions
        //allow a few self closing tags to pass validation;
        //https://stackoverflow.com/questions/57120694/markdown-1-not-working-inside-the-p-tag
        $tags = "!,!--,hr,img,input.meta,link,br,br/,abbr,acronym,audio,b,bdi,bdo,big,button,canvas,cite,code,data,datalist,del,dfn,em,i,iframe,ins,kbd,level,map,mark,meter,noscript,object,output,picture,progress,q,ruby,s,samp,script,select,slot,small,span,strong,sub,sup,svg,template,textarea,time,u,var,video";
        //<nav markdown="1">
        $blocktags = "div,nav";
        $doFindMatch = curry3('findMatch')(-11)($content);
        $getter = fn(&$o, $p) => $o[$p];
        $isEmpty = partial('equals', []);
        $notEmpty = negate($isEmpty);
        $inValid = function ($tags, $tag) use ($notEmpty) {
            if (isset($tag[0]) && preg_match('/^!--/', $tag[0])) {
                return false;
            }
            return $notEmpty($tag) && !in_array($tag[0], $tags);
        };
        $checkTagStyled = function ($tag) use ($notEmpty) {
            if ($notEmpty($tag)) {
                return [explode(' ', $tag[0])[0]];
            }
            return $tag;
        };
        /*
        "|\s<(\w+)[^>]*>[^<]+\S</\\1>\s|"
        the above regex has backreferences to check tags balance, kept for reference
        BUT we are checking for deliberate violations and when found $getZero populates the $live array whilst shifting from the myerrors array
        */
        $validate = partial($inValid, explode(',', "$tags$blocktags"));
        $doDiff = composer($notEmpty, 'arrayDiff');

        $errors = ['/mismatch', '/superbold', '/badendtag', '/tagmatch', '/nosuchtag', '/spanspace', '/attrspace', '/illegal', '/spandigits', '/endtag', '/starttag', '/swaptag', '/brokentag', '/brokentag'];
        $buildErrors = curry2ByRefDefer($build)($live);
        $myerrors = array_map($buildErrors, $errors);
        $soShift = $soShiftDefer($myerrors);
        $getZero = composer('invokeOnly', curry22Ref($getter)(0)($myerrors));
        $whenDiff = composer($soShift, doWhen($doDiff, $getZero));

        $whenNotEmpty = composer($soShift, doWhen($notEmpty, $getZero)); //expects array
        $onInvalid = composer($soShift, doWhen($validate, $getZero));
        $doTag = negate(partial('equals', '>'));
        $isTag = composer($soShift, curry2($getZero), 'always', doWhen($notEmpty, $doTag));
        list($context, $start, $end) = $doFindMatch("/(\*+)[^*]+(\*+)/");

        $fstart = safeFilter($start, fn($char) => strlen($char) < 4);
        $counted = array_map('count', [$start, $fstart]);
        $doCount = negate(partial('equals', ...$counted));

        $unEqual = composer($soShift, doWhen($doCount, $getZero));
        $whenDiff($start, $end); //mismatch
        $unEqual(); //superbold

        $res = $doFindMatch("/\s<(\w+)\s?([^>]*)>([^>]+)<(\W)(\w+)>\s/");
        list($context, $start, $attr, $text, $slash, $end) = safeList($res, 6);

        if (!empty($context)) {
            $res = ($slash[0] === '/') ? [] : ['/'];
            $whenNotEmpty($res); //
            $whenDiff($start, $end); //tagmatch
            $onInvalid($start); //nosuchtag !! expects array??
            //issue with list in nav...
            if (!is_int(strpos($blocktags, $start[0]))) {
                $res = findMatch('/(?:^\s.+|.+\s$)/', $text[0]); //spanspace
                $res = $res ? $res : [];
                $whenNotEmpty($res); //

                $res = findMatch('/(?:^\s.+|.+\s$)/', $attr[0]); //attrspace
                $res = $res ? $res : [];
                $whenNotEmpty($res); //

                $res = findMatch('/[^\w=:;\s\-"]+/', $attr[0]); //illegal, characters
                $res = $res ? $res : [];
                $whenNotEmpty($res); //

                $res = findMatch('/\d/', $attr[0]); //no digits we do not want font-size:100em
                $res = $res ? $res : [];
                $whenNotEmpty($res); //
            }

            list($context) = $doFindMatch("/<([^>]+)>[^<]+<(\w+)>/");
            $whenNotEmpty($context); //endtag
            list($context) = $doFindMatch("/<\/([^>]+)>[^<]+<\/(\w+)>/");
            $whenNotEmpty($context); //starttag
            list($context) = $doFindMatch("/<\/([^>]+)>[^<]+<(\w+)>/");
            $whenNotEmpty($context); //swaptag
            list($context, $start, $end) = $doFindMatch("/<[^>]+(\W)[^<]+<\/\w+(\W)/");
            $start = $checkTagStyled($start);
            $isTag($start); //brokentag
            $isTag($end); //brokentag
        }
        if ($notEmpty($live)) {
            $err = current($live);
            reLocate(ARTICLES_EDIT . $id . $err);
        }
        return $content;
    }
    protected function getAccess($i)
    {
        //2 'Content Editors' //4 'Photo Editors' 
        $lib = [1 => 'Registered Users', 2 => 'Content Editors', 4 => 'Photo Editors'];
        return isset($lib[$i]) ? $lib[$i] : 'Account Administrators';
    }

    protected function checkRepeating($words, $i, $exceptions = [])
    {
        $cb = function ($t) {
            //allows for stripping potential currency symbol
            return $t && is_numeric($t) || $t && is_numeric(substr($t, 1));
        };

        $f = curry2(partial('array_filter'))($cb);
        $nums = $f($words);
        $words = arrayDiff($words, $nums);

        if (!empty($nums)) {
            $f = curry2(partial('array_filter'))(fn($t) => strlen($t) >= 10);
            $nums = $f($nums);
            if (!empty($nums)) {
                return $nums;
            }
        }
        $repeat = curry3('maxRepeating')($exceptions)($i);
        $map = curry2('array_map')($words);
        $negate = fn($t) => $t && is_bool($t);
        $f = curry2(partial('array_filter'))($negate);
        $res = composer($f, $map)($repeat);
        return $res;
    }

    protected function doPaginate()
    {
        $p = [];
        $tmp = [];
        $inc = $this->inc;
        for ($i = 1, $s = 0; $s < $this->count; $s += $inc) {
            $tmp[] = $i;
            $tmp[] = $s;
            $p[] = $tmp;
            $tmp = [];
            $i++;
        }
        return $p;
    }

    protected function repop($entity, $payload)
    {
        if ($entity) {
            $entity->setName($payload['page']);
            $titles = $entity->findAll('id');
            $titles = array_column($titles, 'title');
            $entity->repop(array_merge($titles, [$payload['title']]));
        }
    }

    protected function getPrev($pp)
    {
        $prev = $pp - 1;
        return $prev > 0 ? $prev : null;
    }

    protected function getNext($pp)
    {
        $next = $pp + 1;
        return $next <= intval($this->validateFolio()) ? $next : null;
    }

    protected function uniq($array)
    {
        $uniq = array_unique($array);
        return array_filter($uniq, fn($o) => $o);
    }

    protected function unsetPage($page)
    {
        $pass = is_bool($page);
        $cookie =  isset($_COOKIE['page']) ?  $page === $_COOKIE['page'] : null;
        $unset = doSetCookie(false);

        if ($pass || $cookie) {
            $unset('page');
        }
    }
    //https://stackoverflow.com/questions/24662580/php-setcookie-not-working
    protected function setCookie($k, $flag = false, $v = '', $time = -1)
    {
        $v = $v ? $v : $k;
        if (!isset($_COOKIE[$k]) && $flag) {
            setcookie($k, $v, $time, '/');
            $_COOKIE[$k] = $v;
        } elseif (isset($_COOKIE[$k]) && !$flag) {
            unset($_COOKIE[$k]);
            setcookie($k, '', -1, '/');
        }
    }
    //list of page
    protected function getPageList($active = false)
    {
        $articles = $this->table->findAll(null, 0, 0, \PDO::FETCH_ASSOC);
        $slot = $this->persist($articles[0]);
        $slot->setName('pp');
        $articles = array_map(fn($o) => $o['page'], $articles);
        $pages = array_map(fn($o) => $o->title, $slot->findAll('id'));
        if ($active && is_bool($active)) {
            $articles = $this->uniq($articles);
            list($pp) = getDiff($pages, $articles);
            $pages = empty($pp) ? $pages : $articles;
        }

        $pp = [];
        foreach ($pages as $p) {
            $tmp = [];
            if ($p === $active) {
                continue;
            }
            $tmp['id'] = $p;
            $tmp['page'] = ucfirst($p);
            $pp[] = $tmp;
        }
        return $pp;
    }

    protected function prepTemplate($files, $prev, $next, $key, $alt = false)
    {
        $archived = [];
        $pp = [];
        $offset = 0;
        $key = abs($key);
        if (is_numeric($key) && !empty($this->paginate)) {
            list($_, $offset) = $this->paginate[$key] ?? 0;
        }
        $active = $files;
        $archived = $this->table->filterNull('page', true);
        $pp = $this->getPageList(true);
        return [
            'template' => 'articles.html.php',
            'title' => 'Articles List',
            'variables' => [
                'klas' => '',
                'action' => ARTICLES_CONFIRM,
                'exit' => BADMINTON,
                'target' => '',
                'files' => $active,
                'archived' => $alt ? [] : $archived,
                'prev' => $prev,
                'next' => $next,
                'paginate' => $this->paginate,
                'offset' => $offset ?? null,
                'increment' => $this->inc,
                'perform' => $alt ? 'destroy' : 'delete',
                'minmax' => $this->minmax,
                'page' => isset($pp[0]) ? $pp[0]['id'] : 0,
                'select' => ['target' => null, 'identity' => 'pp', 'optval' => 'page', 'options' => $alt ? [] : $pp, 'default' => 'pubdate']
            ]
        ];
    }

    protected function prepEditTemplate($article, $route, $edit = 'Edit', $key = '')
    {
        $pp = '';
        $page = '';
        $max = 0;
        $upload = '';
        //photos should have no articles and therfore should not appear in dropdown list for potential moves, this filters it out, it is required when displaying main public menu
        $pp = $this->getPageList('photos');
        $action = ARTICLES_EDIT;
        $exit = ARTICLES_LIST;
        $unset = doSetCookie(false);
        if ($article) {
            $page = $_COOKIE['page'] ?? $article->page ?? '';
            $max = count($this->table->find('page', $page));
            $upload = isset($article) ? ASSET_UPLOAD . $article->id : '';
            $action = ARTICLES_EDIT . $article->id;
            $exit = ARTICLES_LIST . strtolower($article->title ?? '');
        }
        $msg = $_COOKIE['error'] ?? '';
        $unset('error');
        $content = $_COOKIE['heading'] ?? '';
        $title = $_COOKIE['title'] ?? '';
        $page = $_COOKIE['page'] ?? $page;
        $date = $_COOKIE['date'] ?? '';
        $cookies = ['heading', 'title', 'date', 'page'];
        array_map($unset, $cookies);
        return [
            'template' => 'edit.html.php',
            'title' => "$edit Article",
            'variables' => [
                'action' => $action,
                'exit' => $exit,
                'article' => $article,
                '_content' => $content,
                '_title' => $title,
                '_page' => $page,
                '_date' => $date,
                'route' => $route,
                'max' => $max,
                'upload' => $upload,
                'key' => $key,
                'message' => $msg,
                'select' => ['target' => $page, 'identity' => 'page', 'optval' => 'page', 'options' => $pp, 'default' => '']
            ]
        ];
    }

    protected function domove($id, $title, $pages, $flag = false)
    {
        if ($flag) {
            return $this->move($id, $flag);
        }
        list($source, $target) = $pages;
        return [
            'template' => 'move.html.php',
            'variables' => [
                'action' => ARTICLES_MOVE . $id,
                'exit' => BADMINTON,
                'submit' => 'submit',
                'identity' => 'move',
                'source' => $source,
                'target' => $target,
                'id' => $id,
                'title' => $title
            ]
        ];
    }
    protected function validateOrder($article, $section)
    {
        if ($section) {
            $set = $this->table->find('attr_id', $article->attr_id, null, 0, 0, 2);
            $set = array_map(fn($a) => $a['title'], $set);
            $set = safeFilter($article->findAll(), fn($o) => in_array($o->title, $set));
            $set = array_map(fn($o) => $o->id, $set);
            return in_array($_POST['position'], $set);
        } else {
            return true;
        }
    }

    protected function order()
    {
        $label = $_POST['mytitle'];
        $reg = '|\.section|';
        $destinationID = intval($_POST['position'] - 1);
        $shuffle = isset($_POST['shuffle']);
        $article = $this->fetch('table', 'id', $_POST['pk']);
        $section = preg_match($reg, $article->attr_id);
        $article->setName($_POST['mypage']);
        $res = false;
        $pass = $this->validateOrder($article, $section);
        $feedback = $pass ? '' : "/!remove the section class from the the meta_data field to move this article out of the existing set.";
        if (!$feedback) {
            $set = $this->table->find('page', $article->page, null, 0, 0, \PDO::FETCH_ASSOC);
            $_article = isset($set[$destinationID]) ? $this->fetch('table', 'id', $set[$destinationID]['id']) : '';
            $section = !$section && $_article && preg_match($reg, $article->attr_id) && $article->id !== $_article->id;
            $feedback = $section ? "/!set the meta_data field to $article->attr_id in order to include this article to the existing set." : '';
        }
        if ($feedback) {
            reLocate(BADMINTON . $feedback, '../../');
        }
        if ($article) {
            if ($shuffle) {
                $res = $article->shuffle($destinationID, $label);
            } else {
                $res = $article->swap($destinationID, $label);
            }
        }
        if ($res) {
            //need to reload
            $page = $article->getName();
            reLocate(RELOAD . $page);
        }
        reLocate(ARTICLES_LIST, '../../');
    }
    //ensure public (form action) but DON'T add submit as we need to call it two ways
    public function move($id = 0, $flag = false)
    {
        $article = $this->fetch('TABLE', 'id', $id);
        $section = preg_match('|\.section|', $article['attr_id']);
        if ($article && !$section) {
            $oldpp = $article['page'];
            $source = $this->persist($article);
            $source->setName($oldpp);
            $list = array_map(fn($o) => $o->title, $source->findAll('id'));
            $data = array_filter($list, fn($o) => $o != $article['title']);
            $source->repop($data);
            if (!$flag) { //posted by move_submit
                $article['page'] = $_POST['page'];
                $this->persist($article);
            }
            $source->moveAssets($article['id'], $_POST['page']);
            reLocate(ARTICLES_LIST, '../../');
        }
        if ($section) {
            $feedback = "/!remove the section class from the the meta_data field to move this article.";
            if ($feedback) {
                reLocate(BADMINTON . $feedback, '../../');
            }
        }
    }

    protected function pageCheck($payload)
    {
        $entity = $this->fetch('table', 'id', $payload['id']);
        if (empty($entity->page)) {
            return empty($payload['page']) ? [] : [$payload['page']]; //unarchive
        } else {
            return equals($entity->page, $payload['page']) ? [] : [$entity->page, $payload['page']];
        }
    }

    protected function findFolio($arg)
    {
        $files = $this->table->filterNull('page', false, 'title');
        $titles = array_map(fn($o) => $o->title, $files);
        if (is_numeric($arg)) {
            $article = $this->fetch('table', 'id', $arg);
        } else if ($arg) {
            $article = $this->fetch('table', 'title', $arg);
        }
        if (isset($article)) {
            $index = array_search($article->title, $titles);
            if ($index >= 0) {
                for ($k = 0; $k <= $index; $k += $this->inc) {
                }
                return $k /= $this->inc;
            }
        }
        return 0;
    }

    protected function checkRefBlock($types, $blocks, $content)
    {
        $msg = '';
        $i = 0;
        $regex = ['/(?:^target=(blank|_blank|_parent|_self|_top)\s|^\.(left|right|none))(?:\starget=(blank|_blank|_parent|_self|_top)$|\.(left|right|none)$|$)/', '/(?:^\.(none|left|right)$|^$)/', '/(^target=(blank|_blank|_parent|_self|_top)$|^$)/', '/^$/', '/^\s$/'];
        $attrs = ['wrappedlink' => $regex[0], 'img' => $regex[1], 'mylink' => $regex[2], 'wrappedimg' => $regex[3]];
        $msgs = ['wrappedlink' => '/dofloat', 'img' => '/onlyfloat', 'mylink' => '/nofloat', 'wrappedimg' => '/noattrs'];

        while (isset($blocks[$i])) {
            $index = $types[$i] ?? 0;
            $reg = $attrs[$index] ?? '/$^/';
            if (!preg_match($reg, $blocks[$i])) {
                $msg = $msgs[$index] ?? '/$^/';
                break;
            }
            $i++;
        }
        return [$content, $msg];
    }
    protected function checkLinkPaths($types, $locations, $blocks, $content, $id, $move)
    {
        $mymatchlink = "(?:^\/\w+)|^#(?!#)(?:#\w+|)|^https?:\/\/(?:www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b(?:[-a-zA-Z0-9()@:%_\+.~#?&\/=,'!]*)(?:$|{.+})|mailto:\w+@\w+\.\w{2,4}(\.\w{2,4})?";
        $pdf = "$this->assets[\w-]+\.pdf";
        $imgpath = preg_quote(ARTICLE_IMG, '/');
        $imgpath .= '[\w-]+\.\w{2,4}';
        $imgpathalt = preg_quote(DEV, '/');
        $imgpathalt .= '[\w-]+\.\w{2,4}';
        $workingpaths = [$imgpathalt, $imgpath, $pdf, $mymatchlink];
        $msg = '';
        $i = 0;
        $_workingpaths = array_map('prepReg', $workingpaths);
        //check for duplicates but omit simple hashes that may appear more than once
        $assoc = composer(curry2('safeFilter')(fn($item) => $item !== '#'), partial('array_diff_assoc', $locations), partial('array_unique'));
        $dupes = $assoc($locations);

        if (!empty($dupes)) {
            $msg = '/uniq';
        }

        //pdf status...
        if ($id) { //could be new article
            $record = $this->fetch('TABLE', 'id', $id);
            $e = $this->table->save($record);
            //$e = $this->table->getEntity();
            $assets = $e->getAssets($id, fn($o) => preg_match('/\.pdf/', $o->path), 'path');
            $paths = findMatch('|[^/]+/([^.]+\.pdf)|', $content, -11)[1] ?? [];
            if (!$move && (count($assets) > count($paths))) {
                $msg = '/missingref';
            }
        }
        if ($msg) {
            return [$content, $msg];
        }

        while (isset($locations[$i])) {
            $finder = curry2('findMatch');
            $finder = $finder($locations[$i]);
            $key = array_find_key($_workingpaths, $finder);

            $asset = null;
            $path = true;
            $k = -1;

            if ($msg) {
                break;
            }
            if (is_int($key)) {
                $msg = $key < 2 && preg_match("/mylink/", $types[$i]) ? '/nothref' : $msg;
                $msg = $key > 2 && preg_match("/img/", $types[$i]) ? '/notsrc' : $msg;

                if (!$msg && $key < 2) {
                    $asset = $e->getAsset('assetTable', 'path', basename($locations[$i]));
                    if ($asset) {
                        $msg = $asset->article_id ? '/inlineyes' : '/inlineno';
                    } else {
                        $location = prepRegHost($locations[$i]);
                        list($path, $k) = findfile(substr($location, 1));
                    }
                    if (!$path) {
                        $msg = '/exist';
                    }

                    if (!$msg) {
                        if ($k < 2) {
                            $replacer = basename($path);
                            $pth = basename($location);
                            $fn = partial('preg_replace', '/(\w+)\.\w+$/');
                            $fn = curry2($fn)($pth);
                            $replacer = preg_match('/jpeg$/', $replacer) ? '$1.jpeg' : '$1.jpg';
                            $replacer = $fn($replacer);
                            $content = preg_replace("/$pth/", $replacer, $content);
                        }
                    }
                } else if (!$msg && $key === 2) {
                    list($path, $k) = findfile(substr($locations[$i], 1));
                    if (!$path && !$msg) {
                        $msg = '/exist';
                    }
                    $k = preg_match("/img/", $types[$i]);
                    if ($k) {
                        $msg = '/notimg';
                    }
                    $path = basename($locations[$i]);
                    $alt = '';
                    if (!$msg && !in_array($path, $assets)) {
                        $pass = preg_match('/\.pdf$/', $path);
                        $file = $e->getAsset('assetTable', 'path', $path);
                        if ($file && $file->article_id  && $file->article_id !== $id && !$msg) {
                            $msg = '/pdfother';
                        } else if ($pass) {
                            $alt = findMatch("/$path(?:\s" . '"([^"]+)"|)/', $content, 1);
                            $values = ["id" => null, "alt" => $alt, 'path' => $path, 'article_id' => $id, 'attr_id' => '', 'date' => date('Y-m-d')];
                            $e->save($values);
                            $msg = '/REINSTATED';
                            if (!is_numeric(stripos($locations[$i], $e->page))) {
                                rename(substr($locations[$i], 1), "resources/assets/$e->page/$path");
                            }
                        }
                    }
                }
            } else {
                $msg = '/linkcontent';
            }
            $i++;
        } //while
        return $msg ? [$content, $msg] : $this->checkRefBlock($types, $blocks, $content);
    }

    protected function checkLinkPathsBridge($data, $content, $id, $ref, $move)
    {
        $labels = array_keys($data);
        $types = array_values(array_map(fn($a) => $a[0], $data));
        $finder = curry3('findMatch')(1)($content);
        $refreg = array_map(fn($i) =>  '/\[' . $i . '\]:\s?([^\s"{]+)(?:\s|)(?:"[^"]+"|{([\w\.=#\s"]+)\}|)(?:\s{([\w\.=#\s"]+)\}|)/', $labels);
        $refrego = '/\[.+\]:\s?([^\s"{]+)(?:\s|)(?:"[^"]+"|{([\w\.=#\s"]+)\}|)(?:\s?{([\w\.=#\s"]+)\}|)/';
        $matcha = findMatch($refrego, $content, -11);
        $blocks = [];
        //block may be found in subgroup 2 or 3
        foreach ($matcha[2] as $k => $v) {
            $blocks[] = $v ? $v : $matcha[3][$k];
        }
        $locations = array_map($finder, $refreg);
        return $this->checkLinkPaths($types, $locations, $blocks, $content, $id, $move);
    }

    protected function mapInlineLinks($content, $id, $move)
    {
        $reg = '/(.?.?.?)[^![]*?\]\((\S+?)(?:\)|\s"[^"]+"\))(?:\s?{([^}]+)}|)/';

        $matches = findMatch($reg, $content, -1);

        if (empty($matches)) {
            return [$content, ''];
        }

        $links = findMatch($reg, $content, -2);
        $blocks = findMatch($reg, $content, -3);
        //'img' => '/!\[/' allows for word before 'img' => '/^!\[/' does not
        $types = ['wrappedimg' => '/\[!/', 'wrappedlink' => '/(^$|\]\[)/', 'img' => '/!\[/', 'badimg' => '/^\[\w/', 'mylink' => '/(.\s?|#\[)/'];
        $i = 0;
        $mapped = [];
        while (isset($matches[$i])) {
            foreach ($types as $k => $v) {
                if (preg_match($v, $matches[$i])) {
                    $mapped[] = $k;
                    break;
                }
            }
            $i++;
        }
        if (is_int(array_search('badimg', $mapped))) {
            reLocate(ARTICLES_EDIT . $id . '/mdimageformat');
        }
        if (count($mapped) !== count($links)) {
            reLocate(ARTICLES_EDIT . $id . '/linkcount');
        }

        return $this->checkLinkPaths($mapped, $links, $blocks, $content, $id, $move);
    }

    /*
    edge case; doozy
    a ref link label can be used more than once within the body copy, providing, they are used in the same context, eg: a link surrounding an image should ideally float, whereas an inlink most definitely should not (the same 'label' (2) is used in two different contexts [textlink][2] [![alt][1]][2])
    below we collect link type for everylink [label1 => ['mylink', 'wrappedlink']] if the contents of the array are not the same break the loop and WARN
    */
    protected function mapRefLinks($refs, $content, $id, $move)
    {
        if (empty($refs)) {
            return [$content, ''];
        }
        //[![my icon][1]](#){.right}
        $context = array_map(fn($j) => "/.?.?(?<=\\\\)?\[[^\[]+(?<=\])\[($j)\]/", $refs);
        $i = 0;
        $matches = [];
        while (isset($context[$i])) {
            $matches[] = findMatch($context[$i], $content, -11);
            $i++;
        }
        $i = 0;
        $data = [];
        /*
        we had an issue with an edge case where square brackets surround a link [Google Map] in the html output; needs to be escaped in markdown [\[Google Map\]]
        but could not craft a pattern that pleased php (ok in regex101) ended up moving link to first item in array (link is the only context with a space) so happy to go with that
        otherwise a wrappinglink is the false positive and fails validation as it should normally float as it would be wrapping an image, not what we want here
        had to replace 'link' with 'mylink' (for instance) as key because dump can attempt to call an argument and link just happens to be a built in function: //https://www.php.net/manual/en/function.link.php
        'mylink' => OPTIONAL \s? space as may be start of a sentence
        */
        $types = ['mylink' => '/(^.\s|^\[\\\\)/', 'wrappedlink' => '/(\]|\))\](\[|\()/',  'wrappedimg' => '/\[!/', 'img' => '/(?<!\[)!/', 'badimg' => '/^\[\w/'];

        $key = '';
        while (!$key && isset($matches[$i])) {
            $m = $matches[$i];
            //only ever more than zero if the same label was used more than once
            //[bbc1][1]; [a second link to the bbc1][1]; [bbc2][2];
            $j = 0;
            while (isset($m[0][$j])) {
                foreach ($types as $k => $v) {
                    if (!$key && preg_match($v, $m[0][$j])) {
                        $index = $m[1][$j];
                        if (!isset($data[$index])) {
                            $data[$index] = [$k];
                        } else {
                            if ($data[$index][0] !== $k) {
                                $key = '/multitype';
                            } else {
                                $data[$index][] = [$k];
                            }
                        }
                        break;
                    }
                }
                $j++;
            }
            $i++;
        }

        if ($key) {
            reLocate(ARTICLES_EDIT . $id . $key);
        }
        return $this->checkLinkPathsBridge($data, $content, $id, true, $move);
    }

    protected function checkLinkRefs($content, $title, $page, $id, $override, $move)
    {
        if (empty($content)) {
            reLocate(ARTICLES_EDIT . $id . '/empty');
        }
        //https://stackoverflow.com/questions/30254174/preg-match-with-backslashes-doesnt-work-as-expected-throws-error
        $linkindexes = findMatch('/(?<=\])\[(\w+)\]/', $content, -11);
        $linkindexes = !empty($linkindexes) ? $linkindexes[1] : [];
        $refreg = '/\[(\w+)\]:\s?(\S+)(?:\s?"[^"]+"|)(?:\s?\{([^}]+)\}|)/';
        $refs = findMatch($refreg, $content, -11);
        $refs = !empty($refs) ? $refs : [];
        //!! need to find all occurences of refs in body copy
        $txt = '';
        $msg = '';
        $links = safeFilter($linkindexes, 'identity');
        $setcookie = doSetCookie(true);
        //below two functions can rewrite the content if we have a jpe?g in validating a filepath
        list($content, $msg) = $this->mapRefLinks($refs[1], $content, $id, $move);

        if (!$msg) {
            list($content, $msg) = $this->mapInlineLinks($content, $id, $move);
        }
        $warning = isUpperCase($msg);
        $msg = $warning ? strtolower($msg) : $msg;

        if ($msg) {
            reLocate(ARTICLES_EDIT . $id . $msg);
        }
        $res = arrayDiff($refs[1], $links);

        if (!empty($res)) {
            reLocate(ARTICLES_EDIT . $id . '/linkcount');
        }
        //number of refs/links tally but indexes don't match
        list($res) = getDiff($links, $refs[1]);

        if (!empty($res)) {
            reLocate(ARTICLES_EDIT . $id .  "/linkindex");
        }

        /* uses conditional regex feature of pcre
        passes if brackets and parantheses match up, but also allows for plain heading and optional leading hash (allow this to provide a warning for missing hash)*/
        $regex = '/(?:^(#*)(?:\[([^\]]+))\](?:(\[)|(\())[^[)(\]\n]+(?(3)\]|\))|^(#*)([^[()\]\n]+))/';
        $filta = doWhen('identity', curry2('safeFilter')(fn($o) => $o));
        $zerozero = fn($o) => isset($o[0]);
        $mapper = doWhen($zerozero, partial('array_map', fn($o) => isset($o[0]) ? $o[0] : ''));
        $finder = composer($filta, $mapper, partial('findMatch', $regex, $content, -11));
        $result = $finder();

        $msg = !preg_match('/^(#)+\[[^\]]+\][^A-Z]+(?(1)[\n\r])/', $content) ? '/heading' : '';
        if ($msg) {
            $setcookie('date', date('Y-m-d'));
            $setcookie('heading', $content);
            $setcookie('page', $page);
            $setcookie('title', $title);
            reLocate(ARTICLES_EDIT . $id . $msg);
        }

        //will fail to match if no title or no link/index provided
        if (!isset($result[0]) || !isset($result[3])) {
            $msg = '/headformat';
        }
        if (!empty($res)) {
            reLocate(ARTICLES_EDIT . $id .  $msg);
        }
        list($_, $hash, $txt, $bracket) = $result;

        if ($title !== $txt && !$override) {
            reLocate(ARTICLES_EDIT . $id .  "/headsup");
        }
        //optionally fix bad format??
        //$content = $this->cleanHead($title, $content, $id, $override);
        //a $warning is a benign message and will inform the user about some sync operation with the database
        return [$content, $warning];
    }

    protected function prepPaginationBar($files, $folio)
    {
        $prev = null;
        $next = null;
        $key = 0;
        $offset = 0;
        if (is_integer($folio) && $folio >= 0) {
            $this->setCount($files);
            $this->paginate = $this->doPaginate();
            $prev = $this->getPrev($folio) ?? null;
            $next = $this->getNext($folio) ?? null;
            $key = intval($folio - 1);
            $offset = $this->paginate[$key][1] ?? 0;
        }
        return [$key, $prev, $next, $offset];
    }

    public function __construct(private DatabaseTable $table, private int $inc = 10, private array $minmax = [5, 10])
    {
        $this->table = $table;
        $files = $table->findAll();
        $active = array_filter($files, fn($o) => $o->page);
        $this->setCount($active);
        $assets = preg_quote(ASSETS, '/');
        $this->assets = "$assets(?:trust|scholars|place|stay|polo|medley|enquiries|home|BOND)\/";
    }

    public function edit($id = 0,  $key = '', $op = 'edit')
    {
        doSetCookie(false)('error');
        if (!is_numeric($id)) {
            $article = $this->fetch('table', 'title', urldecode(strtolower($id)));
        } else {
            $article = $this->fetch('table', 'id', $id);
        }
        if (!empty($article)) {
            $route = ASSETS_EDIT . $article->id;
            return $this->prepEditTemplate($article, $route, 'Edit', $key);
        } else {
            return $this->prepEditTemplate(null, '#', 'Add', $key);
        }
    }

    public function editSubmit($id = 0)
    {
        if (empty($_POST)) {
            retour();
        }
        $bond = 62;

        if ($id < $bond) {
            // reLocate(ARTICLES_EDIT . 62 . "/bond");
        }
        $key = '';
        $date = date('Y-m-d');
        $id = $_POST['pk'] ?? null;
        $entity = $this->fetch('table', 'title', $_POST['title']);
        //internal use only for the moment
        $summary = $entity ? $entity->summary : $_POST['summary'];
        $attr = preg_replace('/\s/', '&nbsp;', $_POST['attr_id']);
        $payload = ['id' => $id, 'title' => $_POST['title'], 'pubdate' => $date, 'page' => $_POST['page'], 'summary' => $summary, 'content' => $_POST['content'], 'attr_id' => $attr];
        $hardcoded = findMatch('/\w+\.html\.php/', $_POST['content'], 0);
        //bypass validation for hardcoded articles (currently only the contact form)
        if ($hardcoded) {
            return;
            $payload['content'] = $entity->content;
            $entity = $this->persist($payload);
            $this->repop($entity, $payload);
            return $_POST['content'];
        }
        $entity = $this->fetch('table', 'title', $_POST['title']);
        $checkBigWord = composer(curry2('greaterThan')(33), 'strlen');
        $checkSmallWord = composer(curry2('lesserThan')(3), 'strlen');
        $doubles = ['\.', ',', ';', ':', '<', '>', '\[', '\]', '\(', '\)', '\-', '=',];
        $preds = [['/maxword', curry2('greaterThan')(250),  null], ['/minword', curry2('lesserThan')(10), null], ['/bigword', $checkBigWord, 1], ['/smallword', $checkSmallWord, 60]];
        $content = $payload['content'];
        $content = markdownTidy($content);
        $content = array_reduce($doubles, 'markdownDoubles', $content);
        $pagestatus = $this->pageCheck($payload);
        $payload['page'] = empty($pagestatus) ? $payload['page'] : $pagestatus[0];
        $key = checkMarkdownFormatting($content);
        if ($key) {
            reLocate(ARTICLES_EDIT . $id . "/$key");
        }

        list($content, $warning) = $this->checkLinkRefs($content, $payload['title'], $payload['page'], !empty($_POST['pk']) ? $_POST['pk'] : 0, !empty($_POST['override']), !empty($pagestatus[1]));
        $payload['content'] = $this->checkInlineFormatting($content, $id);
        $payload['content'] = $this->checkSuspectCopy($content, $id, $preds);
        $payload['content'] = $this->getInlineContent($content, $id, ['/(.)\[([^]]+)\](?=\[|\()/', '/(\w)>([^<]+)</', '/(.)\*([^*]+\*)/']);
        $payload['content'] = $this->checkTitleAttribute($content, $id);

        if (isset($entity) && empty($id)) {
            $feedback = '/!cannot add article; article title is already in use.';
            reLocate(BADMINTON . $feedback, '../../');
        }
        /*
        MUST be NULL not JUST empty for MYSQL:
        Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`polafrica`.`articles`, CONSTRAINT `pager_fk` FOREIGN KEY (`page`) REFERENCES `pages` (`name`) ON DELETE SET NULL ON UPDATE CASCADE)
        */
        $payload['page'] = empty($payload['page']) ? NULL : $payload['page'];
        $entity = $this->persist($payload);
        //https://stackoverflow.com/questions/39463134/how-to-store-emoji-character-in-mysql-database
        if (empty($pagestatus) && !empty($_POST['position'])) {
            return $this->order($_POST['position']);
        }
        if ($warning) {
            reLocate(ARTICLES_EDIT . $id .  $warning);
        }
        if (empty($pagestatus) && !empty($payload['page'])) {
            reLocate(ARTICLES_LIST . $this->findFolio($id), '../../');
        }
        //adding;unarchiving
        if (!empty($pagestatus[0]) && empty($pagestatus[1])) {
            $test = $this->table->find('page', $pagestatus[0]);
            $this->repop($entity, $payload);
            if (isset($test[1])) {
                reLocate(ARTICLES_LIST, '../../');
            } else {
                //if populating page for the FIRST time..
                unset($_SESSION['nav']);
                unset($_SESSION['navbar']);
                retour();
            }
        } else if (!empty($pagestatus[1])) {
            return $this->domove($payload['id'], $payload['title'], $pagestatus, empty($pagestatus[1]));
        }
        if ($content) {
            $res = preg_split('|\n|', $content);
            $res = array_map('trim', $res);
            $content = implode("\n", $res);
        }
        reLocate(BADMINTON, '../../');
    }

    //accessed from below EDIT ARTICLE FORM @parm ARTICLE ID
    public function assets($id = 0, $key = '')
    {
        $article = $this->fetch('table', 'id', $id);
        $assets = isset($article) ? $article->getAssets($id, fn($item) => $item) : null;
        if (!empty($assets)) {
            return [
                'template' => 'assetlist.html.php',
                'title' => 'Article Assets',
                'variables' => [
                    'exit' => ARTICLES_EDIT . $id,
                    'files' => $assets,
                    'page' => $article->page,
                    'routes' => [
                        'add' => ASSET_ADD . $id,
                        'assign' => ASSET_ASSIGN . $id,
                        'edit' => ASSET_EDIT,
                        'action' => ASSET_CONFIRM,
                        'manage' => ASSET_MANAGE . $id
                    ]
                ]
            ];
        } else {
            if ($article) {
                if (isset($_SESSION['filestore'])) {
                    reLocate(ASSET_ASSIGN . $id);
                }
                reLocate(ASSET_UPLOAD . $id . "//assetfree");
            } else {
                retour();
            }
        }
    }

    public function retireSubmit($id = 0)
    {
        if (empty($_POST)) {
            retour();
        }
        if (!empty($_POST['cancel'])) {
            reLocate(ARTICLES_LIST, '../../');
        }
        $record = $this->fetch('TABLE', 'id', $id);
        $page = $record['page'];
        $record['page'] = NULL;
        $article = $this->persist($record);
        $article->setName($page);
        $this->unsetPage($page);
        //below code required when archiving; when deleting a record the database does the work (FK's)
        if (isset($_POST['child'])) {
            $article->archiveAssets($record['id'], fn($o) => true);
        }
        $list = array_map(fn($o) => $o->title, $article->findAll('id'));
        $data = array_filter($list, fn($o) => $o != $article->title);
        $res = $article->repop($data, empty($data));
        if (empty($data)) {
            retour($res);
        }
        reLocate(ARTICLES_LIST, '../../');
    }

    public function restore($id = 0)
    {
        return $this->edit($id);
    }

    public function list($folio = 1)
    {
        $unset = doSetCookie(false);
        $unset('js');
        $setcookie = doSetCookie(true);
        $alt = is_numeric($folio) && intval($folio) < 0;
        if (!$alt && isset($_POST['pp']) && $_POST['pp'] === 'pubdate') {
            $by = 'pubdate';
            $setcookie('date', 'pubdate', time() + 10);
        }
        $by = $_COOKIE['date'] ?? 'title';
        $pp = $_COOKIE['page'] ?? '';

        $offset = 0;
        $key = 0;
        $prev = null;
        $next = null;
        //-1 list archived files don't attempt to paginate (at this point)
        if ($alt && !isset($_COOKIE['altmode'])) {
            $setcookie('altmode');
            $folio = 1;
        }
        $alt = $alt || isset($_COOKIE['altmode']);
        $files = [];
        $records = $_COOKIE['records'] ?? $this->inc;

        $this->setInc($records);
        if (isset($_POST['records'])) {
            $this->setInc(intval($_POST['records']));
            $setcookie('records', $this->inc);
        }
        if ($folio === 'clear') {
            $this->unsetPage(true);
            $folio = 1;
            $pp = null;
        }
        $range = $this->validateFolio();

        if (!empty($_POST['pp']) || !empty($pp)) {
            $pubdate = $_POST['pp'] !== 'pubdate';
            //let's not bother to paginate a page view
            $max = $this->maxArticleByPage('page', 'id', \PDO::FETCH_NUM);
            $this->setInc($max);
            $pp = isset($_POST['pp']) && $pubdate ? $_POST['pp'] : $pp;
            if (!empty($pp)) {
                $unset('date');
                $by = 'title';
                $files = $this->listByPage($pp);
                //page may no longer have articles
                if (empty($files)) {
                    $this->unsetPage(true);
                } else {
                    $setcookie('page', $pp);
                }
                if (count($files) > $this->inc) {
                    list($key, $prev, $next, $offset) = $this->prepPaginationBar($files, $folio);
                    $files = $this->listByPage($pp, $this->inc, $offset);
                }
            }
        }

        if (empty($files)) { //not by page or ALL archived
            $files = $alt ? $this->table->filterNull('page', true, $by) : $this->table->filterNull('page', false, $by);
            if ($folio && !is_numeric($folio)) {
                //redirect to origin page
                $folio = $this->findFolio(ucwords(urldecode($folio)));
            }
            if (!$alt && empty($folio)) {
                $folio = 1;
            }

            if (empty($files) && !$alt) {
                $setcookie('altmode');
                reLocate(ARTICLES_LIST . 1, '../../');
            }

            if (!(count($files) > $this->count)) {
                $folio = ($folio > $range || $folio < 1) ? 1 : $folio;
            }

            if (!$alt) {
                list($key, $prev, $next, $offset) = $this->prepPaginationBar($files, intval($folio));
                $files = $this->table->filterNull('page', false, $by, $this->inc, $offset);
            } else {
                list($key, $prev, $next, $offset) = $this->prepPaginationBar($files, intval($folio));
                $files = $this->table->filterNull('page', true, $by, $this->inc, $offset);
            }

            if (empty($files)) {
                $unset('altmode');
                reLocate(ARTICLES_LIST . 1, '../../');
            }
        }
        return  $this->prepTemplate($files, $prev, $next, $key, $alt);
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

    public function destroy()
    {
        return $this->delete();
    }

    public function delete()
    {
        if (!empty($_POST['cancel'])) {
            reLocate(ARTICLES_LIST, '../../');
        }

        if (isset($_POST['pk'])) {
            $article = $this->fetch('TABLE', 'id', $_POST['pk']);
            if ($article) {
                if ($article['page']) {
                    $source = $this->persist($article);
                    $this->unsetPage($article['page']);
                    $source->setName($article['page']);
                    $source->archiveAssets($article['id'], fn($o) => true);
                }
                $this->table->delete('id', $_POST['pk']);
                reLocate(ARTICLES_LIST, '../../');
            }
        } else {
            retour();
        }
        exit;
    }

    public function confirm($id = 0, $perform = 'archive')
    {
        $file = $this->fetch('table', 'id', $id);
        $action = $this->confirmAction($perform);

        if (isset($file)) {
            $entity = $this->persist($this->fetch('TABLE', 'id', $id));
            $assets = $entity->getAssets($file->id, fn() => true);
            return [
                'template' => 'archive.html.php',
                'variables' => [
                    'action' => $action,
                    'exit' => BADMINTON,
                    'submit' => $perform,
                    //'submit' => 'submit',
                    'perform' => $perform,
                    'identity' => 'delete',
                    'confirm' => ARTICLES_CONFIRM,
                    'file' => $file,
                    'assets' => $perform === 'archive' && !empty($assets) ? true : false,
                    'record' => $file->title
                ]
            ];
        } else {
            retour();
        }
    }
}
