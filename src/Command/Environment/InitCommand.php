<?php
/**
 * @link https://github.com/notamedia/console-jedi
 * @copyright Copyright © 2016 Notamedia Ltd.
 * @license MIT
 */

namespace Notamedia\ConsoleJedi\Command\Environment;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Config\Option;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ModuleManager;
use Notamedia\ConsoleJedi\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class InitCommand extends Command
{
    /**
     * @var string Path to the environment directory.
     */
    protected $dir;
    /**
     * @var array Settings for current environment.
     */
    protected $configs = [];
    /**
     * @var array
     */
    protected $bootstrap = ['copyFiles'];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('env:init')
            ->setDescription('Init environment settings')
            ->setHelp('Run command and select environment from the list')
            ->addArgument('type', InputArgument::OPTIONAL, 'Type of the environments')
            ->addOption('memcache-cold-start', null, null, 'All memcache servers adds with status "ready"');
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $dir = $this->getApplication()->getRoot() . '/environments/';

        if (!Directory::isDirectoryExists($dir))
        {
            throw new \Exception('Environments directory not found');
        }

        $environments = include $dir . 'index.php';

        if (!is_array($environments))
        {
            throw new \Exception('Environment\'s description file not found!');
        }
        elseif (count($environments) == 0)
        {
            throw new \Exception('Environments not found in description file!');
        }

        if ($input->getArgument('type'))
        {
            $code = $input->getArgument('type');
        }
        else
        {
            $output->writeln('<info>Available environments:</info>');

            foreach ($environments as $code => $settings)
            {
                $output->writeln('<info> ' . $code . ' - ' . $settings['name'] . '</info>');
            }

            $questionHelper = $this->getHelper('question');
            $question = new Question('<question>Enter environment\'s name: </question>', false);
            $question->setAutocompleterValues(array_keys($environments));
            $code = trim($questionHelper->ask($input, $output, $question));
        }

        if (!isset($environments[$code]))
        {
            throw new \Exception('Invalid environment name!');
        }
        elseif (!isset($environments[$code]['path']))
        {
            throw new \Exception('Environment path not found!');
        }

        $this->dir = $dir . $environments[$code]['path'];
        $this->configs = include $this->dir . '/config.php';
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->bootstrap as $method)
        {
            $this->$method($input, $output);
        }
        
        $this->getApplication()->initializeBitrix();
        
        foreach ($this->configs as $config => $settings)
        {
            $method = $config . 'Config';

            if (!in_array($method, $this->bootstrap) && method_exists($this, $method))
            {
                $output->writeln('<comment>Setup "' . $config . '"</comment>');
                $this->$method($input, $output, $settings);
            }
        }
    }

    /**
     * Copy files from the environment directory to application.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function copyFiles(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>Copy files from the environment directory to application.</comment>');
    }

    /**
     * Sets license key Bitrix CMS.
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $licenseKey
     */
    protected function licenseKeyConfig(InputInterface $input, OutputInterface $output, $licenseKey)
    {
        if (!is_string($licenseKey))
        {
            throw new \InvalidArgumentException('Config "licenseKey" must be string type.');
        }
        
        $licenseFileContent = "<" . "? $" . "LICENSE_KEY = \"" . EscapePHPString($licenseKey) . "\"; ?" . ">";
        File::putFileContents(Application::getDocumentRoot() . BX_ROOT . '/license_key.php', $licenseFileContent);
    }

    /**
     * Installation modules.
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $modules
     *
     * @throws LoaderException
     */
    protected function modulesConfig(InputInterface $input, OutputInterface $output, array $modules)
    {
        foreach ($modules as $module)
        {
            if (!ModuleManager::isModuleInstalled($module))
            {
                ModuleManager::registerModule($module);
            }
            
            if (!Loader::includeModule($module))
            {
                $output->writeln('   ' . $module . ': <error>FAILED</error>');
            }
            else
            {
                $output->writeln('   ' . $module);
            }
        }
    }

    /**
     * Sets configs to .settings.php.
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $settings
     */
    protected function settingsConfig(InputInterface $input, OutputInterface $output, array $settings)
    {
        $configuration = Configuration::getInstance();

        foreach ($settings as $name => $value)
        {
            $configuration->setValue($name, $value);
        }
    }

    /**
     * Installation config to module "cluster".
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $cluster
     *
     * @throws \Bitrix\Main\LoaderException
     * @throws \Exception
     */
    protected function clusterConfig(InputInterface $input, OutputInterface $output, array $cluster)
    {
        global $APPLICATION;

        if (!Loader::includeModule('cluster'))
        {
            throw new \Exception('Failed to load module "cluster"');
        }

        $memcache = new \CClusterMemcache;

        if (isset($cluster['memcache']))
        {
            $output->writeln('   <comment>memcache</comment>');

            if (!is_array($cluster['memcache']))
            {
                throw new \Exception('Server info must be an array');
            }

            $rsServers = $memcache->GetList();

            while ($server = $rsServers->Fetch())
            {
                $memcache->Delete($server['ID']);
            }

            foreach ($cluster['memcache'] as $index => $server)
            {
                $serverId = $memcache->Add($server);

                if ($serverId && !$input->getOption('memcache-cold-start'))
                {
                    $memcache->Resume($serverId);
                }
                else
                {
                    $exception = $APPLICATION->GetException();
                    $message = 'Invalid memcache config with index ' . $index;

                    if ($exception->GetString())
                    {
                        $message = str_replace('<br>', "\n", $exception->GetString());
                    }

                    $output->writeln('<error>' . $message . '</error>');
                }
            }
        }
    }

    /**
     * Installation of option modules.
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $options
     */
    protected function optionsConfig(InputInterface $input, OutputInterface $output, array $options)
    {
        if (empty($options))
        {
            return;
        }

        foreach ($options as $module => $moduleOptions)
        {
            if (!is_array($moduleOptions) || empty($moduleOptions))
            {
                continue;
            }

            foreach ($moduleOptions as $code => $value)
            {
                if (is_array($value))
                {
                    if (isset($value['value']) && isset($value['siteId']))
                    {
                        Option::set($module, $code, $value['value'], $value['siteId']);
                    }
                    else
                    {
                        $output->writeln('<error>Invalid option for module "' . $module . '" with code "' . $code. '"</error>');
                    }
                }
                else
                {
                    Option::set($module, $code, $value);
                }
            }
        }
    }
}
