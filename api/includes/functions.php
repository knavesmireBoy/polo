<?php

function always($x)
{
    return function () use ($x) {
        return $x;
    };
}

function doWhen($predicate, $action)
{
    return function (...$args) use ($predicate, $action) {
        if ($predicate(...$args)) {
            return $action(...$args);
        }
    };
}


function getter($o, $p)
{
    return $o[$p];
}

$stripos = doWhen('identity', 'stripos');


function isSubString($haystack, $needle, $offset = 0)
{
    return composer('is_int', 'stripos')($haystack, $needle, $offset);
}

function invokeDefer($func)
{
    return function (...$args) use ($func) {
        return $func(...$args);
    };
}

function invokeArray(callable $cb, array $args)
{
    return $cb(...$args);
}

function invoke($func, ...$args)
{
    return $func(...$args);
}

function invokeOnly($func)
{
    return $func();
}

function lazyRest($func, $b)
{
    return function ($a, $c) use ($func, $b) {
        if (is_callable($func)) {
            call_user_func($func, $a, $b, $c);
        }
    };
}

function lazyMiddle($func, $a, $c)
{
    return function ($b) use ($func, $a, $c) {
        if (is_callable($func)) {
            call_user_func($func, $a, $b, $c);
        }
    };
}

function curry2($fun)
{
    return function ($arg2) use ($fun) {
        return function ($arg1) use ($fun, $arg2) {
            return $fun($arg1, $arg2);
        };
    };
}

function curryDefer($fun)
{
    return function ($arg1) use ($fun) {
        return function () use ($fun, $arg1) {
            return $fun($arg1);
        };
    };
}

function curryDeferByRef($fun)
{
    return function (&$arg1) use ($fun) {
        return function () use ($fun, &$arg1) {
            return $fun($arg1);
        };
    };
}


function curry22($fun)
{
    return function ($arg2) use ($fun) {
        return function ($arg1) use ($fun, $arg2) {
            return function () use ($fun, $arg1, $arg2) {
                return $fun($arg1, $arg2);
            };
        };
    };
}

function curry22Ref($fun)
{
    return function ($arg2) use ($fun) {
        return function (&$arg1) use ($fun, $arg2) {
            return function () use ($fun, &$arg1, $arg2) {
                return $fun($arg1, $arg2);
            };
        };
    };
}


function curry3($fun)
{
    return function ($arg3) use ($fun) {
        return function ($arg2) use ($fun, $arg3) {
            return function ($arg1) use ($fun, $arg2, $arg3) {
                return $fun($arg1, $arg2, $arg3);
            };
        };
    };
}


function curry33($fun)
{
    return function ($arg3) use ($fun) {
        return function ($arg2) use ($fun, $arg3) {
            return function ($arg1) use ($fun, $arg2, $arg3) {
                return function () use ($fun, $arg1, $arg2, $arg3) {
                    return $fun($arg1, $arg2, $arg3);
                };
            };
        };
    };
}


function curry2L($fun)
{
    return function ($arg1) use ($fun) {
        return function ($arg2) use ($fun, $arg1) {
            return $fun($arg1, $arg2);
        };
    };
}

function curry2LDefer($fun)
{
    return function ($arg1) use ($fun) {
        return function ($arg2) use ($fun, $arg1) {
            return function () use ($fun, $arg1, $arg2) {
                return $fun($arg1, $arg2);
            };
        };
    };
}


function curry2ByRefDefer($fun)
{
    return function (&$arg1) use ($fun) {
        return function ($arg2) use ($fun, &$arg1) {
            return function () use ($fun, &$arg1, $arg2) {
                return $fun($arg1, $arg2);
            };
        };
    };
}

function curry2ByRef($fun)
{
    return function (&$arg1) use ($fun) {
        return function ($arg2) use ($fun, &$arg1) {
            return $fun($arg1, $arg2);
        };
    };
}

//https://eddmann.com/posts/using-partial-application-in-php/
function partial($func, ...$args)
{
    return function (...$newargs) use ($func, $args) {
        return $func(...$args, ...$newargs);
    };
}

function mittel($func, $b)
{
    return function ($a, $c) use ($func, $b) {
        return $func($a, $b, $c);
    };
}

function lazyMittel($func, $a, $c)
{
    return function ($b) use ($func, $a, $c) {
        return $func($a, $b, $c);
    };
}

function eagerMittel($func, $b)
{
    return function ($a) use ($func, $b) {
        return function ($c) use ($func, $b, $a) {
            return $func($a, $b, $c);
        };
    };
}

function callUserFuncArray($args)
{
    $func = array_shift($args);
    return function ($newargs) use ($func, $args) {
        return call_user_func_array($func, array_merge($args, $newargs));
    };
}

function partialDefer($func, ...$args)
{
    return function (...$newargs) use ($func, $args) {
        return function () use ($func, $args, $newargs) {
            return $func(...$args, ...$newargs);
        };
    };
}

function partialDefer2($func, ...$args)
{
    return function () use ($func, $args) {
        return function ($arg) use ($func, $args) {
            return $func(...$args);
        };
    };
}

function partialRef($f, &$a, ...$args)
{
    return function (...$newargs) use ($f, &$a, $args) {
        call_user_func_array($f, [&$a, ...$args, ...$newargs]);
        return $a;
    };
}

function deco($fn)
{
    return function (...$args) use ($fn) {
        return $fn(...$args);
    };
}
function dump($arg = "Hello")
{
    if (!is_array($arg) && is_callable($arg)) {
        if ($arg()) {
            var_dump($arg);
            exit;
        }
    } else {
        var_dump($arg);
        exit;
    }
}

function vdump($a)
{
    var_dump($a);
    return $a;
}

function doEcho($arg)
{
    echo $arg;
}

function greaterThan($a, $b)
{
    return $a > $b;
}

function greaterEqual($a, $b)
{
    return $a >= $b;
}

function lesserThan($a, $b)
{
    return $a < $b;
}

function divideBy($divisor)
{
    return function ($dividend) use ($divisor) {
        return $dividend / $divisor;
    };
}

function multiplyBy($multiplier)
{
    return function ($multiplicand) use ($multiplier) {
        return $multiplicand * $multiplier;
    };
}

function divide($dividend, $divisor)
{
    return $dividend / $divisor;
}
/*
function minus($a)
{
    return function ($b) use ($a) {
        return $a - $b;
    };
}

function add($a)
{
    return function ($b) use ($a) {
        return $a + $b;
    };
}
    */

function add($a, $b)
{
    return $a + $b;
}
function minus($a, $b)
{
    return $a - $b;
}
function equals($a, $b)
{
    return $a === $b;
}

function doMatch($k, $v)
{
    return function ($o) use ($k, $v) {
        if (is_array($o)) {
            return isset($o[$k]) && $o[$k] === $v;
        }
        return isset($o->{$k}) && $o->{$k} === $v;
    };
}

function toObject($o, $arg = false)
{
    return json_decode(json_encode($o), $arg);
}

function doEncode($str)
{
    return urlencode(strtolower($str));
}

function prepID($str)
{
    return strtolower(str_replace(' ', '_', $str));
}

function prepPoloTitles($str = 'home')
{
    $list = ['admin', 'home', 'polo in africa', 'trust', 'scholars', 'place', 'stay', 'polo', 'medley', 'enquiries', 'photos'];
    $res = explode(' ', strtolower($str));
    $home = isset($res[2]) && $res[2] === 'africa';
    if ($home) {
        return 'home';
    }
    $fail = isset($res[2]) ? true : false;
    $res = $res[1] ?? $res[0];
    // "Your Stay","The Place" => stay, place
    $t = $fail ? 'lost' : $res;
    if (in_array($res, $list)) {
        return $t;
    }
    return $res;
}

function prepTitle($str)
{
    return ucwords(str_replace('_', ' ', $str));
}

function checkLower($str)
{
    return $str === strtolower($str);
}

function beautify($txt)
{
    return ucwords(strtolower(str_replace('_', ' ', $txt)));
}

function isUpperCase($str, $flag = false)
{
    return  $str && ucwords($str) === $str ? ($flag ? strtolower($str) : true) : false;
}

function exclaim($msg, $char = '!')
{
    $msg = urldecode($msg);
    if (substr($msg, 0, 1) === $char) {
        //  $msg = ucfirst(substr($msg, 1));
        $msg = substr($msg, 1);
    } else {
        $msg = '';
    }
    return is_numeric($msg) ? '' : $msg;
}

function abbr($name)
{
    preg_match_all('/\b\w/u', $name, $abbreviatedName);
    return implode("", $abbreviatedName[0]);
}

function makeReg($txt, $mod)
{
    return "/$txt/$mod";
}

function regClear($str, $reg)
{
    return preg_replace($reg, '', $str);
}

function beautify2($txt)
{
    $txt = strtolower(str_replace('_', ' ', $txt));
    $txt = $txt !== 'id' ? ucwords($txt) : $txt;
    if (strpos($txt, "B")) {
        $txt = abbr($txt);
    }
    return $txt;
}

function split($data, $not)
{
    $ret = [];
    $sale = [];
    $alp = [];
    $pair = [];
    foreach ($data as $d) {
        foreach ($d as $k => $v) {
            if ($k === strtolower($k)) {
                $sale[$k] = $v;
            } else {
                $alp[$k] = $v;
            }
        }
        $pair[] = $sale;
        $pair[] = $alp;
        //$tmp = $pair;
        $ret[] = $pair;
    }
    return $ret;
}

function html($str)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function htmlout($str)
{
    echo html($str);
}

function toHtml($str)
{
    // convert $this->string to HTML
    $text = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');

    // strong (bold)
    $text = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);

    // emphasis (italic)
    $text = preg_replace('/_([^_]+)_/', '<em>$1</em>', $text);
    $text = preg_replace('/\*([^\*]+)\*/', '<em>$1</em>', $text);

    // Convert Windows (\r\n) to Unix (\n)
    $text = str_replace("\r\n", "\n", $text);
    // Convert Macintosh (\r) to Unix (\n)
    $text = str_replace("\r", "\n", $text);
    /*
        // Paragraphs
        $text = '<p>' . str_replace("\n\n", '</p><p>', $text) . '</p>';
        // Line breaks
        $text = str_replace("\n", '<br>', $text);
*/
    // [linked text](link URL)

    $text = preg_replace('/\[([^\]]+)]\(([-a-z0-9._~:\/?#@!$&\'()*+,;=%]+)\)/i', '<a href="$2">$1</a>',  $text);
    return $text;
}

//https://stackoverflow.com/questions/14334740/missing-parentheses-with-regex; adapted from js
//usage: replace ALL but special characters then replace ONLY those that FOLLOW one another
//if orphans are left return true ie: !$str
function checkBrackets($str, $pair = null)
{
    $s = '';
    $reg = null;
    $rpl = null;

    if ($pair) {
        $reg = "/[^$pair]/";
        //must escape end square bracket in both scenarios when in an exclusion block [^abc[\]]
        $reg = substr($pair, 1) === ']' ? "/[^[\]]/" : $reg;
        //brace does not need escaping but no harm is done
        $rpl = preg_quote($pair);
    }
    $reg = $reg ? $reg : '/[^{}[\]()]/';
    $rpl = $rpl ? $rpl : '{}|\[\]|\(\)';
    $str = preg_replace($reg, '', $str);
    while ($s != $str) {
        $s = $str;
        $str = preg_replace("/$rpl/", '', $str);
    }
    return !$str;
};

function single_space($value)
{
    return preg_match('/^\s$/', $value);
}
function identity($arg)
{
    return $arg;
}


// Function to find out the maximum repeating
// character in given string
function maxRepeating($str, $limit, $exceptions)
{
    $n = strlen($str);
    $maxCnt = 0;
    //at least three letter word
    $res = $str[2] ?? null;
    $cnt = 1;
    $limit = is_numeric($res) ? 7 : $limit;
    if ($res) {
        for ($i = 1; $i < $n; $i++) {
            if ($cnt > $limit) {
                $res = true;
                break;
            }
            if (in_array($str[$i], $exceptions)) {
                continue;
            }
            if ($str[$i] && ($str[$i] === $str[$i - 1])) {
                $cnt++;
            } else {
                $cnt = 1;
            }

            if ($cnt > $maxCnt) {
                $maxCnt = $cnt;
                $res = $str[$i - 1];
            }
        }
        return $res;
    }
}

function spam_scrubber($value)
{
    //usage: $scrubbed = array_map('spam_scrubber', $data($_POST));
    // List of very bad values:
    $very_bad = array(
        'to:',
        'cc:',
        'bcc:',
        'content-type:',
        'mime-version:',
        'multipart-mixed:',
        'content-transfer-encoding:'
    );
    if (is_array($value)) {
        foreach ($value as $v) {
            return spam_scrubber($v);
        }
    }
    // If any of the very bad strings are in
    // the submitted value, return an empty string:
    foreach ($very_bad as $v) {
        if (preg_match("/^$v$/", $value)) {
            return '';
        }
        if (isSubString($value, $v)) {
            // return '';
        }
    }
    /*Replace any newline characters with spaces:
    $value = str_replace(array(
        "\r",
        "\n",
        "%0a",
        "%0d",
        "\t",
        "%08",
        "%09"
    ), ' ', $value);
    */
    //$value = str_replace(array('Fucking', 'fucking', 'fuck', 'wank', 'cunt'), '***', $value);
    // Return the value:
    return trim($value);
} // End of spam_scrubber() function.

function spam_scrubber2($value)
{

    //usage: $scrubbed = array_map('spam_scrubber', $data($_POST));
    // List of very bad values:
    $very_bad = array(
        'to:',
        'cc:',
        'bcc:',
        'content-type:',
        'mime-version:',
        'multipart-mixed:',
        'content-transfer-encoding:'
    );

    $postbad = [];

    if (is_array($value)) {
        /*
        foreach ($value as $v) {
         return spam_scrubber($v);
        }
        */
        $value = implode(' ', $value);
    }
    // If any of the very bad strings are in
    // the submitted value, return an empty string:

    foreach ($very_bad as $v) {
        $postbad[] = stripos($value, $v) ? '' : $v;
    }
    $postbad = safeFilter($postbad, fn($o) => !$o);
    if ($postbad === []) {
        $spacelike = [
            "\r",
            "\n",
            "%0a",
            "%0d",
            "\t",
            "%08",
            "%09"
        ];
        // Replace any newline characters with spaces:
        $value = str_replace($spacelike, ' ', $value);
        return is_array($value) ? array_map('trim', $value) : trim($value);
    }
    return $postbad[0];

    //$value = str_replace(array('Fucking', 'fucking', 'fuck', 'wank', 'cunt'), '***', $value);
} // End of spam_scrubber() function.

function buildMessage($k, $v, $flag)
{
    $ret = $flag ? "" : "\r\n\r\n"; //!MUST BE DOUBLE QUOTES!
    $str = ucfirst($k) . ': ' . $v;
    return $str . $ret;
}

function stringMin($v)
{
    return strlen($v) > 15;
}

function stringMax($v)
{
    return strlen($v) < 1000;
}

function getDiff(array $a, array $b)
{
    $big = $a;
    if (count($a) >= count($b)) {
        return [array_values(array_filter(array_diff($a, $b), fn($o) => $o)), $big];
    }
    $big = $b;
    return [array_values(array_filter(array_diff($b, $a), fn($o) => $o)), $big];
}

function arrayFindKey($arr, $cb)
{
    $res = array_filter($arr, $cb);
    if (empty($res)) {
        return -1;
    }
    return current(array_keys($res));
}
function arrayFindKeys($arr, $cb)
{
    $res = array_filter($arr, $cb);
    if (empty($res)) {
        return -1;
    }
    return array_keys($res);
}


function arrayFindValues($arr, $cb)
{
    $res = array_filter($arr, $cb);
    if (empty($res)) {
        return null;
    }
    return current(array_values($res));
}

function getProp($o, $p)
{
    return isset($o[$p]) ? $o[$p] : null;
}

function inMyArray($needle, $haystack)
{

    $cb = function ($n) {
        return function ($agg, $cur) use ($n) {
            return $agg ? $agg : preg_match("/^$n$/i", $cur);
        };
    };
    return array_reduce($haystack, $cb($needle));
}

function mypluck($haystack, $n = 0)
{
    if (!$n) {
        $n = reset($haystack);
        return [$n];
    }
    if (is_bool($n)) {
        $n = end($haystack);
        return [$n];
    }
    return $haystack[$n] ?? null;
}

function preconditions()
{
    $checkers = func_get_args();
    return function ($strategy, $value) use ($checkers) {
        $errors = array_reduce(array_map(
            function ($checker) use ($strategy, $value) {
                return $checker->validate($value) ? array() : array(
                    $checker->message
                );
            },
            $checkers
        ), 'array_merge', array());
        if (!empty($errors)) {
            return $errors;
        }
        //return $strategy->algorithm($value);
        return $strategy($value);
    };
}

function preconditionsMod()
{
    $checkers = func_get_args();
    return function ($strategy, $value) use ($checkers) {
        $errors = array_reduce(array_map(
            function ($checker) use ($strategy, $value) {
                return $checker->validate($value) ? array() : array(
                    $checker->message
                );
            },
            $checkers
        ), 'array_merge', array());
        if (!empty($errors)) {
            return $errors;
        }
        //return $strategy->algorithm($value);
        return $strategy($value);
    };
}

//https://stackoverflow.com/questions/25105796/php-add-value-to-a-existing-query-string
function setQueryString($url, $key, $val)
{
    $pUrl = parse_url($url);
    if (isset($pUrl['query'])) parse_str($pUrl['query'], $pUrl['query']);
    else $pUrl['query'] = [];
    $pUrl['query'][$key] = $val;

    $scheme = isset($pUrl['scheme']) ? $pUrl['scheme'] . '://' : '';
    $host = isset($pUrl['host']) ? $pUrl['host'] : '';
    $path = isset($pUrl['path']) ? $pUrl['path'] : '';
    $path = count($pUrl['query']) > 0 ? $path . '?' : $path;
    return $scheme . $host . $path . http_build_query($pUrl['query']);
}


function path($f, $d = '/templates/')
{
    $path = strrchr(__DIR__, '/');
    include __DIR__ . "../../../websites$path$d$f";
}

function loadTemplate($templateFileName, $variables)
{
    extract($variables);
    ob_start();
    include TEMPLATE . $templateFileName;
    return ob_get_clean();
}

function checkUri($uri)
{
    if ($uri != strtolower($uri)) {
        http_response_code(301);
        header('Location: ' . strtolower($uri));
    }
}

function startSession()
{
    if (!isset($_SESSION)) {
        session_start();
    }
}

function fileExists($file, $root = false)
{
    if ($root && $file) {
        return is_file($_SERVER['DOCUMENT_ROOT']  . $file);
    } else if ($file) {
        $i = stripos($file, '/');
        $file = $i === 0 ? substr($file, 1) : $file;
        return is_file($file) ? $file : null;
    }
}

function isDir($file)
{
    if ($file) {
        $i = strpos($file, '/');
        $file = $i === 0 ? substr($file, 1) : $file;
        return is_dir($file) ? $file : null;
    }
    return null;
}

function makeMove($route, $pathtofile, $sub)
{
    $path = "/" . basename($pathtofile);
    $mkdir = composer(curry2('mkdir')(0777), curry2('substr')(1));
    $doMkDir = doBestDefer('isDir', [fn($o) => null, $mkdir]);
    $move = composer(partial('rename', $pathtofile), 'normalize');
    $doMkDir($route  . $sub)();
    $move($route . $sub  . $path);
}

function scanDirectory($dir)
{
    $i = strpos($dir, '/');
    $dir = $i === 0 ? substr($dir, 1) : $dir;
    return scandir($dir);
}

function getFilePath($file)
{
    $i = strpos($file, '/');
    $file = $i === 0 ? substr($file, 1) : $file;
    return $file;
}

function normalizePath($f, $path, $rev = false)
{
    $i = $path ? strpos($path, '/') : null;
    if ($path) {
        if ($i === 0) {
            $path = substr($path, 1);
        } else if (!$i && $rev) {
            $path = "/$path";
        }
    }
    return $f($path);
}

function normalize($path)
{
    return normalizePath('identity', $path);
}

//https://stackoverflow.com/questions/7497733/how-can-i-use-php-to-check-if-a-directory-is-empty
function dir_is_empty($dir)
{
    $handle = opendir($dir);
    $handle = $handle ? $handle : null; //null not false
    if ($handle) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                closedir($handle);
                return false;
            }
        }
        closedir($handle);
        return true;
    }
    return false;
}
//https://philio.me/recursively-remove-a-directory-in-php-using-spl/
function recursiveRmDir($dir)
{
    $iterator = new RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $filename => $fileInfo) {
        if ($fileInfo->isDir()) {
            rmdir($filename);
        } else {
            unlink($filename);
        }
    }
}
//used by scanMultiDir in functions.php (this file) and checkLinkPaths in Article.php controller
function findfile($dir, $path = '', $i = 2)
{
    $path = normalizePath('identity', $path);
    //!$path assumes $dir is fullpath to file
    if (!$path) {
        $path = basename($dir);
        $dir = dirname($dir);
        $dir = "$dir/";
    }
    $ret = fileExists($dir . $path);
    if (!$ret && $i && preg_match('/\.jpe?g$/i', $path)) {
        $ext = stristr($path, '.');
        $pth = strstr($path, '.', true);
        if ((strlen($ext) === 4) && $i) {
            return findfile($dir, $pth . '.jpeg', $i -= 1);
        } else if ($i) {
            return findfile($dir, $pth . '.jpg', $i -= 1);
        }
    } else if ($ret) {
        return [$path, $i];
    }
    return ['', ''];
}

function getMimeType($path, $ubers = [])
{
    if (empty($ubers)) {
        $ubers = [GALLERY, IMAGES, ASSETS, VIDEOS, RESOURCES, FILESTORE_DIR];
    }
    $scan = partial('scanMultiDir', $ubers);
    $exists = composer('fileExists', $scan);
    $filename = $exists(basename($path));
    if ($filename && is_file($filename)) { // deal with '../'
        $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type aka mimetype extension
        $ret = finfo_file($finfo, $filename);
        //finfo_close($finfo);
        return $ret;
    }
    return '';
}

//https://deadlytechnology.com/blog/web-development-tips/php-ternary-syntax
function prepReg($str, $x = '', $y = '')
{
    return (
        ($x && $y) ? "/^$str$/" : (($x) ? "/^$str/" : (($y) ? "/$str$/" : "/$str/")));
}


function notEmpty($arg = '')
{
    $a = is_array($arg);
    if ($a) {
        return $arg !== [];
    }
    $a = is_string($arg);
    if ($a) {
        return $arg !== '';
    }
    $a = is_numeric($arg);
    if ($a) {
        return $arg !== 0;
    }
    return $arg;
}


function findMatch($reg, $str, $i = 0)
{
    //  $reg = preg_quote($reg, '_');
    $def = $i < 0;

    if (!empty($reg) || !empty($str)) {
        if ($def) {
            preg_match_all($reg, $str, $matches);
            $i = abs($i);
            $i = $i > 10 ? true : $i;
        } else {
            preg_match($reg, $str, $matches);
        }
        $ret = $def ? [] : '';
        return empty($matches) ? $ret : (is_bool($i) ? $matches : ((isset($matches[$i])) ? $matches[$i] : $ret));
    }
    return null;
}

function findMatcher($reg, $str, $i = 0)
{
    if (!empty($reg) || !empty($str)) {
        if ($i < 0) {
            preg_match_all($reg, $str, $matches);
            $i = $i < -1 ? true : 0;
        } else {
            preg_match($reg, $str, $matches);
        }
        if (empty($matches)) {
            return '';
        }
        if (is_bool($i)) {
            return $matches;
        }
        return $matches[$i] ?? null;
    }
    return null;
}

function prepRegHost($path)
{
    $finder = curry2('findMatch')(trim($path));
    $host = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]";
    $host = preg_quote($host, '/');
    if ($finder("/$host/")) {
        $path = parse_url($path);
        $path = end($path);
    }
    return trim($path);
}

function isSpaceDelimited($h, $n)
{
    $i = stripos($h, $n);
    $first = substr($h, $i - 1, 1);
    $last = substr($h, $i + strlen($n), 1);
    return preg_match("/\s/", $first) && preg_match("/\s/", $last);
}

function validate_extension($needle, $haystack)
{
    $n = strtolower(strrchr($needle, '.'));
    $h = array_map('strtolower', $haystack);
    return in_array($n, $h);
}

function exemptMember($n, $h, $flag = false)
{
    return in_array($n, $h) ? $flag : !$flag;
}

function swapExtension($path, $ext, $sep = '')
{

    if ($sep) {
        $path = explode('/', $path);
        $path = end($path);
    }
    return preg_replace('|\.\w+$|', $ext, $path);
}


//https://www.php.net/manual/en/function.getimagesize.php
// Retrieve JPEG width and height without downloading/reading entire image.
function getjpegsize($img_loc)
{
    $img_loc = normalizePath('file_exists', trim($img_loc));
    if (fileExists($img_loc)) {
        $handle = fopen($img_loc, "rb");
        $new_block = NULL;
        if (!feof($handle)) {
            $new_block = fread($handle, 32);
            $i = 0;
            if ($new_block[$i] == "\xFF" && $new_block[$i + 1] == "\xD8" && $new_block[$i + 2] == "\xFF" && $new_block[$i + 3] == "\xE0") {
                $i += 4;
                if ($new_block[$i + 2] == "\x4A" && $new_block[$i + 3] == "\x46" && $new_block[$i + 4] == "\x49" && $new_block[$i + 5] == "\x46" && $new_block[$i + 6] == "\x00") {
                    // Read block size and skip ahead to begin cycling through blocks in search of SOF marker
                    $block_size = unpack("H*", $new_block[$i] . $new_block[$i + 1]);
                    $block_size = hexdec($block_size[1]);
                    while (!feof($handle)) {
                        $i += $block_size;
                        $new_block .= fread($handle, $block_size);
                        if ($new_block[$i] == "\xFF") {
                            // New block detected, check for SOF marker
                            $sof_marker = array("\xC0", "\xC1", "\xC2", "\xC3", "\xC5", "\xC6", "\xC7", "\xC8", "\xC9", "\xCA", "\xCB", "\xCD", "\xCE", "\xCF");
                            if (in_array($new_block[$i + 1], $sof_marker)) {
                                // SOF marker detected. Width and height information is contained in bytes 4-7 after this byte.
                                $size_data = $new_block[$i + 2] . $new_block[$i + 3] . $new_block[$i + 4] . $new_block[$i + 5] . $new_block[$i + 6] . $new_block[$i + 7] . $new_block[$i + 8];
                                $unpacked = unpack("H*", $size_data);
                                $unpacked = $unpacked[1];
                                $height = hexdec($unpacked[6] . $unpacked[7] . $unpacked[8] . $unpacked[9]);
                                $width = hexdec($unpacked[10] . $unpacked[11] . $unpacked[12] . $unpacked[13]);
                                return array($width, $height);
                            } else {
                                // Skip block marker and read block size
                                $i += 2;
                                $block_size = unpack("H*", $new_block[$i] . $new_block[$i + 1]);
                                $block_size = hexdec($block_size[1]);
                            }
                        } else {
                            return FALSE;
                        }
                    }
                }
            }
        }
    } //fof
    return FALSE;
}
// Retrieve PNG width and height without downloading/reading entire image.
function getpngsize($img_loc)
{
    $handle = fopen($img_loc, "rb") or die("Invalid file stream.");

    if (!feof($handle)) {
        $new_block = fread($handle, 24);
        if (
            $new_block[0] == "\x89" &&
            $new_block[1] == "\x50" &&
            $new_block[2] == "\x4E" &&
            $new_block[3] == "\x47" &&
            $new_block[4] == "\x0D" &&
            $new_block[5] == "\x0A" &&
            $new_block[6] == "\x1A" &&
            $new_block[7] == "\x0A"
        ) {
            if ($new_block[12] . $new_block[13] . $new_block[14] . $new_block[15] === "\x49\x48\x44\x52") {
                $width  = unpack('H*', $new_block[16] . $new_block[17] . $new_block[18] . $new_block[19]);
                $width  = hexdec($width[1]);
                $height = unpack('H*', $new_block[20] . $new_block[21] . $new_block[22] . $new_block[23]);
                $height  = hexdec($height[1]);
                return array($width, $height);
            }
        }
    }
    return false;
}

//makes some assumptions, a warning is in an array, a string is ok
function flushMsg($missing, $data, $klas = 'warning')
{
    return function ($k, $flag = false, $ret = false) use ($missing, $data, $klas) {
        $output = isset($missing[$k]) && is_array($missing[$k]) ? $missing[$k][0] : null;
        if (isset($output)) {
            //we can be adding to value field of input, or class of label
            //assumes no other class present
            if (!$ret) { //default is echo
                echo $flag ? $output : " class=$klas";
            } //but echo may be delegated so just return
            else {
                return $flag ? $output : " class=$klas";
            }
        } else {
            htmlout(trim(strip_tags($data[$k]))); //outputs

        }
    };
}

function flushMsgCb($missing, $data, $klas = 'warning')
{
    return function ($k, $flag = false) use ($missing, $data, $klas) {
        $output = isset($missing[$k]) && is_array($missing[$k]) ? $missing[$k][0] : null;
        if (isset($output)) {
            return $flag ? $output : " class=$klas";
        } else {
            htmlout(trim(strip_tags($data[$k]))); //outputs

        }
    };
}


function splitOn($str, $seps = [' ', '/'])
{
    $L = count($seps);
    $res = null;
    $ret = [];
    while ($L--) {
        $res = explode($seps[$L], $str);
        if (isset($res[1])) {
            $ret = array_map(function ($o) {
                return !is_numeric($o) ? intval($o) : $o;
            }, $res);
            break;
        }
    }
    return empty($ret) ? [$str] : $ret;
}


function getAccess($i)
{
    $lib = [null, 'Content Editors', 'Photo Editors'];
    return isset($lib[$i]) ? $lib[$i] : 'Account Administrators';
}

function getPeers($p, $b = 64)
{
    $test = ($p % $b) === $p;
    if ($test) {
        $b /= 2;
        return getPeers($p, $b);
    } else {
        return $b;
    }
}

function trimToLower($str)
{
    return strtolower(trim($str));
}

function reLocate($path, $prefix = '')
{
    header('Location: ' . $path);
    exit;
}

function retour($arg = '')
{
    header("Location: /$arg");
    exit;
}

function fixUri()
{
    $uri = $_SERVER['REQUEST_URI'];
    $uri = strtok(ltrim($uri, '/'), '?');
    $route = explode('/', $uri);
    array_unique($route);
    return $route;
}

function doBest($pred, $actions)
{
    return function (...$args) use ($pred, $actions) {
        return array_reduce($actions, function ($a, $b) use ($pred, $args) {
            return $a && $pred(...$args) ? $a : $b;
        });
    };
}

function doBestInvoke($pred, $actions)
{
    return function (...$args) use ($pred, $actions) {
        return array_reduce($actions, function ($a, $b) use ($pred, $args) {
            return $a && $pred(...$args) ? $a(...$args) : $b(...$args);
        });
    };
}

function doImmediate($pred, $actions, ...$args)
{
    return array_reduce($actions, function ($a, $b) use ($pred, $actions, $args) {
        return $a && $pred(...$args) ? $a(...$args) : $b(...$args);
    });
}

function doBestDefer($pred, $actions)
{
    return function (...$args) use ($pred, $actions) {
        return array_reduce($actions, function ($a, $b) use ($pred, $args) {
            return $a && $pred(...$args) ? partial($a, ...$args) : partial($b, ...$args);
        });
    };
}
function classify($str, $flag = null)
{
    $res = explode('.', $str);
    $ret = 'id=%s class=%s';
    $false = !$flag && is_bool($flag);
    //taking of the piss '.' yields ["",""];
    if (empty($res[0]) && (empty($res[1]) || $flag)) {
        return '';
    }

    if (empty($res[0]) || $false) { //class NO id
        $k = sprintf('class=%s', $res[1] ?? '');
        return preg_match('/=\w+/', $k) ? $k : '';
    }

    if (empty($res[1]) || $flag) {
        $id = sprintf('id=%s', $res[0] ?? '');
        return preg_match('/=\w+/', $id) ? $id : '';
    }
    return sprintf($ret, $res[0], $res[1]);
}


function validateMove($arg, $strategy = null)
{
    return $strategy ? $strategy($arg) : true;
}

function doAssign(&$arr, $i)
{
    while (!$next = array_shift($arr)) {
        $i++;
    }
    return [$next, $i];
}

function whilst($array, $cb, $i = 0)
{
    $i = $i ? count($array) : 0;
    while (isset($array[$i])) {
        if ($cb($array[$i])) {
            break;
        }

        $i++;
    }
    return $i;
}

function queryPath($pathtofile, $str)
{
    $ext = strrchr($pathtofile, '.');
    $ext = $ext ? strtolower(substr($ext, 1)) : null;
    $segments = explode('/', $pathtofile);
    if ($ext) {
        $file = array_pop($segments);
    } else {
        $subpath = array_pop($segments);
    }
    $res = null;
    switch ($str) {
        case 'ext':
            $res = $ext;
            break;
        case 'file':
            $res = $file;
            break;
        case 'parent':
            $res = $file ? end($segments) : $subpath;
            break;
        default:
            $res = implode('/', $segments) . '/';
    }
    return $res;
}

function getPortion($h, $sep = '/', $bool = false)
{
    if ($h) {
        return substr(strrchr($h, $sep, $bool), 1);
    }
    return $h;
}

function safeFilter($array, $cb)
{
    return array_values(array_filter($array, $cb));
}

function safeList($array, $i)
{
    $n = ($i - 1);
    $i = 0;
    $ret = [];
    while ($i <= $n) {
        if (!isset($array[$i])) {
            $ret[] = null;
        } else {
            $ret[] = $array[$i];
        }
        $i++;
    }
    return $ret;
}


function myScanDir(string $path, array $blacklist = ['thumb'])
{
    if (isDir($path)) {
        $directories = scanDirectory($path);
        $directories = array_diff($directories, $blacklist);
        //filter directories that match words not '..' etc..
        $f = partial('preg_match', '|^\w+|');
        //$g = partial('preg_match', '|^\w+$|');
        $dirs = safeFilter($directories, $f);
        return $dirs;
    }
    return [];
}

function arrayDiff($arr, ...$args)
{
    return array_values(array_diff($arr, ...$args));
}

function scanMultiDir(array $ubers, string $thefile, array $blacklist = [])
{
    //ensure uberpath has subdirectories
    //files/home/home.jpg we need to start from files/ not files/home/
    //issue with same file existing in two folders discovered when uncropped gallery image found in articles

    $map = function ($o) use ($blacklist) {
        $dir = myScanDir($o, $blacklist);
        $isFile = fn($agg, $cur) => $agg && preg_match('/\w+\.\w+$/', $cur);
        $allfiles = array_reduce($dir, $isFile, true);
        if ($allfiles) { //safe if all files, but not if a mix of files/subdirectories
            //remove last segment "path/to/file" becomes "path/to/"
            return preg_replace('|\/\w+\/$|', '/', $o);
        }
        return $o;
    };

    $uberpaths = array_map($map, $ubers);
    $uberpaths = safeFilter($uberpaths, 'identity');
    $scan = curry2('myScanDir')($blacklist);
    $scandirs = array_map($scan, $uberpaths);
    $scans = [];
    //issue with directory that contains both subdirectories and files
    //we need to read them but must filter out actual files
    foreach ($scandirs as $dir) {
        $L = count($dir);
        $ret = [];
        for ($i = 0; $i < $L; $i++) {
            $str = isset($dir[$i]) ? $dir[$i] : '';
            if ($str) {
                $ret[] = $str;
            }
            if ($str && preg_match("|\.\w+$|", $str)) {
                array_pop($ret);
            }
        }
        $scans[] = $ret;
    }

    $scandirs = $scans;
    //can't use function statement here, must assign to var (redclare)
    $callback = function ($uberpaths, $scandirs, $file) {

        $drive = function ($i = 0, $j = 0) use ($uberpaths, $scandirs, $file, &$drive) {
            $uberpath = $uberpaths[$j] ?? '';
            if ($uberpath) {
                $scandir = $scandirs[$j];
            }
            $subpath = isset($scandir[$i]) ? $scandir[$i] : '';
            $subpath = isDir("$uberpath$subpath") ? $subpath : '';

            // if($i > 1) var_dump($scandirs, $j);
            if (!$subpath && isset($scandirs[$j + 1])) {
                return $drive(0, $j += 1);
            } else if (!$subpath) {
                return '';
            }
            $path = "$uberpath$subpath/$file";
            list($path) = findfile("$uberpath$subpath/", $file, 2);
            if ($path) {
                return "$uberpath$subpath/$path";
            }
            return $drive($i += 1, $j);
        };
        return $drive;
    };

    return $callback($uberpaths, $scandirs, $thefile)();
}

function safeScanDir($directory)
{
    $scandir = doWhen('identity', 'scandir');
    $filter = partial('preg_grep', "/^[^\.]/");
    $getValues = doWhen('identity', 'array_values');
    $getDirFiles = composer($getValues, $filter, $scandir, 'isDir');
    return $getDirFiles($directory);
}

function isNull($o, $p)
{
    $arg = isset($o[$p]) ? $o[$p] : null;
    return is_null($arg);
}

//https://stackoverflow.com/questions/8311074/how-to-call-the-current-anonymous-function-in-php
//https://stackoverflow.com/questions/2480179/anonymous-recursive-php-functions

function driver($strategy)
{
    $drive = function ($end, &$van, &$places, $i) use ($strategy, &$drive) {
        if (!count($van)) return;
        $nextItem = $places[$i];
        $place = null;
        if ($end) { //end is false until we reach the NULL item
            //inward skip NULL items in van
            //eg only connery bonds pass for reassigning['frwl', 'yolt', NULL, 'daf', NULL]
            list($nextItem, $j) = doAssign($van, 1);
            $places[$i] = $nextItem;
            return $drive($end, $van, $places, $i - $j);
            //the location of the item to be moved is set to NULL and put on the van
            //no further processing of $places is required
            //$place = isset($places[$i + 1]) ? $places[$i + 1] : null;
        } else if (isNull($places, $i + 1)) {
            //arrival
            $j = 0;
            $pass = validateMove($nextItem, $strategy);
            if (!$pass) {
                list($nextItem, $j) = doAssign($van, 1);
            }
            $places[$i + 1] = $nextItem;
            return $drive(true, $van, $places, $i - $j);
        } else {
            //outward, populate $van set failed items to null
            $nextItem = validateMove($nextItem, $strategy) ? $nextItem : null;
            array_unshift($van, $nextItem);
            return $drive($end, $van, $places, $i + 1);
        }
    };
    return $drive;
}
//simple version that requires no strategy
function drive($end, &$van, &$places, $i)
{
    //$i === 'destination';
    //$places [a,b,c,d...]
    //$van [a||b||c||d...]; firstLabel
    if (!isset($van[0])) return;
    $nextLabel = $places[$i];
    if ($end) {
        $places[$i] = array_shift($van);
        return drive($end, $van, $places, $i - 1);
    } else if (empty($places[$i + 1])) {
        $places[$i + 1] = $nextLabel;
        $end = true;
        return drive($end, $van, $places, $i);
    } else {
        array_unshift($van, $nextLabel);
        $places[$i] = null;
        return drive($end, $van, $places, $i + 1);
    }
}
//!! $flag indicate whether $args should be supplied as an array to call_user_func_array
function negate($predicate, $flag = false)
{
    return function (...$args) use ($predicate, $flag) {
        return $flag ? !$predicate($args) : !$predicate(...$args);
    };
}


function alternate($predicate)
{
    return function ($arg) use (&$predicate) {
        $i = $predicate($arg);
        if (!$i) {
            $predicate = negate($predicate);
        }
        return $i;
    };
}

//https://medium.com/@assertchris/function-composition-c8094ae9be63
$reduce = function ($result, $item) {
    return !is_array($result) ? call_user_func_array($item, [$result]) : call_user_func_array($item, $result);
};

$compose = function () use ($reduce) {
    $callbacks = func_get_args();
    return function () use ($callbacks, $reduce) {
        return array_reduce($callbacks, $reduce, func_get_args());
    };
};


function myReducer($agg, $f)
{
    $agg = !is_array($agg) ? array($agg) : $agg;
    return call_user_func_array($f, $agg);
}

function compose($reducer)
{
    return function () use ($reducer) {
        $callbacks = func_get_args();
        return function () use ($callbacks, $reducer) {
            return array_reduce($callbacks, $reducer, func_get_args());
        };
    };
}

//assumes a seed value (or two)
function modCompose(...$fns)
{
    return function (...$args) use ($fns) {
        return array_reduce($fns, function ($val, $fn) {
            return $fn($val);
        }, ...$args);
    };
}

/*
in case we need it
function composeroo(...$fns) {
    return array_reduce($fns, function ($f, $g) {
        return function(...$vals) use($f, $g) { 
            return $f($g(...$vals));
        };
    }, 'identity');
}
    */

//works like JS version but requires initial identity function??
function composer(...$fns)
{
    return array_reduce($fns, fn($f, $g) => fn(...$vals) => $f($g(...$vals)), 'identity');
}

function tester($i = 100)
{
    $add = fn($a, $b) => $a + $b;
    $sub = fn($a, $b) => $a - $b;
    $div = fn($a, $b) => $a / $b;
    $perform = composer(curry2($add)(9), curry2($sub)(1), curry2($div)(2));
    dump($perform($i));
}

function unsetCookie($str)
{
    unset($_COOKIE[$str]);
    setcookie($str, '', -1, '/');
}

function doSetCookie($flag)
{
    return function ($k, $v = '', $time = -1) use ($flag) {

        //need if undefined here
        if (!is_string($v)) {
            if (!is_int($v)) {
                $v = $k;
            }
        }

        if (!is_int($v)) {
            if (!is_string($v)) {
                $v = $k;
            }
        }

        if (!isset($_COOKIE[$k]) && $flag) {
            setcookie($k, $v, $time, '/');
            $_COOKIE[$k] = $v;
        } elseif (isset($_COOKIE[$k]) && !$flag) {
            unset($_COOKIE[$k]);
            setcookie($k, '', -1, '/');
        }
    };
}

function doCookie($k, $v, $sep = ';')
{
    if (isset($_COOKIE[$k])/* && strpos($v, $sep)*/) {
        $store = explode($sep, $_COOKIE[$k]);
        $arr = explode($sep, $v);
        $arr = array_merge($store, $arr);
        $arr = array_unique($arr);
        $v = implode($sep, $arr);
        unset($_COOKIE[$k]);
        setcookie($k, '', -1, '/');
    }
    setcookie($k, $v, time() + 60 * 60 * 24 * 30, '/');
    return $v;
}


function doInclude($content, $template = '_image_article.html.php')
{
    if (preg_match('/\w+\.html\.php$/', $content)) {
        return $content;
    }
    return $template;
}

function formatError($e)
{
    $ret = implode(preg_split('/SQLSTATE\[[^:]+:/', $e));
    return implode(preg_split('/:\s\d+/', $ret));
}

function doPreparedQuery($st, $values = [], $msg = '')
{
    try {
        $bool = $st->execute($values);
        // dump(is_bool($bool));
        return $bool && is_bool($bool) ? $st : null;
    } catch (PDOException $e) {
        $error = $msg . ' ' . $e->getMessage();
        $error = formatError($error);
        return $error;
    }
}


function orderByList($data, $list, $a, $b)
{
    function foo(&$coll, $key)
    {
        return function ($o) use (&$coll, $key) {
            return $coll[$o[$key]] = $o;
        };
    }

    $assoc = [];
    $foo = foo($assoc, $a);
    foreach ($data as $d) {
        $foo($d);
    }
    $coll = [];

    foreach ($list as $k) {
        if (!isset($assoc[$k])) {
            continue;
        }
        $coll[$k] = $assoc[$k][$b];
    }
    return $coll;
}

function getRatio($w, $h)
{
    $r = $w / $h;
    $ret = $r < 1 ? $h / $w : $r;
    return round($ret, 1);
}

function getResult($arg)
{
    if (is_callable($arg)) {
        return $arg();
    }
    return $arg;
}

function getRandomString($n)
{
    return bin2hex(random_bytes($n / 2));
}

function pass($cb, $arg)
{
    $res = $cb($arg);
    return $res ? $res : $arg;
}


function markdownTidy2($content)
{
    //$content = preg_replace('/\s\[\s*([^\]]+?)\s*\](\[|\()/', '[$1]$2', $content);
    //[ oh my ] : [oh my]
    // [no label follows] : no label follows but watch out for [\[link]\]]
    //$content = preg_replace('/(\w)([,;])\S/', '$1$2', $content);
    //$content = preg_replace('/([^\s\]])\(([^(])/', '$1 ($2', $content);
    //$content = preg_replace('/\)(\S)/', ') $1', $content);
    //[Ficksburg][3] (25km away) without the space [Ficksburg][3](25km away) our code will think it is dealing with an inline link; need to fix it
    //hello"quot"
    //<nav markdown="1">...
    //  $content = preg_replace('/([^"\s])"([^"\.\s])/', '$1 "$2', $content);
    //prevents this malarkey: Polo[africa][2]
    // $content = preg_replace('/(.)([^*]+)\*+([^*]+)\*+([^*]+)(.)/', ' $1$2$3$4$5 ', $content);
    //[![my icon][1]](#){.right}
    $content = preg_replace('/\s?(\w+)\[([^]]+)\]\[([^]]+)\](\w*)\s?/', ' [$1$2$4][$3] ', $content);
    //$content = preg_replace('/\s?(\S*)\[([^]]+)\]\[([^]]+)\](\S*)\s?/', ' [$1$2$4][$3] ', $content);
    return $content;
}

function markdownTidy($content)
{
    $content = preg_replace('/(?<=!)!+(?=\[)/', '', $content);
    $content = preg_replace('/\[+\[(?=!)+/', '[', $content);
    $content = preg_replace('/\]\]\]+/', ']]', $content);
    $content = preg_replace('/\[\[+/', '[', $content);
    $content = preg_replace('/\](?<=\()[^[]+\]/', ']]', $content);
    $content = preg_replace('/(?<=\s)\[\s*([^\]]+?)\s*\]/', '[$1]', $content);
    $content = preg_replace('/(?<=\s)\[([^\]]+)\](?![[(:\]])/', '$1', $content);
    $content = preg_replace('/([a-zA-Z])(,)([a-zA-Z])/', '$1$2 $3', $content);
    $content = preg_replace('/(\]\[[^\]]+\])\(/', '$1 (', $content);
    $content = preg_replace('/\]\(\s*(\S)/', ']($1', $content);
    $content = preg_replace_callback('/\.\h+([^\n])/', fn($m) => strtoupper($m[0]), $content);
    return markdownTidy2($content);
}



function markdownDoubles($content, $char)
{
    $rpl = substr($char, 1) ? substr($char, 1) : $char;
    $wrapped = preg_match('/\]\](\[|\()/', $content); // [![my icon][6]][5]
    return $wrapped ? $content : preg_replace("|(?<!\\\\)$char$char|", $rpl, $content);
}
function checkMarkdownFormatting($content)
{
    $brackets = ['()', '{}', '""', '[]'];
    $msg = ['para', 'brace', 'quote', 'bracket'];
    $i = 0;
    $ran = false;
    $key = '';
    $escaped = preg_match('|(?<=\\\\)\[|', $content);
    if (!$escaped) {
        $ran = true;
        while (isset($brackets[$i])) {
            if (!checkBrackets($content, $brackets[$i])) {
                break;
            }
            $i++;
        }
    }
    $key = $ran && isset($msg[$i]) ? $msg[$i] : '';
    //kludge to allow escaped square brackets [\[Google Map\]][5].
    //'/(?<=\S)\*\w/' '/\S\*+\s\S/','starstart', 
    // 'star' =>'/(\S\*+?\S)/'

    if (!$key) {
        $regs = ['|<\/?>|', '/\S<[^>]+>\S/',  '/{{[^}]+}}/', '/\[\[/', '/\[[^\]]+\]\([^)]+(?:\s[^"]*\)|\s"[^"]+"[^)])/', '/\]\([^)]+\)[{^\s\n\r\.,]]/', '/\]?\[\d{3,}\]:?/', '/(?<!#)\[[^\]]+\]\[\w{12,}\](?!:)/'];
        $msgs = ['emptytag', 'span', 'doublebrace', 'doublebracket', 'attr', 'postattr', 'indexnum', 'indexlimit'];
        $regs2 = ['/::/', '/;;/', '/==/', '/\-\-/', '/\.\./', '/,\S/', '/\W{7,}/'];
        $msgs2 = ['doublecolon', 'doublesemicolon', 'doubleequals', 'doublehyphens', 'doubleperiod', 'doublecomma', 'nonword'];
        $key = '';
        $i = 0;
        $pregs = array_map(curry22('preg_match')($content), $regs);
        foreach ($pregs as $k => $f) {
            if ($f()) {
                $key = $msgs[$k] ?? null;
                break;
            }
        }
    }
    return $key;
}

function foobar($haystack, $needle, $str)
{
    $haystack = explode(' ', strtolower($haystack));
    $needle = explode(' ', strtolower($needle));
    $word = array_intersect($haystack, $needle);

    if (isset($word[1])) {
        $word = implode(' ', $word);
    } else {
        $word = current($word);
    }
    $orig = $word;
    //$pre = findMatch("/\[?\w+(?=\s$word)/i", $str, 0);
    $aft = [];
    $pre = [];
    $i = 0;

    while ($word = preg_match("/(?<=$word\s)\w+(?:\]\[(\d+)\])?/i", $str, $m)) {
        if (!empty($m)) {
            $j = stripos($m[0], '[');
            if ($j) {
                list($word) = (explode(']', $m[0]));
                $aft[] = $word;
                $i = $m[1];
                break;
            } else {
                $word = $m[0];
                $aft[] = $word;
            }
        } else {
            break;
        }
    }
    $word = $orig;
    //$pre = findMatch("/\w+(?=\s\[?$word)/i", $str, 0);

    while ($word = preg_match("/\[?\w+(?=\s$word)/i", $str, $m)) {
        if (!empty($m)) {
            $j = stripos($m[0], '[');
            if (is_numeric($j)) {
                list($_, $word) = explode('[', $m[0]);
                $pre[] = $word;
                break;
            } else {
                $word = $m[0];
                $pre[] = $word;
            }
        } else {
            break;
        }
    }

    return implode(' ', array_merge([$pre], [$orig], $aft));
    /*
   

    $string = "This is a string, keyword next, some more text. keyword next-word. keyword another_word. Okay, keyword do-rae-mi-fa_so_la.";
    // Set the regex.
    $regex = '/(?<=\bkeyword\s)(?:[\w-]+)/is';
    // Run the regex with preg_match_all.
    preg_match_all($regex, $string, $matches);
    // Dump the resulst for testing.
    echo '<pre>';
    print_r($matches);
    echo '</pre>';
    */
}



function cc($unpaid, $balance, $dur, $rate)
{
    while ($dur) {
        $min = ($unpaid * $rate) + ($balance / 100);
        $unpaid -= $min;
        $balance -= $min;
        $dur--;
    }
    return [$unpaid, $balance];
}

function tsb($unpaid, $balance, $dur, $rate)
{
    while ($dur) {
        $min = $balance * .02;
        $unpaid -= $min;
        $balance -= $min;
        $dur--;
    }
    return [$unpaid, $balance];
}
