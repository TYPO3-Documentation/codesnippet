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
use T3docs\Codesnippet\Exceptions\InvalidConfigurationException;
use T3docs\Codesnippet\Util\CodeSnippetCreator;

/**
 * This command will parse the given codesnippets.php file with all its sub-configurations
 * to build restructured text interpretations of the configured files.
 * The command is called PhpDomain, because it will convert short classnames
 * to their FQCN.
 */
class PhpDomainCommand extends Command
{
    public function __construct(
        readonly protected CodeSnippetCreator $codeSnippetCreator,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure(): void
    {
        $this->setHelp('This command will loop through all configured files in codesnippets.php '
            . LF . 'and creates a restructured interpretation of the configured file.')
            ->setDescription('Command to create a restructured text interpretation of PHP files.')
            ->addArgument(
                'config',
                InputArgument::REQUIRED,
                'Enter the path to the directory which contains the codesnippets.php file',
            );
    }

    /**
     * Executes the command
     *
     * @return int error code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $pathToConfigFile = $this->getPathToConfigFile((string)$input->getArgument('config'));
        $creatorConfiguration = $this->getCreatorConfiguration(
            $pathToConfigFile,
            $io
        );

        if ($creatorConfiguration === null) {
            return Command::FAILURE;
        }

        try {
            $this->codeSnippetCreator->run($creatorConfiguration, dirname(realpath($pathToConfigFile)));
        } catch (InvalidConfigurationException $e) {
            $io->error($e->getMessage());
        }

        return Command::SUCCESS;
    }

    protected function getCreatorConfiguration(string $pathToConfigFile, SymfonyStyle $io): ?array
    {
        if (!is_file($pathToConfigFile) || !is_readable($pathToConfigFile)) {
            $io->error('File ' . $pathToConfigFile . ' does not exists or is not readable.');
            return null;
        }

        $creatorConfiguration = include($pathToConfigFile);

        if (!is_array($creatorConfiguration)) {
            $io->error('File ' . $pathToConfigFile . ' contains no valid Configuration array.');
            return null;
        }

        return $creatorConfiguration;
    }

    protected function getPathToConfigFile(string $configArgument): string
    {
        if (str_ends_with($configArgument, 'codesnippets.php')) {
            $pathToConfigFile = $configArgument;
        } elseif (str_ends_with($configArgument, 'codesnippet.php')) {
            $pathToConfigFile = dirname($configArgument) . '/codesnippets.php';
        } else {
            $pathToConfigFile = rtrim($configArgument, '/') . '/codesnippets.php';
        }

        return $pathToConfigFile;
    }
}
