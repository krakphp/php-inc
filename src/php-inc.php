<?php

namespace Krak\PhpInc;

// Functional Utilities

function _reduce(callable $reduce, iterable $iter, $acc = null) {
    foreach ($iter as $key => $value) {
        $acc = $reduce($acc, $value);
    }
    return $acc;
}
function _filter(callable $fn, iterable $iter) {
    foreach ($iter as $k => $v) {
        if ($fn($v)) {
            yield $k => $v;
        }
    }
}
function _toArray(iterable $iter) {
    $values = [];
    foreach ($iter as $v) {
        $values[] = $v;
    }
    return $values;
}

/** Create matches from an AST  */
const astMatchFactory = __NAMESPACE__ . '\\astMatchFactory';
function astMatchFactory(array $matchRoot) {
    switch ($matchRoot['type']) {
    case 'and':
        if (!isset($matchRoot['matches'])) {
            throw new \InvalidArgumentException('Expected \'and\' matcher to include matches key.');
        }
        return andMatch(\array_map(astMatchFactory, $matchRoot['matches']));
    case 'or':
        if (!isset($matchRoot['matches'])) {
            throw new \InvalidArgumentException('Expected \'or\' matcher to include matches key.');
        }
        return orMatch(\array_map(astMatchFactory, $matchRoot['matches']));
    case 'lowerCase':
        return lowerCaseMatch();
    case 'ext':
        if (!isset($matchRoot['exts'])) {
            throw new \InvalidArgumentException('Expected \'ext\' matcher to include exts key.');
        }
        return extMatch($matchRoot['exts']);
    case 'excludePath':
        if (!isset($matchRoot['path'])) {
            throw new \InvalidArgumentException('Expected \'excludePath\' matcher to include path key.');
        }
        return excludePathMatch($matchRoot['path']);
    case 'includePath':
        if (!isset($matchRoot['path'])) {
            throw new \InvalidArgumentException('Expected \'includePath\' matcher to include path key.');
        }
        return includePathMatch($matchRoot['path']);
    default:
        throw new \InvalidArgumentException('Unexpected php-inc match type: ' . $matchRoot['type']);
    }
}

/** only match lower case filenames */
function lowerCaseMatch() {
    return function(\SplFileInfo $finfo) {
        $first = $finfo->getFilename()[0];
        return ctype_lower($first);
    };
}

function extMatch(array $exts) {
    return function(\SplFileInfo $finfo) use ($exts) {
        return in_array($finfo->getExtension(), $exts);
    };
}

/** exclude certain paths, if the path matches the re, it will be excluded */
function excludePathMatch(string $path_re) {
    return function(\SplFileInfo $finfo) use ($path_re) {
        $res = preg_match($path_re, $finfo->getPathname());
        return !boolval($res);
    };
}

/** include certain paths, if the path matches the re, it will be included */
function includePathMatch(string $path_re) {
    $match = excludePathMatch($path_re);
    return function(\SplFileInfo $finfo) use ($match) {
        return !$match($finfo);
    };
}

function orMatch(iterable $matches) {
    return function($finfo) use ($matches) {
        foreach ($matches as $match) {
            if ($match($finfo)) {
                return true;
            }
        }
        return false;
    };
}

function andMatch(iterable $matches) {
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
        $files = _filter(function(\SplFileInfo $finfo) {
            return $finfo->isFile();
        }, $files);
        $files = _filter(function(\SplFileInfo $file) use ($match) {
            return $match($file);
        }, $files);
        return $files;
    };
}


/** @param \SplFileInfo[] $files */
function scannedFilesToIncludePaths(string $base, iterable $files) {
    foreach ($files as $file) {
        yield str_replace($base, '', $file->getPathname());
    }
}

function genIncFile() {
    return function($base, $files) {
        $files = _toArray($files);
        usort($files, function(\SplFileInfo $a, \SplFileInfo $b) {
            return strcmp($a->getPathname(), $b->getPathname());
        });
        $include_php = _reduce(function(string $acc, \SplFileInfo $file) use ($base) {
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
