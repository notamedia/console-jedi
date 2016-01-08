<?php
/**
 * @link https://github.com/notamedia/console-jedi
 * @copyright Copyright © 2016 Notamedia Ltd.
 * @license MIT
 */

namespace Notamedia\ConsoleJedi;

use Notamedia\ConsoleJedi\Command\Agents\OnCronCommand;
use Notamedia\ConsoleJedi\Command\Cache\ClearCommand;

class Application extends \Symfony\Component\Console\Application
{
    const VERSION = '1.0.0';

    public function __construct()
    {
        parent::__construct('Console Jedi', static::VERSION);

        $this->autoload();

        $this->addCommands([
            new OnCronCommand(),
            new ClearCommand()
        ]);
    }

    public function autoload()
    {
        if (!isset($_SERVER['DOCUMENT_ROOT']) || strlen($_SERVER['DOCUMENT_ROOT']) <= 0)
        {
            return false;
        }

        define('NO_KEEP_STATISTIC', true);
        define('NOT_CHECK_PERMISSIONS', true);

        require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
    }

    /**
     * @return bool
     */
    public function isBitrixLoaded()
    {
        return defined('B_PROLOG_INCLUDED') && B_PROLOG_INCLUDED === true;
    }
}