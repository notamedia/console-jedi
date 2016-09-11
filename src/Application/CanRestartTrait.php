<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Application;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait used for restarting process.
 *
 * @author Marat Shamshutdinov <m.shamshutdinov@gmail.com>
 */
trait CanRestartTrait
{
    /**
     * Executes another copy of console process to continue updates
     *
     * We may encounter problems when module update scripts (update.php or update_post.php) requires module files,
     * they are included only once and stay in most early version.
     * Bitrix update system always run update scripts in separate requests to web-server.
     * This ensures the same behavior as in original update system, updates always run on latest module version.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function restartScript(InputInterface $input, OutputInterface $output)
    {
        $proc = popen('php -f ' . join(' ', $GLOBALS['argv']) . ' 2>&1', 'r');
        while (!feof($proc)) {
            $output->write(fread($proc, 4096));
        }

        return pclose($proc);
    }
}