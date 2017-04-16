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
     * @return Exporter
     */
    static public function export()
    {
        return new Exporter();
    }

    /**
     * @return Importer
     */
    static public function import()
    {
        return new Importer();
    }
}