<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Console\Command\Environment;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Config\Option;
use Bitrix\Main\IO\File;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ModuleManager;
use Notamedia\ConsoleJedi\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Filesystem\Filesystem;

class InitCommand extends Command
{
    /**
     * @var string Path to the environment directory.
     */
    protected $dir;
    /**
     * @var array Settings for current environment. The contents of the file `config.php`.
     */
    protected $config = [];
    /**
     * @var array
     */
    protected $bootstrap = ['copyFiles'];
    /**
     * @var array
     */
    protected $excludedFiles = ['config.php'];

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
        
        $dir = $this->getApplication()->getRoot() . '/' . $this->getApplication()->getConfiguration()['env-dir'];

        if (!is_dir($dir))
        {
            throw new \Exception('Directory ' . $dir . ' is missing');
        }

        $environments = include $dir . '/index.php';

        if (!is_array($environments))
        {
            throw new \Exception('File with description of environments is missing');
        }
        elseif (count($environments) == 0)
        {
            throw new \Exception('Environments not found in description file');
        }

        if ($input->getArgument('type'))
        {
            $code = $input->getArgument('type');

            if (!isset($environments[$code]))
            {
                throw new \Exception('Invalid environment code!');
            }
        }
        else
        {
            foreach ($environments as $code => $environment)
            {
                $choices[$code] = $environment['name'];
            }
            
            $questionHelper = $this->getHelper('question');
            $question = new ChoiceQuestion('<info>Which environment install?</info>', $choices, false);
            $code = $questionHelper->ask($input, $output, $question);
        }

        if (!isset($environments[$code]['path']))
        {
            throw new \Exception('Environment path not found!');
        }

        $this->dir = $dir . '/' . $environments[$code]['path'];
        $this->config = include $this->dir . '/config.php';
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
        
        foreach ($this->config as $config => $settings)
        {
            $method = 'configure' . ucfirst($config);

            if (!in_array($method, $this->bootstrap) && method_exists($this, $method))
            {
                $output->writeln('<comment>Setup "' . $config . '"</comment>');
                $this->$method($input, $output, $settings);
            }
        }
    }

    /**
     * Copy files and directories from the environment directory to application.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function copyFiles(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>Copy files from the environment directory</comment>');

        $fs = new Filesystem();

        $directoryIterator = new \RecursiveDirectoryIterator($this->dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);
        
        foreach ($iterator as $item)
        {
            if (in_array($iterator->getSubPathName(), $this->excludedFiles))
            {
                continue;
            }
            
            $itemPath = $this->getApplication()->getRoot() . '/' . $iterator->getSubPathName();
            
            if ($item->isDir())
            {
                $fs->mkdir($itemPath);
            }
            else
            {
                $fs->copy($item, $itemPath, true);
            }

            $output->writeln('   ' . $itemPath);
        }
    }

    /**
     * Sets license key Bitrix CMS.
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $licenseKey
     */
    protected function configureLicenseKey(InputInterface $input, OutputInterface $output, $licenseKey)
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
    protected function configureModules(InputInterface $input, OutputInterface $output, array $modules)
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
    protected function configureSettings(InputInterface $input, OutputInterface $output, array $settings)
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
    protected function configureCluster(InputInterface $input, OutputInterface $output, array $cluster)
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
    protected function configureOptions(InputInterface $input, OutputInterface $output, array $options)
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
