<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Module\Command;

use Notamedia\ConsoleJedi\Module\Module;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for module installation/register
 *
 * @author Marat Shamshutdinov <m.shamshutdinov@gmail.com>
 */
class UpdateCommand extends ModuleCommand
{
	use CanRestart;

	/**
	 * {@inheritdoc}
	 */
	protected function configure()
	{
		parent::configure();

		$this->setName('module:update')
			->setDescription('Load module updates from Marketplace')
			->addOption('beta', 'b', InputOption::VALUE_NONE, 'Allow the installation of beta releases');
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$module = new Module($input->getArgument('module'));
		$modulesUpdated = null;
		while ($module->update($modulesUpdated))
		{
			if (is_array($modulesUpdated))
			{
				foreach ($modulesUpdated as $moduleName => $moduleVersion)
				{
					$output->writeln(sprintf('updated %s to <info>%s</info>', $moduleName, $moduleVersion));
				}
			}
			return $this->restartScript($input, $output);
		}
		return 0;
	}
}