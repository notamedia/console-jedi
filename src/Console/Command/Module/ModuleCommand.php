<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Console\Command\Module;

use Notamedia\ConsoleJedi\Console\Command\BitrixCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Module helper
 *
 * @author Marat Shamshutdinov <m.shamshutdinov@gmail.com>
 */
class ModuleCommand extends BitrixCommand
{
	/** @var string */
	protected $moduleName;

	/** @var \CModule */
	protected $moduleObject;

	/**
	 * @var array Methods to call
	 */
	protected $bootstrap = ['copyFiles', 'initializeBitrix'];

	/**
	 * {@inheritdoc}
	 */
	protected function configure()
	{
		$this->addArgument('module', InputArgument::REQUIRED, 'Module name (e.g. `vendor.module`)')
			->addOption('confirm-thirdparty', 'ct', InputOption::VALUE_NONE, 'Suppress third-party modules warning');
	}

	/**
	 * @param string $moduleName
	 * @return string
	 */
	protected function normalizeModuleName($moduleName)
	{
		return preg_replace("/[^a-zA-Z0-9_.]+/i", "", trim($moduleName));
	}

	/**
	 * @return \CModule
	 */
	protected function &getModuleObject()
	{
		if (!isset($this->moduleObject))
		{
			if (!isset($this->moduleName))
			{
				throw new \LogicException('moduleName is not set');
			}

			$this->moduleObject = \CModule::CreateModuleObject($this->moduleName);
		}

		if (!is_object($this->moduleObject) || !($this->moduleObject instanceof \CModule))
		{
			unset($this->moduleObject);
			throw new ModuleNotFoundException('Module not found or incorrect', $this->moduleName);
		}

		return $this->moduleObject;
	}

	/**
	 * @return bool
	 */
	protected function moduleExists()
	{
		try
		{
			$this->getModuleObject();
		}
		catch (ModuleNotFoundException $e)
		{
			return false;
		}

		return true;
	}

	/**
	 * @param string [optional] $moduleName
	 * @return bool true for marketplace modules, false for kernel modules
	 */
	protected function isThrirdParty($moduleName = null)
	{
		return strpos($moduleName ? $moduleName : $this->moduleName, '.') !== false;
	}

	/**
	 * @inheritdoc
	 */
	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		parent::initialize($input, $output);

		$this->moduleName = $this->normalizeModuleName($input->getArgument('module'));
	}

	/**
	 * @inheritdoc
	 */
	protected function interact(InputInterface $input, OutputInterface $output)
	{
		parent::interact($input, $output);

		// @todo decide on which commands to show this warning
		if (in_array($this->getName(),
				['module:register', 'module:unregister']) && $this->isThrirdParty($this->moduleName) && !$input->getOption('confirm-thirdparty')
		)
		{
			$output->writeln($this->moduleName . ' is not a kernel module. Correct operation is cannot be guaranteed for third-party modules!');
			return;
			$question = new ConfirmationQuestion(
				$this->moduleName . ' is not a kernel module. Correct operation is cannot be guaranteed for third-party modules!' . PHP_EOL
				. '<question>Procceed? [N/y]</question> ',
				false
			);

			/** @var QuestionHelper $questionHelper */
			$questionHelper = $this->getHelper('question');
			if (!$questionHelper->ask($input, $output, $question))
			{
				throw new \RuntimeException('User aborted');
			}
		}
	}
}