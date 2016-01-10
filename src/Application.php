<?php
/**
 * @link https://github.com/notamedia/console-jedi
 * @copyright Copyright © 2016 Notamedia Ltd.
 * @license MIT
 */

namespace Notamedia\ConsoleJedi;

use Bitrix\Main\DB\ConnectionException;
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