<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Module\Command;

use Notamedia\ConsoleJedi\Module\Module;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for module installation / register.
 *
 * @author Marat Shamshutdinov <m.shamshutdinov@gmail.com>
 */
class RegisterCommand extends ModuleCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('module:register')
            ->setDescription('Install module');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = new Module($input->getArgument('module'));
        $module->register();
        $output->writeln(sprintf('registered <info>%s</info>', $module->getName()));
    }
}