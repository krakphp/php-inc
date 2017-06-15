<?php

namespace Krak\PhpInc;

use function iter\reduce,
    iter\filter,
    iter\toArray;

/** only match lower case filenames */
function lowerCaseMatch() {
    return function($finfo) {
        $first = $finfo->getFilename()[0];
        return ctype_lower($first);
    };
}

function extMatch($ext) {
    if (!is_array($ext)) {
        $ext = [$ext];
    }

    return function($finfo) use ($ext) {
        return in_array($finfo->getExtension(), $ext);
    };
}

/** exclude certain paths, if the path matches the re, it will be excluded */
function excludePathMatch($path_re) {
    return function($finfo) use ($path_re) {
        $res = preg_match($path_re, $finfo->getPathname());
        return !boolval($res);
    };
}

function orMatch($matches) {
    return function($finfo) use ($matches) {
        foreach ($matches as $match) {
            if ($match($finfo)) {
                return true;
            }
        }

        return false;
    };
}

function andMatch($matches) {
    return function($finfo) use ($matches) {
        foreach ($matches as $match) {
            if (!$match($finfo)) {
                return false;
            }
        }

        return true;
    };
}

function scanSrc($match) {
    return function($path) use ($match) {
        $files = new \RecursiveDirectoryIterator($path);
        $files = new \RecursiveIteratorIterator($files);
        $files = filter(function($finfo) {
            return $finfo->isFile();
        }, $files);
        $files = filter(function($file) use ($match) {
            return $match($file);
        }, $files);
        return $files;
    };
}

function genIncFile() {
    return function($base, $files) {
        $files = toArray($files);
        usort($files, function($a, $b) {
            return strcmp($a->getPathname(), $b->getPathname());
        });
        $include_php = reduce(function($acc, $file) use ($base) {
            return $acc . sprintf(
                "require_once __DIR__ . '%s';\n",
                str_replace($base, '', $file->getPathname())
            );
        }, $files, '');

        return sprintf("<?php\n\n%s", $include_php);
    };
}

function phpInc($scan, $gen) {
    return function($path) use ($scan, $gen) {
        $path = realpath($path);
        return $gen($path, $scan($path));
    };
}
