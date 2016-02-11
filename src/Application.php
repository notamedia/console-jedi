<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi;

use Bitrix\Main\DB\ConnectionException;
use Bitrix\Main\IO\File;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Notamedia\ConsoleJedi\Command\Agents;
use Notamedia\ConsoleJedi\Command\Cache;
use Notamedia\ConsoleJedi\Command\Environment;
use Notamedia\ConsoleJedi\Command\InitCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
    
    protected $bitrixStatus = Application::BITRIX_STATUS_UNAVAILABLE;
    
    protected $documentRoot = null;

    /**
     * {@inheritdoc}
     */
    public function __construct($webDir = null)
    {
        if ($webDir)
        {
            $this->documentRoot = $_SERVER['DOCUMENT_ROOT'] = $this->getRoot() . '/' . $webDir;
        }
        
        parent::__construct('Console Jedi', static::VERSION);
    }

    /**
     * {@inheritdoc}
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        $this->initializeBitrix();

        if ($this->getBitrixStatus() && $moduleCommands = $this->getModulesCommands())
        {
            $this->addCommands($moduleCommands);
        }
        
        return parent::run($input, $output);
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
            $moduleBitrixDir = $this->documentRoot . BX_ROOT . '/modules/' . $module['ID'];
            $moduleLocalDir = $this->documentRoot . '/local/modules/' . $module['ID'];
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
            !$this->documentRoot
            || !is_file($this->documentRoot . '/bitrix/.settings.php')
            || !is_file($this->documentRoot . '/bitrix/php_interface/dbconn.php'))
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