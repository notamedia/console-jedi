<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Application;

use Bitrix\Main\DB\ConnectionException;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Notamedia\ConsoleJedi\Agent\Command\OnCronCommand;
use Notamedia\ConsoleJedi\Agent\Command\ExecuteCommand;
use Notamedia\ConsoleJedi\Application\Exception\ConfigurationException;
use Notamedia\ConsoleJedi\Cache\Command\ClearCommand;
use Notamedia\ConsoleJedi\Environment\Command\InitCommand;
use Notamedia\ConsoleJedi\Module\Command as Module;
use Notamedia\ConsoleJedi\Search\Command\ReIndexCommand;
use Notamedia\ConsoleJedi\Iblock\Command\ExportCommand;
use Notamedia\ConsoleJedi\Iblock\Command\ImportCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Console Jedi application.
 *
 * @author Nik Samokhvalov <nik@samokhvalov.info>
 */
class Application extends \Symfony\Component\Console\Application
{
    /**
     * Version of the Console Jedi application.
     */
    const VERSION = '1.0.0';
    /**
     * Default name of configuration file.
     */
    const CONFIG_DEFAULT_FILE = './.jedi.php';
    /**
     * Bitrix is unavailable.
     */
    const BITRIX_STATUS_UNAVAILABLE = 500;
    /**
     * Bitrix is available, but not have connection to DB.
     */
    const BITRIX_STATUS_NO_DB_CONNECTION = 100;
    /**
     * Bitrix is available.
     */
    const BITRIX_STATUS_COMPLETE = 0;
    /**
     * @var int Status of Bitrix kernel. Value of constant `Application::BITRIX_STATUS_*`.
     */
    protected $bitrixStatus = Application::BITRIX_STATUS_UNAVAILABLE;
    /**
     * @var null|string
     */
    private $documentRoot = null;
    /**
     * @var null|array
     */
    private $configuration = null;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = 'Console Jedi', $version = self::VERSION)
    {
        parent::__construct($name, static::VERSION);
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if ($this->getConfiguration() === null) {
            $this->loadConfiguration();
        }

        if (!in_array($this->getCommandName($input), ['environment:init', 'env:init'])) {
            $this->initializeBitrix();
        }

        if ($this->getConfiguration()) {
            foreach ($this->getBitrixCommands() as $bitrixCommand) {
                $this->add($bitrixCommand);
            }

            foreach ($this->getConfiguration()['commands'] as $command) {
                $this->add($command);
            }
        }

        if ($this->isBitrixLoaded() && $this->getConfiguration()['useModules'] === true) {
            foreach ($this->getModulesCommands() as $moduleCommand) {
                $this->add($moduleCommand);
            }
        }

        $exitCode = parent::doRun($input, $output);

        if ($this->getConfiguration() === null) {
            $output->writeln(PHP_EOL . '<error>No configuration loaded.</error> '
                . 'Please run <info>init</info> command first');
        } else {
            switch ($this->getBitrixStatus()) {
                case static::BITRIX_STATUS_UNAVAILABLE:
                    $output->writeln(PHP_EOL . sprintf('<error>No Bitrix kernel found in %s.</error> '
                            . 'Please run <info>env:init</info> command to configure', $this->getDocumentRoot()));
                    break;

                case static::BITRIX_STATUS_NO_DB_CONNECTION:
                    $output->writeln(PHP_EOL . '<error>Bitrix database connection is unavailable.</error>');
                    break;

                case static::BITRIX_STATUS_COMPLETE:
                    if ($this->getCommandName($input) === null) {
                        $output->writeln(PHP_EOL . sprintf('Using Bitrix <info>kernel v%s</info>.</info>', SM_VERSION),
                            OutputInterface::VERBOSITY_VERY_VERBOSE);
                    }
                    break;
            }
        }

        return $exitCode;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new \Notamedia\ConsoleJedi\Application\Command\InitCommand();

        return $commands;
    }

    /**
     * Gets Bitrix console commands from this package.
     *
     * @return Command[]
     */
    protected function getBitrixCommands()
    {
        return array_merge(
            [
                new OnCronCommand(),
                new ExecuteCommand(),
                new ClearCommand(),
                new InitCommand(),
                new ReIndexCommand(),
                new ExportCommand(),
                new ImportCommand(),
                new ReIndexCommand(),
            ],
            Module\ModuleCommand::getCommands()
        );
    }

    /**
     * Gets console commands from modules.
     *
     * @return Command[]
     *
     * @throws \Bitrix\Main\LoaderException
     */
    protected function getModulesCommands()
    {
        $commands = [];

        foreach (ModuleManager::getInstalledModules() as $module) {
            $cliFile = getLocalPath('modules/' . $module['ID'] . '/cli.php');

            if ($cliFile === false) {
                continue;
            } elseif (!Loader::includeModule($module['ID'])) {
                continue;
            }

            $config = include_once $this->getDocumentRoot() . $cliFile;

            if (isset($config['commands']) && is_array($config['commands'])) {
                $commands = array_merge($commands, $config['commands']);
            }
        }

        return $commands;
    }

    /**
     * Loading application configuration.
     *
     * @param string $path Path to configuration file.
     *
     * @return bool
     *
     * @throws ConfigurationException
     */
    public function loadConfiguration($path = self::CONFIG_DEFAULT_FILE)
    {
        if (!is_file($path)) {
            return false;
        }

        $this->configuration = include $path;

        if (!is_array($this->configuration)) {
            throw new ConfigurationException('Configuration file ' . $path . ' must return an array');
        }

        $filesystem = new Filesystem();

        if ($filesystem->isAbsolutePath($this->configuration['web-dir'])) {
            $this->setDocumentRoot($this->configuration['web-dir']);
        } else {
            $this->setDocumentRoot($this->getRoot() . '/' . $this->configuration['web-dir']);
        }

        if (!is_dir($_SERVER['DOCUMENT_ROOT'])) {
            return false;
        }

        return true;
    }

    /**
     * Gets application configuration.
     *
     * @return null|array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Initialize kernel of Bitrix.
     *
     * @return int The status of readiness kernel.
     */
    public function initializeBitrix()
    {
        if ($this->bitrixStatus === static::BITRIX_STATUS_COMPLETE) {
            return static::BITRIX_STATUS_COMPLETE;
        } elseif (!$this->checkBitrix()) {
            return static::BITRIX_STATUS_UNAVAILABLE;
        }

        define('NO_KEEP_STATISTIC', true);
        define('NOT_CHECK_PERMISSIONS', true);

        try {
            /**
             * Declare global legacy variables
             *
             * Including kernel here makes them local by default but some modules depend on them in installation class
             */
            global
            /** @noinspection PhpUnusedLocalVariableInspection */
            $DB, $DBType, $DBHost, $DBLogin, $DBPassword, $DBName, $DBDebug, $DBDebugToFile, $APPLICATION, $USER, $DBSQLServerType;

            require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

            if (defined('B_PROLOG_INCLUDED') && B_PROLOG_INCLUDED === true) {
                $this->bitrixStatus = static::BITRIX_STATUS_COMPLETE;
            }
        } catch (ConnectionException $e) {
            $this->bitrixStatus = static::BITRIX_STATUS_NO_DB_CONNECTION;
        }

        return $this->bitrixStatus;
    }

    /**
     * Checks readiness of Bitrix for kernel initialize.
     *
     * @return bool
     */
    public function checkBitrix()
    {
        if (
            !is_file($_SERVER['DOCUMENT_ROOT'] . '/bitrix/.settings.php')
            && !is_file($_SERVER['DOCUMENT_ROOT'] . '/bitrix/.settings_extra.php')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Gets Bitrix status.
     *
     * @return int Value of constant `Application::BITRIX_STATUS_*`.
     */
    public function getBitrixStatus()
    {
        return $this->bitrixStatus;
    }

    /**
     * Checks that the Bitrix kernel is loaded.
     *
     * @return bool
     */
    public function isBitrixLoaded()
    {
        return $this->bitrixStatus === static::BITRIX_STATUS_COMPLETE;
    }

    /**
     * Autoloader classes of the tests.
     *
     * Initializes Bitrix kernel, finds and connects files in directory `vendor.module/tests/`
     * by pattern `<class>test.php` and loading modules of tests.
     *
     * @throws ConfigurationException
     */
    public function autoloadTests()
    {
        if ($this->getConfiguration() === null) {
            $this->loadConfiguration();
        }

        $this->initializeBitrix();

        spl_autoload_register(function ($className) {
            $file = ltrim($className, "\\");
            $file = strtr($file, Loader::ALPHA_UPPER, Loader::ALPHA_LOWER);
            $file = str_replace('\\', '/', $file);

            if (substr($file, -5) === 'table') {
                $file = substr($file, 0, -5);
            }

            $arFile = explode('/', $file);

            if (preg_match("#[^\\\\/a-zA-Z0-9_]#", $file)) {
                return false;
            } elseif ($arFile[0] === 'bitrix') {
                return false;
            } elseif ($arFile[2] !== 'tests') {
                return false;
            }

            $module = array_shift($arFile) . '.' . array_shift($arFile);

            if (!Loader::includeModule($module)) {
                return false;
            }

            $path = getLocalPath('/modules/' . $module . '/' . implode('/', $arFile) . '.php');

            if ($path !== false) {
                include_once $this->getDocumentRoot() . $path;
            }
        });
    }

    /**
     * Gets root directory from which are running Console Jedi.
     *
     * @return string
     */
    public function getRoot()
    {
        return getcwd();
    }

    /**
     * Sets path to the document root of site.
     *
     * @param string $dir Path to document root.
     */
    public function setDocumentRoot($dir)
    {
        $_SERVER['DOCUMENT_ROOT'] = $this->documentRoot = $dir;
    }

    /**
     * Gets document root of site.
     *
     * @return null|string
     */
    public function getDocumentRoot()
    {
        return $this->documentRoot;
    }
}