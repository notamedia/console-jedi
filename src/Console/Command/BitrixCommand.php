<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Console\Command;

/**
 * Base class for Bitrix console command.
 */
class BitrixCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        if ($this->getApplication()->getBitrixStatus())
        {
            return true;
        }
        
        return false;
    }
}