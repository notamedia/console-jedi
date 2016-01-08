<?php
/**
 * @link https://github.com/notamedia/console-jedi
 * @copyright Copyright © 2016 Notamedia Ltd.
 * @license MIT
 */

namespace Notamedia\ConsoleJedi\Command;

use Symfony\Component\Console\Command\Command;

class BitrixCommand extends Command
{
    public function isEnabled()
    {
        if ($this->getApplication()->isBitrixLoaded())
        {
            return true;
        }
        
        return false;
    }
}