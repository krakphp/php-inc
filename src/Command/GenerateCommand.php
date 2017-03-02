<?php

namespace Krak\PhpInc\Command;

use Krak\PhpInc,
    Symfony\Component\Console,
    Webmozart\PathUtil\Path;

class GenerateCommand extends Console\Command\Command
{
    protected function configure() {
        $this->setName('php-inc:generate')
            ->setDescription('Generate a php inc file for source files')
            ->addArgument(
                'path',
                Console\Input\InputArgument::REQUIRED,
                'The path of the source files to scan for'
            )
            ->addOption(
                'output',
                'o',
                Console\Input\InputOption::VALUE_REQUIRED,
                'The output file to write too. This file will be exluded in inc matching'
            );
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output) {
        $o = $input->getOption('output');
        $o = $o ? Path::makeAbsolute($o, getcwd()) : null;
        $match = PhpInc\andMatch(array_filter([
            PhpInc\extMatch(['php']),
            PhpInc\lowerCaseMatch(),
            PhpInc\excludePathMatch('/.*\/Resources\/.*/'),
            $o ? PhpInc\excludePathMatch("@^$o$@") : null
        ]));

        $scan = PhpInc\scanSrc($match);
        $gen = PhpInc\genIncFile();
        $phpinc = PhpInc\phpInc($scan, $gen);
        $inc_file = $phpinc($input->getArgument('path'));

        if ($o) {
            file_put_contents($o, $inc_file);
        } else {
            $output->write($inc_file);
        }
    }
}
