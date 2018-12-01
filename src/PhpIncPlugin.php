<?php

namespace Krak\PhpInc;

require_once __DIR__ . '/php-inc.php';

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
        $phpIncConfig = $package->getExtra()['php-inc'] ?? [];

        $sourceDir = $rootDir . '/' . ($phpIncConfig['src-path'] ?? 'src');
        $testDir = $rootDir . '/' . ($phpIncConfig['test-path'] ?? 'tests');
        $standardMatches = [
            ['type' => 'ext', 'exts' => ['php']],
            ['type' => 'lowerCase'],
        ];
        $matches = $phpIncConfig['matches'] ?? [
            'type' => 'and',
            'matches' => \array_merge($standardMatches, [
                ['type' => 'excludePath', 'path' => '@.*/(Resources|Tests)/.*@']
            ])
        ];
        $devSrcMatches = $phpIncConfig['matches-dev-src'] ?? [
            'type' => 'and',
            'matches' => \array_merge($standardMatches, [
                ['type' => 'includePath', 'path' => '@.*/Tests/.*@'],
                ['type' => 'excludePath', 'path' => '@.*/Tests/.*/Fixtures/.*@'],
            ]),
        ];
        $devTestMatches = $phpIncConfig['matches-dev-test'] ?? [
            'type' => 'and',
            'matches' => \array_merge($standardMatches, [
                ['type' => 'excludePath', 'path' => '@.*/Fixtures/.*@'],
            ]),
        ];

        $rootPrefix = $rootDir . '/';
        $additionalAutoloadFiles = _toArray(scannedFilesToIncludePaths($rootPrefix, scanSrc(astMatchFactory($matches))($sourceDir)));
        $additionalDevSrcAutoloadFiles = _toArray(scannedFilesToIncludePaths($rootPrefix, scanSrc(astMatchFactory($devSrcMatches))($sourceDir)));
        $additionalDevTestAutoloadFiles = _toArray(scannedFilesToIncludePaths($rootPrefix, scanSrc(astMatchFactory($devTestMatches))($testDir)));

        if ($event->getIO()->isVerbose()) {
            $event->getIO()->write('<info>php-inc generating autoload files.</info>');
            $event->getIO()->write('Autoload Files:' . "\n" . \implode("\n", $additionalAutoloadFiles));
            $event->getIO()->write('Autoload Dev Files:' . "\n" . \implode("\n", \array_merge($additionalDevSrcAutoloadFiles, $additionalDevTestAutoloadFiles)));
        }

        $autoload = $package->getAutoload();
        $autoload['files'] = \array_unique(\array_merge($autoload['files'] ?? [], $additionalAutoloadFiles));
        $package->setAutoload($autoload);
        $autoloadDev = $package->getDevAutoload();
        $autoloadDev['files'] = \array_unique(\array_merge($autoload['files'] ?? [], $additionalDevSrcAutoloadFiles, $additionalDevTestAutoloadFiles));
        $package->setDevAutoload($autoloadDev);
    }
}
