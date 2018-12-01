<?php

namespace Krak\PhpInc;

describe(astMatchFactory, function() {
   it('can create complex matches from ast', function() {
      $expectedMatcher = andMatch([
          orMatch([
              lowerCaseMatch(),
              extMatch(['phpf'])
          ]),
          excludePathMatch('/abc/')
      ]);
      $files = [
          new \SplFileInfo('foo.php'),
          new \SplFileInfo('Foo.phpf'),
          new \SplFileInfo('foo-abc.php')
      ];
      $expectedFiles = _toArray(_filter($expectedMatcher, $files));
      $match = astMatchFactory([
          'type' => 'and',
          'matches' => [
              ['type' => 'or', 'matches' => [
                  ['type' => 'lowerCase'],
                  ['type' => 'ext', 'exts' => ['phpf']]
              ]],
              ['type' => 'excludePath', 'path' => '/abc/']
          ]
      ]);
      $matchedFiles = _toArray(_filter($match, $files));
      expect($matchedFiles)->equal($expectedFiles);
   });
});
