<?php

namespace Krak\PhpInc;

require_once __DIR__ . '/autoload.php';

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Krak\PhpInc\Command\GenerateCommand;

class PhpIncPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * Apply plugin modifications to Composer
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io) {}

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     * * The method name to call (priority defaults to 0)
     * * An array composed of the method name to call and the priority
     * * An array of arrays composed of the method names to call and respective
     *   priorities, or 0 if unset
     *
     * For instance:
     *
     * * array('eventName' => 'methodName')
     * * array('eventName' => array('methodName', $priority))
     * * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents() {
        return [
            'pre-autoload-dump' => 'onPreAutoloadDump',
        ];
    }

    public function onPreAutoloadDump(Event $event) {
        $package = $event->getComposer()->getPackage();

        $rootDir = dirname($event->getComposer()->getConfig()->get('vendor-dir'));
        $phpIncConfig = \array_merge($this->createDefaultConfig(), $package->getExtra()['php-inc'] ?? []);

        $rootPrefix = $rootDir . '/';

        $srcPath = $phpIncConfig['src-path'] ?? null;
        $srcDir = $srcPath ? $rootDir . '/' . $srcPath : null;
        $testPath = $phpIncConfig['test-path'] ?? null;
        $testDir = $testPath ? $rootDir . '/' . $testPath : null;
        $matches = $phpIncConfig['matches'] ?? null;
        $devSrcMatches = $phpIncConfig['matches-dev-src'] ?? null;
        $devTestMatches = $phpIncConfig['matches-dev-test'] ?? null;

        $additionalAutoloadFiles = [];
        $additionalAutoloadDevFiles = [];

        if ($srcDir && \file_exists($srcDir)) {
            $sourceDir = $rootDir . '/' . $srcPath;
            $additionalAutoloadFiles = $this->matchFiles($rootPrefix, $matches, $sourceDir);
            $additionalAutoloadDevFiles = $this->matchFiles($rootPrefix, $devSrcMatches, $sourceDir);
        }
        if ($testPath && \file_exists($testDir)) {
            $testDir = $rootDir . '/' . $testPath;
            $additionalAutoloadDevFiles = \array_merge($additionalAutoloadDevFiles, $this->matchFiles($rootPrefix, $devTestMatches, $testDir));
        }

        if ($event->getIO()->isVerbose()) {
            $event->getIO()->write('<info>php-inc generating autoload files.</info>');
            $event->getIO()->write('Autoload Files:' . "\n" . \implode("\n", $additionalAutoloadFiles));
            $event->getIO()->write('Autoload Dev Files:' . "\n" . \implode("\n", $additionalAutoloadDevFiles));
        }

        $autoload = $package->getAutoload();
        $autoload['files'] = \array_unique(\array_merge($autoload['files'] ?? [], $additionalAutoloadFiles));
        $package->setAutoload($autoload);
        $autoloadDev = $package->getDevAutoload();
        $autoloadDev['files'] = \array_unique(\array_merge($autoloadDev['files'] ?? [], $additionalAutoloadDevFiles));
        $package->setDevAutoload($autoloadDev);
    }

    private function matchFiles(string $rootPrefix, ?array $matches, string $dir): array {
        if (!$matches) {
            return [];
        }

        return _toArray(scannedFilesToIncludePaths($rootPrefix, scanSrc(astMatchFactory($matches))($dir)));
    }

    private function writeVerbose(IOInterface $io, string $line) {
        if (!$io->isVerbose()) {
            return;
        }

        $io->write($line);
    }

    /** @return mixed[] */
    private function createDefaultConfig(): array {
        // this is pulled directly from the README config
        return json_decode(<<<JSON
{
  "src-path": "src",
  "test-path": "tests",
  "matches": {
    "type": "and",
    "matches": [
      {"type":  "ext", "exts":  ["php"]},
      {"type":  "lowerCase"},
      {"type":  "excludePath", "path":  "@.*/(Resources|Tests)/.*@"}
    ]
  },
  "matches-dev-src": {
    "type": "and",
    "matches": [
      {"type":  "ext", "exts":  ["php"]},
      {"type":  "lowerCase"},
      {"type":  "includePath", "path":  "@.*/Tests/.*@"},
      {"type":  "excludePath", "path":  "@.*/Tests/.*/Fixtures/.*@"}
    ]
  },
  "matches-dev-test": {
    "type": "and",
    "matches": [
      {"type":  "ext", "exts":  ["php"]},
      {"type":  "lowerCase"},
      {"type":  "excludePath", "path":  "@.*/Fixtures/.*@"}
    ]
  }
}
JSON, true);
    }
}
