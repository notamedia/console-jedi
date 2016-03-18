<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Module\Command;

use Bitrix\Main\ModuleManager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Notamedia\ConsoleJedi\Module\Exception\ModuleException;

/**
 * Command for module installation/register
 *
 * @author Marat Shamshutdinov <m.shamshutdinov@gmail.com>
 */
class LoadCommand extends ModuleCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected function configure()
	{
		$this->setName('module:load')
			->setDescription('Load and install module from Marketplace');

		parent::configure();
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		try
		{
			if (!$this->isThrirdParty())
			{
				$output->writeln('<info>Module name seems incorrect: ' . $this->moduleName . '</info>');
			}

			if (ModuleManager::isModuleInstalled($this->moduleName) && $this->moduleExists())
			{
				$output->writeln(sprintf('<comment>Module %s is already registered</comment>', $this->moduleName));
			}
			else
			{
				if ($this->moduleExists())
				{
					$output->writeln('Module is already loaded');
				}
				else
				{
					require_once($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/classes/general/update_client_partner.php');

					$output->write('Downloading module... ');

					if (!\CUpdateClientPartner::LoadModuleNoDemand($this->moduleName, $strError, $bStable = "Y", LANGUAGE_ID))
					{
						throw new ModuleException(sprintf('Error occured: %s', $strError), $this->moduleName);
					}

					$output->writeln('<info>done</info>');
				}

				$registerCommand = $this->getApplication()->find('module:register');
				$arguments = array(
					'command' => 'module:register',
					'module' => $this->moduleName,
					'',
				);
				$registerInput = new ArrayInput($arguments);

				return $registerCommand->run($registerInput, $output);
			}

			return 0;
		}
		catch (ModuleException $e)
		{
			$output->writeln('<error>' . $e->getMessage() . '</error>');

			return 1;
		}
	}
}