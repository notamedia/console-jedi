<?php
/**
 * @link https://github.com/notamedia/console-jedi
 * @copyright Copyright © 2016 Notamedia Ltd.
 * @license MIT
 */

namespace Notamedia\ConsoleJedi\Command;

use Notamedia\ConsoleJedi\Application;

class Command extends \Symfony\Component\Console\Command\Command
{
    /**
     * @return Application
     */
    public function getApplication()
    {
        return parent::getApplication();
    }
}