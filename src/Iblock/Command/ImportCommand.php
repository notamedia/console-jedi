<?php

namespace Notamedia\ConsoleJedi\Iblock\Command;

use Notamedia\ConsoleJedi\Application\Command\BitrixCommand;
use Notamedia\ConsoleJedi\Iblock\Exception\SchemaException;
use Notamedia\ConsoleJedi\Iblock\Importer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\FormatterHelper;
use Bitrix\Main\Loader;

/**
 * Command import information block(s) from xml file(s)
 */
class ImportCommand extends BitrixCommand
{
    use MigrationCommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('iblock:import')
            ->setDescription('Import information block(s) from xml')
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'Information block type'
            )
            ->addArgument(
                'sites',
                InputArgument::REQUIRED,
                'Sites to which the information block will be bound (if it is to be created)'
            )
            ->addArgument(
                'dir',
                InputArgument::OPTIONAL,
                'Directory to import'
            )
            ->addOption(
                'sections',
                's',
                InputOption::VALUE_OPTIONAL,
                'If an existing section is no longer in the source file [ leave: "N", deactivate: "A", delete: "D" ]',
                'A'
            )
            ->addOption(
                'elements',
                'e',
                InputOption::VALUE_OPTIONAL,
                'If an existing element is no longer in the source file [ leave: "N", deactivate: "A", delete: "D" ]',
                'A'
            );

    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        Loader::includeModule('iblock');
    }


    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->setDir($input);
        $this->setType($input);
        $this->setSites($input);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $formatter = new FormatterHelper();

        if (count($this->errors) > 0) {
            $output->writeln($formatter->formatBlock($this->errors, 'error'));
            return false;
        }

        $importer = new Importer();
        $importer
            ->setType($this->type)
            ->setSites($this->sites)
            ->setActionSection($input->getOption('sections'))
            ->setActionElement($input->getOption('elements'));;

        foreach (glob(implode(DIRECTORY_SEPARATOR . '*', [$this->dir, $this->extension])) as $file) {

            try {
                $importer
                    ->setPath($file)
                    ->execute();

                $output->writeln(sprintf('<info>%s</info> file %s', 'success', $file));

            } catch (SchemaException $e) {
                $output->writeln(sprintf('<error>%s</error> file %s', 'fail', $file));
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln($e->getMessage());
                }
            }
        }
    }
}