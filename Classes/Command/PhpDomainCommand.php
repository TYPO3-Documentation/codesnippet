<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace T3docs\Codesnippet\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use T3docs\Codesnippet\Util\CodeSnippetCreator;

/**
 * This command will parse the given codesnippet.php file with all its sub-configurations
 * to build restructured text interpretations of the configured files.
 * The command is called PhpDomain, because it will convert short classnames
 * to their FQCN.
 */
class PhpDomainCommand extends Command
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
    {
        $this->setHelp('This command will loop through all configured files in codesnippet.php '
            . LF . 'and creates a restructured interpretation of the configured file.')
            ->setDescription('Command to create a restructured text interpretation of PHP files.')
            ->addArgument(
                'config',
                InputArgument::REQUIRED,
                'Enter the path to the directory which contains the codesnippet.php file',
            );
    }

    /**
     * Executes the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int error code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getArgument('config') === null) {
            $io->error('Please enter the path to the codesnippets.php relative to
                the path calling this script');
            return Command::FAILURE;
        }

        $configPath = $input->getArgument('config');
        $config = include($configPath . '/codesnippets.php');
        if (!is_array($config)) {
            $io->error('File ' . $configPath . '/codesnippets.php contains no
                valid Configuration array.');
            return Command::FAILURE;
        }
        $codeSnippedCreator = new CodeSnippetCreator();
        $codeSnippedCreator->run($config, realpath($configPath));

        return Command::SUCCESS;
    }
}
