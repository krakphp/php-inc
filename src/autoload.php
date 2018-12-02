<?php

namespace Krak\PhpInc;

// Work around to prevent the php functions from being redeclared due to composer plugin semantics.

if (defined('Krak\\PhpInc\\_INCLUDED')) {
    return;
}

const _INCLUDED = 1;

require_once __DIR__ . '/autoload.php';
