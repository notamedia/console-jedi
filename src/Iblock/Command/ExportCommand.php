<?php

namespace Notamedia\ConsoleJedi\Iblock\Command;

use Notamedia\ConsoleJedi\Application\Command\BitrixCommand;
use Notamedia\ConsoleJedi\Iblock\Exception\IblockException;
use Notamedia\ConsoleJedi\Iblock\Exporter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\FormatterHelper;
use Bitrix\Main\Loader;

/**
 * Command export information block(s) in xml file(s)
 */
class ExportCommand extends BitrixCommand
{
    use MigrationCommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('iblock:export')
            ->setDescription('Export information block(s) to xml')
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'Information block type'
            )
            ->addArgument(
                'code',
                InputArgument::REQUIRED,
                'Information block code'
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

        $exporter = new Exporter();
        $exporter
            ->setSections($input->getOption('sections'))
            ->setElements($input->getOption('elements'));

        foreach ($this->iblocks as $iblock) {

            try {
                $xml_id = \CIBlockCMLExport::GetIBlockXML_ID($iblock['ID']);
                $path = implode(DIRECTORY_SEPARATOR, [$this->dir, $xml_id]) . $this->extension;

                $exporter
                    ->setPath($path)
                    ->setId($iblock['ID'])
                    ->execute();

                $output->writeln(sprintf('<info>%s</info> iblock %s %s', 'success', $iblock['CODE'], $path));

            } catch (IblockException $e) {
                $output->writeln(sprintf('<error>%s</error> iblock %s', 'fail', $iblock['CODE']));
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln($e->getMessage());
                }
            }
        }

        return true;
    }
}