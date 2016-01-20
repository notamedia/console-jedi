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

class Application extends \Symfony\Component\Console\Application
{
    const VERSION = '1.0.0';
    
    const BITRIX_STATUS_UNAVAILABLE = 0;
    
    const BITRIX_STATUS_NO_DB_CONNECTION = 10;
    
    const BITRIX_STATUS_COMPLETE = 20;
    
    protected $bitrixStatus = Application::BITRIX_STATUS_UNAVAILABLE;
    
    protected $documentRoot = null;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->configure();        
        $this->initializeBitrix();
        
        parent::__construct('Console Jedi', static::VERSION);
        
        if ($this->getBitrixStatus() && $moduleCommands = $this->getModulesCommands())
        {
            $this->addCommands($moduleCommands);
        }
    }
    
    protected function configure()
    {
        if (isset($_SERVER['DOCUMENT_ROOT']) && strlen($_SERVER['DOCUMENT_ROOT']) > 0)
        {
            $this->documentRoot = $_SERVER['DOCUMENT_ROOT'];
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();
        
        return array_merge($commands, [
            new Agents\OnCronCommand(),
            new Agents\RunCommand(),
            new Cache\ClearCommand(),
            new Environment\InitCommand()
        ]);
    }
    
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

    public function getBitrixStatus()
    {
        return $this->bitrixStatus;
    }
    
    public function getRoot()
    {
        return getcwd();
    }
}