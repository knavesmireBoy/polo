<?php

namespace Ninja\Loader;

use \PoloAfrica\Controllers\Uploader;

class ApplicationLoader extends VideoLoader
{
    protected $extensions = ['pdf'];

    public function __construct(protected Uploader $controller, protected string $local, protected string $thumbs = '', protected $ratio = 0, protected $pp = '')
    {
        $this->local = normalizePath('identity', $local);
        /*
        if there is a failed replace operation we need to restore the file targeted for replacing
        and also the linked copy [my link][1] OR [my link](/path/to/file) becomes "my link" 
        it is easier to revert the entire article text rather than keep tabs of the previous linked copy
        */
        $this->cache = '';
        //(?<=\s)\[[^\]]+\](?!:) links excluding header and ref
    }

    protected function seekPage($str, $path)
    {
        $x = strpos($str, $path);
        if (!$x) return '';
        $x -= 2;
        $y = $x;
        while (substr($str, $y, 1) !== '/') {
            $y--;
        }
        return substr($str, $y + 1, $x - $y);
    }


    protected function isWord($needle)
    {
        $letters = [substr($needle, 0, 1), substr($needle, -1, 1)];
        $cb = function ($agg, $curr) {
            return $agg && preg_match('/[\w.,:;!]/', $curr);
        };
        return array_reduce($letters, $cb, true);
    }

    protected function unTrim($haystack, $needle, $trim = ' ')
    {
        //only real point of this is to assess whether we're dealing with a list item
        if (empty($needle)) {
            return $needle;
        }
        if (isSpaceDelimited($haystack, $needle)) {
            return $needle;
        }
        $n = $needle;
        $_needle = false;
        $j = stripos($haystack, $needle);
        if (!is_numeric($j)) {
            $_needle = $needle;
            $needle = preg_replace('/[\[\]]/', '', $needle);
            $j = stripos($haystack, $needle);
            if (isSpaceDelimited($haystack, $needle)) {
                return $_needle;
            }
        }
        if (!is_numeric($j)) {
            return $needle;
        }
        $k = $j;
        $l = strlen($needle);
        while (preg_match('/\S/', substr($haystack, $j - 1, 1))) {
            $j--;
        }
        $j = max($j, 0);
        if ($j !== $k) {
            $needle = substr($haystack, $j, $l + $k - $j);
            $j = stripos($haystack, $needle);
            $l = strlen($needle);
        }

        while (preg_match('/\S/', substr($haystack, $j + $l, 1))) {
            $l++;
        }
        $needle = substr($haystack, $j, $l);

        $i = 0;
        while (preg_match("/\W/", substr($needle, $i, 1))) {
            $i++;
        }
        if ($_needle) {
            //restore bracketed text
            $word = findMatch("/(?<=\[)(\w+)(?=\])/", $_needle, 1);
            $needle = preg_replace("/$word/", "[$word]", $needle);
        }
        //can end up with onesided result ###[Attending school](#) will yield school](#) if selection is school
        //###[Attending if 'attending'
        return $this->isWord($needle) ? trim($needle, $trim) : trim($n, $trim);
    }

    protected function resolvePath($pp, $path)
    {
        $mypath = scanMultiDir([RESOURCES, ASSETS, VIDEOS, IMAGES], $path);
        if (str_contains($this->dir, $pp)) {
            $mypath = $this->dir . $path;
        } else {
            $mypath = $this->dir . $pp . '/' . $path;
        }
        return $mypath;
    }

    protected function clearLink($linktext = '', $flag = false)
    {
        $reg = $flag ? '(?<!#)' : '\W+';
        return preg_replace("/$reg\[(.+)\](?:\[|\().+(?:\]|\))/", "$1", $linktext);
    }
    /*
    supplying only $str will clear supplied $str only
    otherwise it is assumed we are dealing with "some |new| copy" and a FRESH index 
    by default ref style links are created
    */
    protected function doClearPipe($str, $index = 0, $selection = '', $list = '')
    {
        if ($list && is_string($list)) {
            $lookup = ['ul' => '/-\s(.+)/', 'ol' => '/\d\.\s(.+)/'];
            $reg = $lookup[$list];
            return [preg_replace($reg, '$1', $str), false];
        }

        if (!$index) {
            return [preg_replace("/\|/", "", $str), false];
        }
        if (preg_match('/(\|)/', $selection)) {
            $fixEnd = partial('preg_replace', "/(?<=\|)(\w+)\s/", "$1| ");
            $fixStart = partial('preg_replace', "/\s(\w+)(?=\|)/", " |$1");
            $fix = composer($fixEnd, $fixStart);
            $selection = $fix($selection);
            $link = findMatch('/\|(\w+)\|/i', $selection, 1);
            $plaintext = preg_replace('/\|/', '', $selection);
            $myindex = "[$index]";
            $copy = findMatch("/$plaintext/i", $str);
            $link = findMatch("/($link)/i", $copy);
            $selection = preg_replace("/($link)/i", "|$1|", $copy);
            $rpl = preg_replace('/\|(\w+)\|/', "[$1]$myindex", $selection);
            return [preg_replace("/$plaintext/i", $rpl, $str), true];
        }
        return [$str, false];
    }

    protected function queryList($str, $list)
    {
        $lookup = ['ul' => '/-\s(.+)(?!-)/', 'ol' => '/\d\.\s(.+)/'];
        $rev = safeFilter(['ul', 'ol'], fn($str) => $str !== $list);
        $reg = $lookup[$list] ?? null;
        $regalt = $lookup[$rev[0]] ?? null;
        $getProp = curry2('getProp')(1);
        $getZero = curry2('getProp')(0);
        $getIndex = curry2([$this, 'getLinkInfo'])(-11);
        $linkrefs = composer($getProp, $getZero, $getIndex)($str);
        return [$reg, $regalt, $linkrefs];
    }

    protected function soReplace($regx, $str, $selection, $flag = false)
    {
        $f = $flag ? 'str_replace' : 'preg_replace';
        $replace = curry33($f)($str)($selection);
        $identity = curryDefer('identity')($str);
        //don't even allow to run if empty $reg
        $doReplace = doWhen('identity', $replace);
        //identity returns $str if empty $reg
        return doImmediate('identity', [$doReplace, $identity], $regx);
    }

    protected function getLinkInformation($str, $pathtofile = '')
    {
        $type = 'ref';
        if (is_int($pathtofile)) { //int would refer to a regex capturing group
            $reg = "|(\[[^\]]+\][:(])\s?([^){\s]+)|";
            $i = $pathtofile;
        } else { //assume reflink
            $reg = "~\[([^\]]+)\]:\s*\/?$pathtofile~i";
            $i = 1;
        }
        $ret = findMatch($reg, $str, $i);
        if (!$ret && $pathtofile) {
            $reg = "~\[([^\]]+)\]\(.*\/?$pathtofile~i";
            $ret = findMatch($reg, $str, 1);
            $type = $ret ? 'inline' : null;
        }
        return [$ret, $type];
    }

    protected function removeLinkHref($str, $pathtofile, $type = 'ref', $attr_id = false)
    {

        if ($type === 'inline') {
            $file = preg_quote($pathtofile);
            //article|author.pdf; //article~author.pdf
            $reg = '~\[([^\]]+)\]\(\/?' . $file . '(\s"([^"]+)"|)\)(?:\s*{[^}]+}|)~i';
            if (is_numeric(strpos($file, '~')) && !is_numeric(strpos($file, '|'))) {
                $reg = '|\[([^\]]+)\]\(\/?' . $file . '(\s"([^"]+)"|)\)(?:\s*{[^}]+}|)|i';
            }
            $linktext = findMatch($reg, $str, 1);
            $title = findMatch($reg, $str, 3);
            if ($attr_id && $linktext) {
                $str = $linktext ? preg_replace($reg, $linktext, $str) : $str;
                return [$str, $title];
            }
            return [$str, $title];
        } else {
            //$reg = "~\[[^\]]+\]:\s*\/?$pathtofile\s?(.+)\n?~i";
            $file = preg_quote($pathtofile);
            $reg = "~\[[^\]]+\]:\s*\/?$file(?:\s?\"([^\"]+)\"|)(?:\s*{[^}]+}|)~i";
            if (is_numeric(strpos($file, '~')) && !is_numeric(strpos($file, '|'))) {
                $reg = "|\[[^\]]+\]:\s*\/?$file(?:\s?\"([^\"]+)\"|)(?:\s*{[^}]+}|)|i";
            }
            $linktext = findMatch($reg, $str);
            $title = findMatch($reg, $str, 1);
            $str = $linktext ? str_replace($linktext, '', $str) : $str;
            return [$str, $title];
        }
    }
    protected function restoreLinkHref($str, $path, $index, $title = '')
    {

        $attrs = safeFilter([$path, $title, '{target=_blank}'], 'identity');
        $attrs = implode(" ", $attrs);
        $ref = "[$index]: $attrs";
        $a = "$str$ref";
        $b = "$str\n$ref";
        $n = substr($str, -1) == "\n" ? $a : $b;
        $n = preg_replace('/(\n\[.+\n)\s\W(.+)/', '$1$2', $n);
        return [$n, 1];
    }

    protected function appendToList($exists, $list, $listalt, $selection, $str)
    {
        if (!$exists && !empty($list)) {
            $end = preg_quote(end($list));
            $str = preg_replace("/($end)/", "$1$selection", $str);
        } else if (!$exists && !empty($listalt)) { //6).
            $rpl = curry2(partial('preg_replace', "/\S+\s(.+)/"))($selection);
            $selection = findMatch('/^-/', $selection) ? $rpl("1. $1") : $rpl("- $1");
            $end = preg_quote(end($listalt));
            $str = preg_replace("/($end)/", "$1\n$selection", $str);
        } else if (!$exists) {
            //arbitrary number to grab the end of the string; effectively a regex that affords appending to end of string
            $end = substr($str, -20);
            //create list of one item???
            //insert BEFORE FIRST ref link IF ANY, append to end of string otherwise
            $i = findMatch('/\[([^\]]+)\]:/', $str, 1);
            $x = $i ? "/(\n\[$i\]:.+)/" : "/($end)/";
            $rpl = $i ? "\n$selection$1" : "$1\n\n$selection";
            $str = preg_replace($x, $rpl, $str);
        }
        //6). //if the opposite type (ol|ul) is found append to that list RATHER than start a new list
        return [$str, trim(substr($selection, 2))];
    }

    protected function isListItem($selection)
    {
        $isUL = partial('preg_match', '/\-\s.+(?!-)/', $selection);
        $isOL = partial('preg_match', '/\d\.\s.+/', $selection);
        $findKey = partial('arrayFindKey', [$isUL, $isOL], 'invoke');
        $gt = curry2('greaterEqual')(0);
        $i = $findKey();
        $pass = $gt($i);
        return $pass ? ($i ? 'ol' : 'ul') : null;
    }

    protected function findMyListItem($str, $selection, $i = 0)
    {
        $isUL = "/(?<=-\s)$selection/i";
        $isOL = "/(?<=\d\.\s)$selection/i";
        $outcomes = [$isUL, $isOL];
        return arrayFindValues($outcomes, curry3('findMatch')($i)($str));
    }

    protected function archiveBridge($key, $linkref = '')
    {
        //may get called as a result of simple file archive request (no data field in that one button form)
        $location = isset($_POST['action']) && $_POST['action'] === 'upload' ? ASSET_UPLOAD : ASSET_ASSIGN;
        $data = $_POST['data'] ?? [];
        $data['asset_id'] = $_POST['pk'] ?? 0;
        $data['article_id'] = $data['article_id'] ?? NULL;
        $id = $data['article_id'];
        $asset_id = $data['asset_id']; //defaults to zero on upload form 

        if ($location === ASSET_UPLOAD) {
            $this->controller->preserveValues();
            return $this->revoke($data, $linkref, "$location$id/$asset_id/$key");
        } else {
            if (!$asset_id) {
                $res = $this->controller->fetch('TABLE', 'path', $data['path']);
                $asset_id = $res['id'];
                $id = $id ?? $res['article_id'];
                $data['article_id'] = $id ? $id : NULL;
            }
            return $this->archive($data,  $linkref, "$location$id/$asset_id/edit/$key");
        }
    }

    private function update($file, $str = '', $id = NULL)
    {
        if ($str) {
            $file->setContent($str, $id);
        }
        if ($file) {
            $res = toObject($file, true);
            $res['attr_id'] = '';
            $res['article_id'] = $id;
            $this->controller->update($res);
        }
    }

    protected function revoke($data, $linkref, $reroute)
    {
        $res = $this->controller->fetch('TABLE', 'attr_id', $data['attr_id'] ?? '');
        if ($res) {
            $id = $data['article_id'] ?? NULL;
            if (!$linkref) {
                $this->controller->destroy($res['id'], true);
            }
            $file = $this->controller->fetch('table', 'id', $data['path']);
            if ($file && $this->cache) {
                $this->update($file, $id);
            }
        }
        return reLocate($reroute);
    }

    protected function archive($data, $linkref, $reroute)
    {
        $id = $data['article_id'] ?? NULL;
        $res = $this->controller->fetch('TABLE', 'attr_id', $data['attr_id'] ?? '');
        if ($res) {
            //$file is the prospective REPLACEE on UPLOAD form, but prospective REPLACER ON EDIT form
            $file = $this->controller->fetch('table', 'id', $data['path']);
            if ($file && $this->cache) { //indicates an attempt to REPLACE a file
                $this->update($file, $this->cache); //archive $replacer
                $file = $this->controller->fetch('table', 'id', $data['asset_id']);
                $this->update($file, $this->cache, $id); //unarchive original
            } else {
                //failed attempt to RESTORE/UPDATE attr_id, revert if update operation  
                $this->update($res, '', $linkref ? $res['article_id'] : NULL);
            }
        } else if ($id && !empty($data['path'])) {
            $col = is_numeric($data['path']) ? 'id' : 'path';
            //attempt to restore a file from the editAsset form without $data['attr_id'] being set
            $res = $this->controller->fetch('TABLE', $col, $data['path']);
            $this->update($res);
        }
        reLocate($reroute);
    }

    protected function checkLinkStatus($linkedcopy, $txval, $selection, $index)
    {
        $i = 0;
        $found = '';
        //$selection needs to be falsy BUT if ACTUALLY set to boolean false it is an indication to override
        if ($selection) {
            list($needle) = $this->doClearPipe($selection);
            $_needle = preg_quote($needle, '[');
            $found = findMatch("~$_needle~i", $txval);
            $cb = curry2('isSubString')($needle);
            $i = whilst($linkedcopy, $cb);
            //failed if still set
            if (isset($linkedcopy[$i])) {
                return 'phrase';
            }
            //an attempt to update $linkref with duff selection will be ignored
            return $found ? true : 'pdf';
        } else {
            //$linkref and no selection ONLY when updating alt
            $aux = $index || is_bool($selection);
            return $aux ? $aux : 'pdf';
        }
    }

    protected function exemptHeading($str, $bool = false)
    {
        if (!$bool) {
            $res = findMatch("/(#+)\[([\w\s]+)\](?:\[|\()(?:([^\]]+))(?:\]|\))/i", $str, true);
            list($_, $hash, $heading, $i) = $res;
            $this->heading = [];
            $x = getRandomString(16);
            $reg = "/\[$heading\]/i";
            $str = preg_replace($reg, "[$x]", $str);
            $regx = preg_quote("/$x/i"); //use this to restore the $secret
            $this->heading = $heading ? [$regx, $heading] : [];
        } else if ($this->heading !== []) {
            list($r, $h) = $this->heading;
            $replace = curry3('preg_replace')($str)($h);
            /*
            $replacer = curry33('preg_replace')($str)($h);
            $identity = curryDefer('identity')($str);
            $doReplace = doWhen('identity', $replacer);
            $str = doImmediate('identity', [$doReplace, $identity], $r);
            */
            $str = $replace($r);
            $this->heading = [];
        }
        return $str;
    }

    protected function exemptReferences($refs, $str, $bool)
    {
        if ($bool) {
            foreach ($refs as $k => $v) {
                $str = str_replace($k, $v, $str);
            }
            $myrefs = $refs;
        } else {
            $myrefs = [];
            $refs = array_map(function ($txt) use (&$myrefs) {
                $x = getRandomString(16);
                $myrefs[$x] = $txt;
                return $txt;
            }, $refs);

            foreach ($myrefs as $k => $v) {
                $str = str_replace($v, $k, $str);
            }
        }
        return [$str, $myrefs];
    }

    protected function exempt($str, $refs, $flag = false)
    {
        if (!$flag) {
            list($info) = $this->getLinkInformation($str, -11);
            list($_, $keys, $values) = $info;
            $refs = safeFilter($values, fn($str) => preg_match('/\w/', $str));
        }
        $str = $this->exemptHeading($str, $flag);
        [$str, $refs] = $this->exemptReferences($refs, $str, $flag);
        return [$str ? preg_replace('/(?<=\n\n)\n/', '', $str) : $str, $refs];
    }

    protected function checkMulti($sanslinks, $input, $filepath)
    {
        $input = preg_quote($input, '[');
        $res = findMatch("/(\W)$input/i", $sanslinks, -11);
        // $res = empty($res) ? [] : safeFilter($res[0], fn($o) => preg_match('/^ /', $o))[0];
        $res = empty($res) ? [] : $res[0];
        $multiple = isset($res[1]); //WARN on matching MORE THAN ONE word or phrase
        //will return path if it exists in $sanslinks, indicates whether to set article_id to existing id or to null
        $update = findMatch("/$filepath/i", $sanslinks);
        if ($multiple && !$update) {
            $this->cleanup($filepath);
        }
        $res = true;
        if ($multiple && !isset($_POST['doppel'])) {
            setcookie('doppel', $_POST['data']['attr_id'], -1, '/');
            $_COOKIE['doppel'] = $_POST['data']['attr_id'];
            $res = 'doppel';
            return $this->archiveBridge('doppel', $update);
        } else if (isset($_POST['doppel'])) {
            return $res;
        }
    }

    protected function validateSelection($txval, $selection, $pathtofile, $replace)
    {
        $filepath = basename($pathtofile);
        list($linkIndex, $linkedcopy) = $this->getActiveLinkCopy($txval, $selection, $pathtofile);

        $res = $this->checkLinkStatus($linkedcopy, $txval, $selection, $linkIndex);

        //$res may be a 'pdf' if ring-fenced copy encountered; refine...
        if ($res && $selection) {
            $sanslinks = $this->clearLink($txval, true);
            list($input) = $this->doClearPipe($selection);
            $res = $this->checkMulti($sanslinks, $input, $filepath);

            if (!is_string($res)) {
                $res = isSubString($sanslinks, $input);
                $res = $res ? true : 'pdf';
            }
        }
        return [$res, $linkIndex];
    }

    protected function validateLink($values, $arg)
    {
        $first = preg_match('/upload/i', $arg) && !empty($values['attr_id']);
        return $first || preg_match('/uploaded/i', $arg);
    }
    protected function getLabel($str)
    {
        //return 1 if no current ref links
        $i = 1;
        $j = 0;
        $reflinks = findMatch('/(?<=\[)(\w+)(?=\]:.+)/', $str, -1);
        while (isset($reflinks[$j])) {
            if (!in_array($i, $reflinks)) {
                break;
            }
            $j++;
            $i++;
        }
        return [$i, '', '']; //normalise array as return value
    }

    protected function getActiveLinkCopy($str, $selection, $pathtofile)
    {
        $type = null;
        $label = '';
        $regx = '';
        $linkref = isSubString($str, $pathtofile);
        $doMatch = curry3('findMatch')(-11)($str);
        //find ALL existing links
        $refreg = "/(?<!#)\[([^\]]+)\]\[[^\]]+\]/i";
        $reg = "/(?<!#)\[([^\]]+)\]\([^)]+\)/i";
        $refcopy = $doMatch($refreg)[1] ?? [];
        $inlinecopy = $doMatch($reg)[1] ?? [];
        if ($linkref) {
            //exclude current link text from linked copy set to allow update
            list($label, $type) = $this->getLinkInfo($str, $pathtofile);
            if ($type === 'ref') {
                if ($label && $selection) {
                    $regx = "/(?<!#)\[([^\]]+)\]\[(?!$label)/i";
                    $refcopy = $doMatch($regx)[1] ?? [];
                }
            } else if ($type === 'inline') { {
                    $regx = "/(?<!#)\[(?!$label)\]\([^\)]+\)/i";
                    $inlinecopy = $doMatch($regx)[1] ?? [];
                }
            }
        }
        $regx = $regx ? $regx : ($type === 'inline' ? $reg : $refreg);
        $linkedcopy = array_merge($refcopy, $inlinecopy);
        return [$label, $linkedcopy];
    }

    protected function qualifyingSelection($str, $selection, $list)
    {
        list($input) = $this->doClearPipe(trim($selection), 0, '', $list);
        $res = findMatch("/$input/i", $str, -11)[0];
        //has context required if more than one instance of word
        $res = !isset($res[1]) || (isset($res[1]) && preg_match('/\s/', $input));
        $res = $res ? $res : $list;
        return $res;
    }

    public function findListItem($str, $selection, $i = 0)
    {
        return $this->findMyListItem($str, $selection, $i);
    }

    public function getLinkInfo($str, $pathtofile = '')
    {
        return $this->getLinkInformation($str, $pathtofile);
    }

    public function breakLink($str, $pp, $path, $selection)
    {
        $this->dir = $this->setDir($pp);
        $pathtofile = $this->dir . $path;
        //$this->cache will survive for one request
        $this->cache = $this->cache ? $this->cache : $str;
        //GIVEN a LIST item we could delete the item on removal of link, but let us leave that to the discretion of the editor
        list($aux, $type) = $this->getLinkInfo($str, $pathtofile); //[n]
        //[1]: /path/to/file is removed
        list($str, $title) = $this->removeLinkHref($str, $pathtofile, $type, $selection);
        if ($aux && $selection && $type) {
            //[copy][1] becomes copy IF there was some copy provided
            //it is otherwise left AND the index is available for swapping
            $reg = ($type === 'ref') ? "/\[([^\]]+)\]\[$aux\]/i" : "/\[($aux)\]\([^)]+\)\s*(?:{[^}]+}|)?/i";
            $str = preg_replace($reg, '$1', $str);
        }
        $str = preg_replace('/  {/', ' {', $str);
        return [$str, $title];
    }

    public function makeLink($str, $filepath, $title = '', $selection = '', $replace = null)
    {
        $lookup = null;
        $msg = null;
        $inlinecopy = '';
        $pp = $this->pp ? $this->pp : $this->seekPage($str, $filepath);
        $pathtofile = $this->resolvePath($pp, $filepath);
        $reflinks = findMatch('/(?<=\[)(\w+)(?=\]:.+)/', $str, -11)[1] ?? [];
        $labels = findMatch('/\]\[([^\]]+)\]/', $str, -11)[1] ?? [];
        $key = checkMarkdownFormatting($selection);

        //1)
        $availableIndex = arrayDiff($labels, $reflinks)[0] ?? null;
        if ($availableIndex) {
            $t = $title ? $title : $replace->alt ?? '';
            $title = $t ? '"' . $t . '"' : '';
            return $this->restoreLinkHref($str, $pathtofile, $availableIndex, $title);
        }
        if (isset($replace->path)) {
            $lookup = $this->resolvePath($pp, $replace->path);
            $title = $replace->alt;
            $inlinecopy = findMatch("|\[([^\]]+)\]\(/?$lookup|", $str, -1);
            $inlinecopy = $inlinecopy[0] ?? '';
            if ($inlinecopy) {
                $str = preg_replace("/(\[$inlinecopy\])\(\S+(\s.+|)\)({[^}]+}|)/", "$1($pathtofile$2)$3", $str);
                return [$str, 1];
            }
        }

        if (!$this->checkMimeType($pathtofile)) {
            return [$str, $msg];
        }

        list($msg, $linkIndex) = $this->validateSelection($str, $selection, $pathtofile, $replace);
        $list = ($msg === 'ol' || $msg === 'ul');

        if ((is_string($msg) || $key) && !$list) {
            return $this->archiveBridge($msg, $linkIndex);
        }
        $msg = $list ? $msg : true;
        $myrefs = [];
        $doUpdateCopy = $selection && $msg;
        $doFind = curry3('findMatch')(-1)($str);
        $findLink = partial('findMatch', "/\[[^\]]+\]/");
        if ($linkIndex || $msg) {
            list($str, $t) = $this->breakLink($str, $pp, $filepath, $selection);
            $title = $title ? $title : $t;
            $index = $linkIndex ? $linkIndex  : $this->getLabel($str)[0];
        }
        //if new copy; existing link would have been cleared by $this->breakLink
        //if unqualifying copy we don't get to this point
        if ($doUpdateCopy && $this->qualifyingSelection($str, $selection, $msg)) {
            $piped = false;
            list($str, $myrefs) = $this->exempt($str, $myrefs);
            list($reg, $regalt, $linkrefs) = $this->queryList($str, $msg);
            if ($reg) { //a list item
                $selection = $this->unTrim($str, $selection, '');
                $i = preg_match("/$selection/", $str, $m);
                list($str, $selection) = $this->appendToList($i, $doFind($reg), $doFind($regalt), $selection, $str);
                $queryText = curry2('findMatch')($str);
            } else {
                //2)allow unTrim??, certainly don't advertise it
                $selection = $this->unTrim($str, $selection, '');
                list($str, $piped) = $this->doClearPipe($str, $index, $selection);
                //don't move this, must use cleared $str
                $queryText = curry3('findMatch')(-11)($str);
                if ($findLink($selection)) {
                    $selection = $this->clearLink($selection);
                }
            }
            if (!$piped) {
                $selection = strtolower($selection);
                $reg = "/(?<!\[)$selection(?!\])/i";
                $copy = $queryText($reg)[0];
                if (!isset($copy[0])) {
                    return $this->archiveBridge('pdf', $linkIndex);
                }
                if (!isset($copy[1])) {
                    $copy = $copy[0];
                    $selection = "[$copy][$index]";
                    $str = $this->soReplace($reg, $str, $selection);
                } else {
                    $i = 0;
                    //edge case maybe multiple words BUT different case
                    //need to use reduce as $str not updated in loop
                    $str = array_reduce($copy, function ($agg, $curr) use ($index) {
                        $curr = findMatch("/(?<!\[)$curr(?!\])/i", $agg);
                        return str_replace("$curr", "[$curr][$index]", $agg);
                    }, $str);
                }
            }
        } //qualify

        list($str) = $this->exempt($str, $myrefs, true);

        if ($index) {
            $t = $title ? '"' . $title . '"' : '';
            //default to ref type for fresh links
            return $this->restoreLinkHref($str, $pathtofile, $index, $t);
        }
        if ($this->next) {
            return $this->next->makeLink($str, $filepath, $title, $selection);
        }
        return [$str, $msg];
    }

    public function init($filename, $articleId = 0, $metadata = '')
    {
        if (!$this->checkMimeType(FILESTORE_DIR . $filename) && isset($this->next)) {
            return $this->next->init($filename);
        } else {
            $untrack = preg_match('/^!/', $metadata);
            if (preg_match('|\.pdf$|', $filename) && $untrack) {
                unlink(FILESTORE_DIR . $filename);
                reLocate(ASSET_UPLOAD . "$articleId/0/pdfuntrack", '../../');
            }
        }
    }

    public function exit($id, $record, $flag = true)
    {
        if ($flag) {
            $record['attr_id'] = '';
            $record['id'] = $id; //make sure we UPDATE
            return $record;
        }
        return null;
    }
}
/*
1) 
$availableIndex is ONLY used when no selection copy is provided, ie replacing the FILE the LINK refers to:
when essentially you have linked COPY [mylink][13] but no corresponding REF [13]: which occurs after archiving (courtesy of breakLink) but before relinking; $availableIndex in this case is 13
$availableIndex is a great indicator of state and intent
2)
unTrim can return [title of my book][1] if given "le of my bo" which would mean a selection of "title of my book" would not be found should a second "title of my book" be required for a different link
so we would have to fix this scenario. edge case
3)
doozy
find the text that will be the link in the context ($selection) "my |link| is here"
strip $selection of markup
obtain the article copy
find the actual case sensitive link
markup the new |link|
replace article copy "my [link][2] is here"
    !! ASSUMES ONLY DIGITS ARE USED FOR INDEX
    priority for NEW index
    a) unused index [redundant link][6]
    b) availableIndex (current indexes 1,2,3,6) = 4
    c) lastIndex (1,2,3,4,5) = 5

        can't have nolinkref and no selection
        can have linkref and selection update/replace
        bla bla [dr no][1] blalbla [goldfinger][2] then later doctor no and also thunderball
        [1]: /abc
        [2]: /def
        a) nolinkref (ie /xyz) AND selection ie thunderball JUST ADD check only
        b) linkref (ie /abc) selection ie (doctor no) UPDATE check and exclude
        c) nolinkref (ie /xyz) no selection (implicit replace) ie /abc|/def no linked copy to update
        d) nolinkref AND selection AND replace CLEAR then ADD
        e) linkref AND selection (replace not required)
        exclude:
        linkref && selection
5.)
allow for list item - itemA OR 1. itemB
but not bracketed copy..
eg. "The three female characters — the wife, the nun, and the jockey — are the incarnation of excellence."
MASSIVE ASSUMPTION that all items in a list conform to hypen single space "- Hello"
        
        */