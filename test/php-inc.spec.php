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

describe('PhpIncPlugin', function() {
    describe('preAutoLoadDump listener', function() {
        beforeEach(function() {
            $this->event = new \Composer\Script\Event(
                'preAutoLoadDump',
                (function() {
                    $composer = new \Composer\Composer();
                    $composer->setConfig((function() {
                        $config = new \Composer\Config();
                        $config->merge(['config' => ['vendor-dir' => __DIR__ . '/fixtures/vendor']]);
                        return $config;
                    })());
                    $composer->setPackage(new \Composer\Package\RootPackage('Test Package', 'v0.1', 'Version 0.1'));
                    return $composer;
                })(),
                new \Composer\IO\NullIO()
            );
        });

        it('appends autoload and autoload-dev according to matched files', function() {
            $plugin = new PhpIncPlugin();
            $this->event->getComposer()->getPackage()->setAutoload(['files' => ['src/foo.php']]);
            $this->event->getComposer()->getPackage()->setDevAutoload(['files' => ['tests/bar.php']]);
            $plugin->onPreAutoloadDump($this->event);
            $package = $this->event->getComposer()->getPackage();
            expect($package->getAutoload()['files'])->equal(['src/foo.php', 'src/b.php']);
            expect($package->getDevAutoload()['files'])->equal(['tests/bar.php', 'src/Tests/c.php', 'tests/d.php']);
        });
        it('does not include if dir does not exist', function() {
            $plugin = new PhpIncPlugin();
            $this->event->getComposer()->getPackage()->setExtra(['php-inc' => ['test-path' => 'bad']]);
            $plugin->onPreAutoloadDump($this->event);
            $package = $this->event->getComposer()->getPackage();
            expect($package->getAutoload()['files'])->equal(['src/b.php']);
            expect($package->getDevAutoload()['files'])->equal(['src/Tests/c.php']);
        });
    });
});
