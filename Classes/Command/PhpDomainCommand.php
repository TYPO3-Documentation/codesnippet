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

class PhpDomainCommand extends Command
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
    {
        $this->setHelp('Prints a list of recent sys_log entries.'
            . LF . 'If you want to get more detailed information, use the --verbose option.')
            ->setDescription('Run content importer. Without '
                . ' arguments all available wizards will be run.')
            ->addArgument(
                'config',
                InputArgument::OPTIONAL,
                'Enter the fully qualified name of the structure you want to export'
            );
    }

    /**
     * Executes the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
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
