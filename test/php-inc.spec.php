<?php

namespace Krak\PhpInc;

use function iter\map;

class MockFile {
    public $pathname;
    public function __construct($pathname) {
        $this->pathname = $pathname;
    }

    public function getPathname() {
        return $this->pathname;
    }
}

describe('Krak Php Inc', function() {
    describe('#genIncFile', function() {
        it('generates an inc file with sorted files', function() {
            $gen = genIncFile();
            $files = map(function($file) {
                return new MockFile($file);
            }, [
                "/base/b",
                "/base/a",
                "/base/B",
            ]);

            $expected = <<<FILE
<?php

require_once __DIR__ . '/B';
require_once __DIR__ . '/a';
require_once __DIR__ . '/b';

FILE;
            assert($expected == $gen('/base', $files));
        });
    });
});
