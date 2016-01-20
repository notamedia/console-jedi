<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
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