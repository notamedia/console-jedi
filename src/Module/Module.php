<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Module;

use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;
use Notamedia\ConsoleJedi\Application\Exception\BitrixException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Module entity
 *
 * @author Marat Shamshutdinov <m.shamshutdinov@gmail.com>
 */
class Module
{
	/** @var string */
	private $name;

	/** @var \CModule */
	private $object;

	/** @var bool */
	private $beta = false;

	/**
	 * @param string $moduleName
	 */
	public function __construct($moduleName)
	{
		$this->name = $this->normalizeName($moduleName);
	}

	/**
	 * @param string $moduleName
	 * @return string
	 */
	protected function normalizeName($moduleName)
	{
		return preg_replace("/[^a-zA-Z0-9_.]+/i", "", trim($moduleName));
	}

	/**
	 * @return \CModule
	 */
	protected function &getObject()
	{
		if (!isset($this->object))
		{
			$this->object = \CModule::CreateModuleObject($this->name);
		}

		if (!is_object($this->object) || !($this->object instanceof \CModule))
		{
			unset($this->object);
			throw new Exception\ModuleNotFoundException('Module not found or incorrect', $this->name);
		}

		return $this->object;
	}

	/**
	 * Checks for module and module object existence
	 *
	 * @return bool
	 */
	public function exist()
	{
		try
		{
			$this->getObject();
		}
		catch (Exception\ModuleNotFoundException $e)
		{
			return false;
		}

		return true;
	}

	/**
	 * Check if module exists and installed
	 *
	 * @return bool
	 */
	public function isInstalled()
	{
		return ModuleManager::isModuleInstalled($this->name) && $this->exist();
	}

	/**
	 * @return bool true for marketplace modules, false for kernel modules
	 */
	public function isThirdParty()
	{
		return strpos($this->name, '.') !== false;
	}

	/**
	 * Install module
	 *
	 * @throws Exception\ModuleException
	 * @throws BitrixException
	 */
	public function install()
	{
		if (!$this->isInstalled())
		{
			$moduleObject =& $this->getObject();

			/**
			 * It's important to check if module class defines InstallDB method (it must register module)
			 * Thus absent InstallDB indicates that the module does not support automatic installation
			 */
			if ((new \ReflectionClass($moduleObject))->getMethod('InstallDB')->class !== get_class($moduleObject))
			{
				throw new Exception\ModuleException(
					'Missing InstallDB method. This module does not support automatic installation',
					$this->name
				);
			}

			if (!$moduleObject->InstallDB())
			{
				if (BitrixException::hasException())
				{
					BitrixException::generate();
				}
			}

			$moduleObject->InstallEvents();

			/** @noinspection PhpVoidFunctionResultUsedInspection */
			if (!$moduleObject->InstallFiles())
			{
				if (BitrixException::hasException())
				{
					BitrixException::generate();
				}
			}

			if (!$this->isInstalled())
			{
				throw new Exception\ModuleException(
					'Module was not registered. Probably it does not support automatic installation.',
					$this->name
				);
			}
		}

		return $this;
	}

	/**
	 * Download module from marketplace
	 *
	 * @return $this
	 */
	public function load()
	{
		if (!$this->isInstalled())
		{
			if (!$this->exist())
			{
				require_once($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/classes/general/update_client_partner.php');

				if (!\CUpdateClientPartner::LoadModuleNoDemand(
					$this->getName(),
					$strError,
					$this->isBeta() ? 'N' : 'Y',
					LANGUAGE_ID)
				)
				{
					throw new Exception\ModuleException(sprintf('Error occurred: %s', $strError), $this->getName());
				}
			}
		}

		return $this;
	}

	/**
	 * Uninstall module
	 *
	 * @throws Exception\ModuleException
	 * @throws BitrixException
	 */
	public function uninstall()
	{
		$moduleObject = $this->getObject();

		if ($this->isInstalled())
		{
			/**
			 * It's important to check if module class defines UnInstallDB method (it should unregister module)
			 * Thus absent UnInstallDB indicates that the module does not support automatic uninstallation
			 */
			if ((new \ReflectionClass($moduleObject))->getMethod('UnInstallDB')->class !== get_class($moduleObject))
			{
				throw new Exception\ModuleException(
					'Missing UnInstallDB method. This module does not support automatic uninstallation',
					$this->name
				);
			}

			/** @noinspection PhpVoidFunctionResultUsedInspection */
			if (!$moduleObject->UnInstallFiles())
			{
				if (BitrixException::hasException())
				{
					BitrixException::generate();
				}
			}

			$moduleObject->UnInstallEvents();

			/** @noinspection PhpVoidFunctionResultUsedInspection */
			if (!$moduleObject->UnInstallDB())
			{
				if (BitrixException::hasException())
				{
					BitrixException::generate();
				}
			}

			if ($this->isInstalled())
			{
				throw new Exception\ModuleException('Module was not unregistered', $this->name);
			}
		}

		return $this;
	}

	/**
	 * Uninstall and remove module directory
	 */
	public function remove()
	{
		if ($this->isInstalled())
		{
			$this->uninstall();
		}

		$path = getLocalPath('modules/' . $this->getName());

		if ($path)
		{
			(new Filesystem())->remove($_SERVER['DOCUMENT_ROOT'] . $path);
		}

		unset($this->object);

		return $this;
	}

	/**
	 * Update module
	 *
	 * It must be called repeatedly until the method returns false.
	 * After each call php must be restarted (new process created) to update module class and function definitions.
	 *
	 * @param array $modulesUpdated [optional]
	 * @return bool
	 */
	public function update(&$modulesUpdated = null)
	{
		require_once($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/classes/general/update_client_partner.php');

		if (!$this->isThirdParty())
		{
			throw new Exception\ModuleException('Kernel module updates are currently not supported.', $this->getName());
		}

		// ensures module existence
		$this->getObject();

		$errorMessage = $updateDescription = null;
		$loadResult = \CUpdateClientPartner::LoadModulesUpdates(
			$errorMessage,
			$updateDescription,
			LANGUAGE_ID,
			$this->isBeta() ? 'N' : 'Y',
			[$this->getName()],
			true
		);
		switch ($loadResult)
		{
			// archive loaded
			case "S":
				return $this->update($modulesUpdated);

			// error
			case "E":
				throw new Exception\ModuleException($errorMessage, $this->getName());

			// finished installing updates
			case "F":
				return false;

			// need to process loaded update
			case 'U':
				break;
		}

		/** @var string Temp directory with update files */
		$updateDir = null;

		if (!\CUpdateClientPartner::UnGzipArchive($updateDir, $errorMessage, true))
		{
			throw new Exception\ModuleException('[CL02] UnGzipArchive failed. ' . $errorMessage, $this->getName());
		}

		$this->validateUpdate($updateDir);

		if (isset($updateDescription["DATA"]["#"]["NOUPDATES"]))
		{
			\CUpdateClientPartner::ClearUpdateFolder($_SERVER["DOCUMENT_ROOT"] . "/bitrix/updates/" . $updateDir);
			return false;
		}

		$modulesUpdated = $updateDescr = [];
		if (isset($updateDescription["DATA"]["#"]["ITEM"]))
		{
			foreach ($updateDescription["DATA"]["#"]["ITEM"] as $moduleInfo)
			{
				$modulesUpdated[$moduleInfo["@"]["NAME"]] = $moduleInfo["@"]["VALUE"];
				$updateDescr[$moduleInfo["@"]["NAME"]] = $moduleInfo["@"]["DESCR"];
			}
		}

		if (\CUpdateClientPartner::UpdateStepModules($updateDir, $errorMessage))
		{
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
			throw new Exception\ModuleException('[CL04] UpdateStepModules failed. ' . $errorMessage, $this->getName());
		}

		return true;
	}

	/**
	 * Check update files
	 * 
	 * @param string $updateDir
	 */
	protected function validateUpdate($updateDir)
	{
		$errorMessage = null;
		if (!\CUpdateClientPartner::CheckUpdatability($updateDir, $errorMessage))
		{
			throw new Exception\ModuleException('[CL03] CheckUpdatability failed. ' . $errorMessage, $this->getName());
		}

		if (isset($updateDescription["DATA"]["#"]["ERROR"]))
		{
			$errorMessage = "";
			foreach ($updateDescription["DATA"]["#"]["ERROR"] as $errorDescription)
			{
				$errorMessage .= "[" . $errorDescription["@"]["TYPE"] . "] " . $errorDescription["#"];
			}
			throw new Exception\ModuleException($errorMessage, $this->getName());
		}
	}

	/**
	 * Returns module name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Beta releases allowed?
	 *
	 * @return boolean
	 */
	public function isBeta()
	{
		return $this->beta;
	}

	/**
	 * Set beta releases installation
	 *
	 * @param boolean $beta
	 */
	public function setBeta($beta = true)
	{
		$this->beta = $beta;
	}

	public function getVersion()
	{
		return $this->getObject()->MODULE_VERSION;
	}
}