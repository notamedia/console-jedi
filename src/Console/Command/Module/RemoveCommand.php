<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Console\Command\Module;

use Bitrix\Main\ModuleManager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Command for module installation/register
 *
 * @author Marat Shamshutdinov <m.shamshutdinov@gmail.com>
 */
class RemoveCommand extends ModuleCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected function configure()
	{
		$this->setName('module:remove')
			->setDescription('Uninstall and remove module folder from system');

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
				$arguments = array(
					'command' => 'module:unregister',
					'module' => $this->moduleName,
					'',
				);
				$unregisterInput = new ArrayInput($arguments);
				$returnCode = $this->getApplication()->find('module:unregister')->run($unregisterInput, $output);
				if ($returnCode > 0)
				{
					return $returnCode;
				}
			}

			$path = getLocalPath('modules/' . $this->moduleName);

			if ($path)
			{
				(new Filesystem())->remove($_SERVER['DOCUMENT_ROOT'] . $path);

				$output->writeln(sprintf('Module %s removed', $this->moduleName));
			}
			else
			{
				$output->writeln(sprintf('<error>Module %s is not found</error>', $this->moduleName));
			}
		}
		catch (ModuleException $e)
		{
			$output->writeln('<error>' . $e->getMessage() . '</error>');

			return 1;
		}
	}
}