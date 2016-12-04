<?php

namespace Notamedia\ConsoleJedi\Schema\Command;

use Notamedia\ConsoleJedi\Application\Command\BitrixCommand;
use Notamedia\ConsoleJedi\Schema\Exception\SchemaException;
use Notamedia\ConsoleJedi\Schema\Schema;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\FormatterHelper;
use Bitrix\Main\Loader;

class ExportCommand extends BitrixCommand
{
    use CommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('schema:export')
            ->setDescription('Export information iblock(s) to xml')
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'Information iblock type'
            )
            ->addArgument(
                'code',
                InputArgument::REQUIRED,
                'Information iblock code'
            )
            ->addArgument(
                'dir',
                InputArgument::OPTIONAL,
                'Directory to export'
            )
            ->addOption(
                'sections',
                's',
                InputOption::VALUE_OPTIONAL,
                'Export sections [ "active", "all", "none" ]',
                'none'
            )
            ->addOption(
                'elements',
                'e',
                InputOption::VALUE_OPTIONAL,
                'Export elements [ "active", "all", "none" ]',
                'none'
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
        $this->setIblocks($input);
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

        $export = Schema::export()
            ->setSections($input->getOption('sections'))
            ->setElements($input->getOption('elements'));

        foreach ($this->iblocks as $iblock) {

            try {
                $xml_id = \CIBlockCMLExport::GetIBlockXML_ID($iblock['ID']);
                $path = implode(DIRECTORY_SEPARATOR, [$this->dir, $xml_id]) . $this->extension;

                $export
                    ->setPath($path)
                    ->setId($iblock['ID'])
                    ->execute();

                $output->writeln(sprintf('<info>%s</info> iblock %s %s', 'success', $iblock['CODE'], $path));

            } catch (SchemaException $e) {
                $output->writeln(sprintf('<error>%s</error> iblock %s', 'fail', $iblock['CODE']));
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln($e->getMessage());
                }
            }
        }

        return true;
    }
}