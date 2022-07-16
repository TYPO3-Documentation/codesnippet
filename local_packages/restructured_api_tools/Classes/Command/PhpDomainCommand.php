<?php
namespace T3docs\RestructuredApiTools\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use T3docs\RestructuredApiTools\Util\CodeSnippetCreator;

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
            $io->error('Please enter the fully qualified name of the class or interface you want to document.');
            return Command::FAILURE;
        }

        $configPath = $input->getArgument('config');
        $config = include($configPath . '/codesnippets.php');
        if (!is_array($config)) {
            $io->error('File ' . $configPath . '/codesnippets.php contains no valid Configuration array.');
            return Command::FAILURE;
        }
        $codeSnippedCreator = new CodeSnippetCreator();
        $codeSnippedCreator->run($config, realpath($configPath));

        return Command::SUCCESS;
    }
}
