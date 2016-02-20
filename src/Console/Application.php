<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Console;

use Bitrix\Main\DB\ConnectionException;
use Bitrix\Main\IO\File;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Notamedia\ConsoleJedi\Console\Command\Agents;
use Notamedia\ConsoleJedi\Console\Command\Cache;
use Notamedia\ConsoleJedi\Console\Command\Environment;
use Notamedia\ConsoleJedi\Console\Command\InitCommand;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Console Jedi application.
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
    const BITRIX_STATUS_UNAVAILABLE = 0;
    /**
     * Bitrix is available, but not have connection to DB.
     */
    const BITRIX_STATUS_NO_DB_CONNECTION = 5;
    /**
     * Bitrix is available.
     */
    const BITRIX_STATUS_COMPLETE = 10;
    /**
     * @var int
     */
    protected $bitrixStatus = Application::BITRIX_STATUS_UNAVAILABLE;
    /**
     * @var null|string
     */
    protected $documentRoot = null;
    
    private $configuration = null;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = 'Console Jedi', $version = self::VERSION)
    {
        parent::__construct('Console Jedi', static::VERSION);
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if ($this->getConfiguration() === null)
        {
            $this->loadConfiguration();
        }

        $this->initializeBitrix();

        if ($this->getBitrixStatus() && $moduleCommands = $this->getModulesCommands())
        {
            $this->addCommands($moduleCommands);
        }
        
        return parent::doRun($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();
        
        return array_merge($commands, [
            new InitCommand(),
            new Agents\OnCronCommand(),
            new Agents\RunCommand(),
            new Cache\ClearCommand(),
            new Environment\InitCommand()
        ]);
    }
    
    public function loadConfiguration($path = self::CONFIG_DEFAULT_FILE)
    {
        if (!is_file($path))
        {
            throw new \Exception('Configuration file ' . $path . ' is missing');
        }
        
        $this->configuration = include $path;
        
        if (!is_array($this->configuration))
        {
            throw new \Exception('Configuration file ' . $path . ' must return an array');
        }

        $_SERVER['DOCUMENT_ROOT'] = $this->getRoot() . '/' . $this->configuration['web-dir'];
    }
    
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Gets console commands from modules.
     * 
     * @return array|null
     * 
     * @throws \Bitrix\Main\LoaderException
     */
    protected function getModulesCommands()
    {
        return [];
        $commands = [];
                
        foreach (ModuleManager::getInstalledModules() as $module)
        {
            $moduleBitrixDir = $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/' . $module['ID'];
            $moduleLocalDir = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/' . $module['ID'];
            $cliFile = '/cli.php';
            
            if (File::isFileExists($moduleBitrixDir . $cliFile))
            {
                $cliFile = $moduleBitrixDir . $cliFile;
            }
            elseif (File::isFileExists($moduleLocalDir . $cliFile))
            {
                $cliFile = $moduleLocalDir . $cliFile;
            }
            else
            {
                continue;
            }
            
            if (!Loader::includeModule($module['ID']))
            {
                continue;
            }
                
            $config = include_once $cliFile;

            if (isset($config['commands']) && is_array($config['commands']))
            {
                $commands = array_merge($commands, $config['commands']);
            }
        }
        
        return !empty($commands) ? $commands : null;
    }

    /**
     * Initialize kernel of Bitrix.
     * 
     * @return int The status of readiness kernel.
     */
    public function initializeBitrix()
    {
        if (!$this->checkBitrix())
        {
            return static::BITRIX_STATUS_UNAVAILABLE;
        }
        
        define('NO_KEEP_STATISTIC', true);
        define('NOT_CHECK_PERMISSIONS', true);

        try
        {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
            
            if (defined('B_PROLOG_INCLUDED') && B_PROLOG_INCLUDED === true)
            {
                $this->bitrixStatus = static::BITRIX_STATUS_COMPLETE;
            }
        }
        catch (ConnectionException $e)
        {
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
            !$_SERVER['DOCUMENT_ROOT']
            || !is_file($_SERVER['DOCUMENT_ROOT'] . '/bitrix/.settings.php')
            || !is_file($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/dbconn.php'))
        {
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
     * Gets root directory from which are running Console Jedi.
     * 
     * @return string
     */
    public function getRoot()
    {
        return getcwd();
    }
}