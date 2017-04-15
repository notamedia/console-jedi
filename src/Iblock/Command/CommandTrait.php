<?php

namespace Notamedia\ConsoleJedi\Iblock\Command;

use Symfony\Component\Console\Input\InputInterface;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\SiteTable;
use Bitrix\Iblock\TypeTable;
use Bitrix\Iblock\IblockTable;

/**
 * Class CommandTrait
 * @package Notamedia\ConsoleJedi\Iblock\Command
 */
trait CommandTrait
{
    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var array
     */
    protected $sites = [];

    /**
     * @var string
     */
    protected $type = '';

    /**
     * @var array
     */
    protected $iblocks = [];

    /**
     * @var string
     */
    protected $dir;

    /**
     * @var string
     */
    protected $extension = '.xml';

    /**
     * @param InputInterface $input
     */
    private function setDir(InputInterface $input)
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
     * @param InputInterface $input
     */
    private function setIblocks(InputInterface $input)
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
     * @param InputInterface $input
     */
    private function setType(InputInterface $input)
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
     * @param InputInterface $input
     */
    private function setSites(InputInterface $input)
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