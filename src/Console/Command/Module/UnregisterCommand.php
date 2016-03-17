<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Console\Command\Module;

use Bitrix\Main\ModuleManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for module installation/register
 *
 * @author Marat Shamshutdinov <m.shamshutdinov@gmail.com>
 */
class UnregisterCommand extends ModuleCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected function configure()
	{
		$this->setName('module:unregister')
			->setDescription('Uninstall module');
		
		parent::configure();
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		try
		{
			$module =& $this->getModuleObject();

			if (ModuleManager::isModuleInstalled($this->moduleName))
			{
				/**
				 * It's important to check if module class defines UnInstallDB method (it must unregister module)
				 * Thus absent UnInstallDB indicates that the module does not support automatic uninstallation
				 */
				if ((new \ReflectionClass($module))->getMethod('UnInstallDB')->class !== get_class($module))
				{
					throw new ModuleException('Missing UnInstallDB method. This module does not support automatic uninstallation',
						$this->moduleName);
				}

				// @todo Return value is not documented, no need to check it?
				if (!$module->UnInstallFiles())
				{
					$output->writeln(sprintf('<info>%s::UnInstallFiles() returned false;</info>'));
					if (BitrixException::hasException())
						BitrixException::generate();
				}

				$module->UnInstallEvents();

				// @todo Return value is not documented, no need to check it?
				// @todo iblock module problem
				if (!$module->UnInstallDB())
				{
					$output->writeln(sprintf('<info>%s::UnInstallDB() returned false;</info>'));
					if (BitrixException::hasException())
						BitrixException::generate();
				}

				if (ModuleManager::isModuleInstalled($this->moduleName))
				{
					throw new ModuleException('Module was not unregistred', $this->moduleName);
				}

				/**
				 * @todo Try to guess correct uninstallation
				 * — check if module files are delete from /bitrix/components/ 
				 * — other ways?
				 */
				$output->writeln(sprintf('Module %s uninstalled', $this->moduleName));			}
			else
			{
				$output->writeln(sprintf('<comment>Module %s wasn\'t installed</comment>', $this->moduleName));
			}

		}
		catch (ModuleException $e)
		{
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return 1;
		}
	}
}