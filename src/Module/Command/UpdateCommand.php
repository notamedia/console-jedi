<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Module\Command;

use Bitrix\Main\Config\Option;
use Notamedia\ConsoleJedi\Module\Exception\ModuleException;
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
	/**
	 * {@inheritdoc}
	 */
	protected function configure()
	{
		$this->setName('module:update')
			->setDescription('Load module updates from Marketplace')
			->addOption('beta', 'b', InputOption::VALUE_NONE, 'Allow the installation of beta releases');

		parent::configure();
	}

	/**
	 * Executes another copy of console script to continue updates
	 *
	 * We may encounter problems when module update scripts (update.php or update_post.php) requires module files,
	 * they are included only once and stay in most early version.
	 * Bitrix update system always run update scripts in separate requests to web-server.
	 * This ensures the same behavior as in original update system, updates always run on latest module version.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return mixed
	 */
	protected function restartScript(InputInterface $input, OutputInterface $output)
	{
		// --no-install argument for calls from module:load command, module will be installed in most parent copy
		$proc = popen('php -f ' . join(' ', $GLOBALS['argv']) . ' --no-install 2>&1', 'r');
		while (!feof($proc))
		{
			$output->write(fread($proc, 4096));
		}

		return pclose($proc);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		require_once($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/classes/general/update_client_partner.php');

		if (!$this->isThrirdParty())
		{
			throw new ModuleException('Kernel modules updates is currently not supported.', $this->moduleName);
		}

		// check module existance
		$this->getModuleObject();

		$errorMessage = $updateDescription = null;
		$loadResult = \CUpdateClientPartner::LoadModulesUpdates(
			$errorMessage,
			$updateDescription,
			LANGUAGE_ID,
			$input->getOption('beta') ? 'N' : 'Y',
			[$this->moduleName],
			true
		);
		switch ($loadResult)
		{
			case "S":
				return $this->restartScript($input, $output);
				break;

			case "E":
				throw new ModuleException($errorMessage, $this->moduleName);
				break;

			case "F":
				$output->writeln('<comment>No more updates available</comment>');

				return 0;

				break;
		}

		/** @var string Temp directory with update files */
		$updateDir = null;

		if (!\CUpdateClientPartner::UnGzipArchive($updateDir, $errorMessage, true))
		{
			throw new ModuleException('[CL02] UnGzipArchive failed. ' . $errorMessage, $this->moduleName);
		}

		$this->validate($updateDir);

		if (isset($updateDescription["DATA"]["#"]["NOUPDATES"]))
		{
			\CUpdateClientPartner::ClearUpdateFolder($_SERVER["DOCUMENT_ROOT"] . "/bitrix/updates/" . $updateDir);
			$output->writeln('No more updates available');

			return 0;
		}

		$modulesUpdated = $updateDescr = [];
		if (isset($updateDescription["DATA"]["#"]["ITEM"]))
		{
			/** @var array $moduleInfo ['NAME' => 'module name', 'VALUE' => 'version', 'DESC' => 'update description'] */
			foreach ($updateDescription["DATA"]["#"]["ITEM"] as $moduleInfo)
			{
				$modulesUpdated[$moduleInfo["@"]["NAME"]] = $moduleInfo["@"]["VALUE"];
				$updateDescr[$moduleInfo["@"]["NAME"]] = $moduleInfo["@"]["DESCR"];

				$output->write(sprintf('Installing %s %s', $moduleInfo["@"]["NAME"], $moduleInfo["@"]["VALUE"]));
			}
		}

		if (\CUpdateClientPartner::UpdateStepModules($updateDir, $errorMessage))
		{
			$output->writeln(' <info>done</info>');
			foreach ($modulesUpdated as $key => $value)
			{
				if (Option::set('main', 'event_log_marketplace', "Y") === "Y")
				{
					\CEventLog::Log("INFO", "MP_MODULE_DOWNLOADED", "main", $key, $value);
				}
			}
		}
		else
		{
			throw new ModuleException('[CL04] UpdateStepModules failed. ' . $errorMessage, $this->moduleName);
		}

		return $this->restartScript($input, $output);
	}

	/**
	 * Checks update files
	 * @param string $updateDir
	 */
	protected function validate($updateDir)
	{
		$errorMessage = null;
		if (!\CUpdateClientPartner::CheckUpdatability($updateDir, $errorMessage))
		{
			throw new ModuleException('[CL03] CheckUpdatability failed. ' . $errorMessage, $this->moduleName);
		}

		if (isset($updateDescription["DATA"]["#"]["ERROR"]))
		{
			$errorMessage = "";
			foreach ($updateDescription["DATA"]["#"]["ERROR"] as $errorDescription)
			{
				$errorMessage .= "[" . $errorDescription["@"]["TYPE"] . "] " . $errorDescription["#"];
			}
			throw new ModuleException($errorMessage, $this->moduleName);;
		}
	}
}