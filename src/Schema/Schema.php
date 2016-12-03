<?php

namespace Notamedia\ConsoleJedi\Schema;

use Notamedia\ConsoleJedi\Schema\Command\ExportCommand;
use Notamedia\ConsoleJedi\Schema\Command\ImportCommand;

/**
 * Class Schema
 * @package Notamedia\ConsoleJedi\Schema
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