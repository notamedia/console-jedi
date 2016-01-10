<?php
/**
 * @link https://github.com/notamedia/console-jedi
 * @copyright Copyright © 2016 Notamedia Ltd.
 * @license MIT
 */

namespace Notamedia\ConsoleJedi\Command;

class BitrixCommand extends Command
{
    public function isEnabled()
    {
        if ($this->getApplication()->getBitrixStatus())
        {
            return true;
        }
        
        return false;
    }
}