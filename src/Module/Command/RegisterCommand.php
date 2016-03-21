<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Module\Command;

use Bitrix\Main\ModuleManager;
use Notamedia\ConsoleJedi\Application\Exception\BitrixException;
use Notamedia\ConsoleJedi\Module\Exception\ModuleException;
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

			if (!$module->InstallDB())
			{
				$output->writeln(sprintf('<info>%s::InstallDB() returned false;</info>'));
				if (BitrixException::hasException())
				{
					BitrixException::generate();
				}
			}

			$module->InstallEvents();

			/** @noinspection PhpVoidFunctionResultUsedInspection */
			if (!$module->InstallFiles())
			{
				$output->writeln(sprintf('<info>%s::InstallFiles() returned false;</info>'));
				if (BitrixException::hasException())
				{
					BitrixException::generate();
				}
			}

			if (!ModuleManager::isModuleInstalled($this->moduleName))
			{
				throw new ModuleException('Module was not registred. Probably it does not support automatic installtion.',
					$this->moduleName);
			}

			$output->writeln(sprintf('Module %s successfully installed', $this->moduleName));
		}
	}
}