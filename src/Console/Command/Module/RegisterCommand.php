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
class RegisterCommand extends ModuleCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected function configure()
	{
		$this->setName('module:register')
			->setDescription('Install module');

		parent::configure();
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		try
		{
			if (ModuleManager::isModuleInstalled($this->moduleName))
			{
				$output->writeln(sprintf('<comment>Module %s is already installed</comment>', $this->moduleName));
			}
			else
			{
				// first check if already exists
				$module =& $this->getModuleObject();

				/**
				 * It's important to check if module class defines InstallDB method (it must register module)
				 * Thus absent InstallDB indicates that the module does not support automatic installation
				 */
				if ((new \ReflectionClass($module))->getMethod('InstallDB')->class !== get_class($module))
				{
					throw new ModuleException('Missing InstallDB method. This module does not support automatic installation',
						$this->moduleName);
				}

				/**
				 * @todo Return value is not documented, do we need to check it?
				 * — Where can be „false-positives“ then module developer forgot to return anything
				 */
				if (!$module->InstallDB())
				{
					$output->writeln(sprintf('<info>%s::InstallDB() returned false;</info>'));
					if (BitrixException::hasException())
					{
						BitrixException::generate();
					}
				}

				$module->InstallEvents();

				// @todo Return value is not documented, no need to check it?
				if (!$module->InstallFiles())
				{
					$output->writeln(sprintf('<info>%s::InstallFiles() returned false;</info>'));
					if (BitrixException::hasException())
					{
						BitrixException::generate();
					}
				}

				/**
				 * @todo Try to guess correct installation
				 * — check if files are copied from module/install/component to /bitrix/components/
				 * — other ways?
				 */

				if (!ModuleManager::isModuleInstalled($this->moduleName))
				{
					throw new ModuleException('Module was not registred. Probably it does not support automatic installtion.', $this->moduleName);
				}

				$output->writeln(sprintf('Module %s successfully installed', $this->moduleName));
			}
		}
		catch (ModuleException $e)
		{
			$output->writeln('<error>' . $e->getMessage() . '</error>');

			return 1;
		}
	}
}