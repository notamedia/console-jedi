<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Console\Command;

use Notamedia\ConsoleJedi\Console\Application;

class Command extends \Symfony\Component\Console\Command\Command
{
    /**
     * @return \Notamedia\ConsoleJedi\Console\Application
     */
    public function getApplication()
    {
        return parent::getApplication();
    }
}