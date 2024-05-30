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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use T3docs\Codesnippet\Renderer\PhpDomainRenderer;

/**
 * This command regenerates the baseline of the functional tests
 */
class BaselineCommand extends Command
{
    public function __construct(
        readonly protected PhpDomainRenderer $phpDomainRenderer,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure(): void {}

    /**
     * Executes the command
     *
     * @return int error code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->generateBaseline();
        return Command::SUCCESS;
    }

    public function generateBaseline(): void
    {
        $configDir = __DIR__ . '/../../Tests/Functional/Fixtures/config/';
        $resultDir = __DIR__ . '/../../Tests/Functional/Fixtures/results/';
        $configFiles = glob($configDir . '*.php');
        if ($configFiles === false || $configFiles === []) {
            throw new \Exception('No files found in ' . $configDir);
        }

        foreach ($configFiles as $configFilePath) {
            $configFileName = basename($configFilePath);
            $expectedFileName = str_replace('.php', '.rst', $configFileName);
            $config = include $configFilePath;

            $result = $this->phpDomainRenderer->extractPhpDomain($config);

            file_put_contents($resultDir . $expectedFileName, $result);
        }
        echo sprintf("Baseline generation completed. Regenerated %s files.\n", count($configFiles));
    }
}
