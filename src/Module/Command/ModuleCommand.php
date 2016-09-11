<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Module\Command;

use Notamedia\ConsoleJedi\Application\Command\BitrixCommand;
use Notamedia\ConsoleJedi\Module\Module;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Module command base class.
 *
 * @author Marat Shamshutdinov <m.shamshutdinov@gmail.com>
 */
abstract class ModuleCommand extends BitrixCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->addArgument('module', InputArgument::REQUIRED, 'Module name (e.g. `vendor.module`)')
            ->addOption('confirm-thirdparty', 'ct', InputOption::VALUE_NONE, 'Suppress third-party modules warning');
    }

    /**
     * @inheritdoc
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

        $module = new Module($input->getArgument('module'));

        if (in_array($this->getName(), ['module:register', 'module:unregister'])
            && $module->isThirdParty() && !$input->getOption('confirm-thirdparty')
        ) {
            $output->writeln($module->isThirdParty() . ' is not a kernel module. Correct operation cannot be guaranteed for third-party modules!');
        }
    }

    /**
     * Gets console commands from this package.
     *
     * @return Command[]
     */
    public static function getCommands()
    {
        return [
            new LoadCommand(),
            new RegisterCommand(),
            new RemoveCommand(),
            new UnregisterCommand(),
            new UpdateCommand(),
        ];
    }
}