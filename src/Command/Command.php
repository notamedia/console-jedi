<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
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