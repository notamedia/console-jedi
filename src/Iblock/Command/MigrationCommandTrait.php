<?php

namespace Notamedia\ConsoleJedi\Iblock\Command;

use Symfony\Component\Console\Input\InputInterface;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\SiteTable;
use Bitrix\Iblock\TypeTable;
use Bitrix\Iblock\IblockTable;

/**
 * Trait checks and sets import/export parameters information block
 */
trait MigrationCommandTrait
{
    /**
     * Errors check parameters
     * @var array
     */
    protected $errors = [];

    /**
     * Information block sites
     * @var array
     */
    protected $sites = [];

    /**
     * Information block type
     * @var string
     */
    protected $type = '';

    /**
     * Information blocks
     * @var array
     */
    protected $iblocks = [];

    /**
     * Directory with file(s)
     * @var string
     */
    protected $dir;

    /**
     * Extension file(s)
     * @var string
     */
    protected $extension = '.xml';

    /**
     * Check argument directory and set $this->dir
     * @param InputInterface $input
     */
    protected function setDir(InputInterface $input)
    {
        $dir = $input->getArgument('dir');

        if (!$dir) {
            $dir = getcwd();
        }

        $dir = rtrim($dir, DIRECTORY_SEPARATOR);

        if (!Directory::isDirectoryExists($dir) || !Directory::isDirectory($dir)) {
            $this->errors[] = 'Directory not found';
        }

        $this->dir = $dir;
    }

    /**
     * Check arguments type and code, set $this->iblocks
     * @param InputInterface $input
     */
    protected function setIblocks(InputInterface $input)
    {
        $iblocks = IblockTable::query()
            ->setFilter([
                'IBLOCK_TYPE_ID' => $input->getArgument('type'),
                'CODE' => $input->getArgument('code')
            ])
            ->setSelect(['ID', 'CODE'])
            ->exec();

        if ($iblocks->getSelectedRowsCount() <= 0) {
            $this->errors[] = 'Iblock(s) not found';
        }

        $this->iblocks = $iblocks->fetchAll();
    }

    /**
     * Check argument type and set $this->type
     * @param InputInterface $input
     */
    protected function setType(InputInterface $input)
    {
        $type = TypeTable::query()
            ->setFilter([
                'ID' => $input->getArgument('type'),
            ])
            ->setSelect(['ID'])
            ->exec();

        if ($type->getSelectedRowsCount() <= 0) {
            $this->errors[] = 'Type not found';
        }

        $this->type = $type->fetch()['ID'];
    }

    /**
     * Check argument sites and set $this->sites
     * @param InputInterface $input
     */
    protected function setSites(InputInterface $input)
    {
        $sites = SiteTable::query()
            ->setFilter([
                'LID' => $input->getArgument('sites'),
            ])
            ->setSelect(['LID'])
            ->exec();

        if ($sites->getSelectedRowsCount() <= 0) {
            $this->errors[] = 'Sites not found';
        }

        $this->sites = $sites->fetchAll();
    }
}