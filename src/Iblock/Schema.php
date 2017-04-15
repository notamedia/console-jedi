<?php

namespace Notamedia\ConsoleJedi\Iblock;

use Notamedia\ConsoleJedi\Iblock\Command\ExportCommand;
use Notamedia\ConsoleJedi\Iblock\Command\ImportCommand;

/**
 * Class Iblock
 * @package Notamedia\ConsoleJedi\Iblock
 */
class Schema
{
    /**
     * @return array
     */
    public static function getCommands()
    {
        return [
            new ExportCommand(),
            new ImportCommand()
        ];
    }

    /**
     * @return Export
     */
    static public function export()
    {
        return new Export();
    }

    /**
     * @return Import
     */
    static public function import()
    {
        return new Import();
    }
}