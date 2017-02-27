<?php

namespace Krak\PhpInc\Command;

use Krak\PhpInc,
    Symfony\Component\Console;

class GenerateCommand extends Console\Command\Command
{
    protected function configure() {
        $this->setName('php-inc:generate')
            ->setDescription('Generate a php inc file for source files')
            ->addArgument(
                'path',
                Console\Input\InputArgument::REQUIRED,
                'The path of the source files to scan for'
            );
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output) {
        $match = PhpInc\andMatch([
            PhpInc\extMatch(['php']),
            PhpInc\lowerCaseMatch(),
            PhpInc\excludePathMatch('/.*\/Resources\/.*/'),
        ]);
        $scan = PhpInc\scanSrc($match);
        $gen = PhpInc\genIncFile();
        $phpinc = PhpInc\phpInc($scan, $gen);
        $output->write($phpinc($input->getArgument('path')));
    }
}
