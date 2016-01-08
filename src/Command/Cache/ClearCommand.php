<?php
/**
 * @link https://github.com/notamedia/console-jedi
 * @copyright Copyright © 2016 Notamedia Ltd.
 * @license MIT
 */

namespace Notamedia\ConsoleJedi\Command\Cache;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Data\StaticHtmlCache;
use Notamedia\ConsoleJedi\Command\BitrixCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for clear Bitrix cache.
 */
class ClearCommand extends BitrixCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('cache:clear')
            ->setDescription('Clear all cache')
            ->addOption('dir', 'd', InputOption::VALUE_REQUIRED, 'Clear cache only for directory (relative from /bitrix/cache/)')
            ->addOption('tag', 't', InputOption::VALUE_REQUIRED, 'Clear cache by tag');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dir = $input->getOption('dir');
        $tag = $input->getOption('tag');
        $cache = Cache::createInstance();

        if (empty($dir) && empty($tag))
        {
            Application::getInstance()->getManagedCache()->cleanAll();
            $cache->CleanDir();
            $cache->CleanDir(false, 'stack_cache');
            StaticHtmlCache::getInstance()->deleteAll();

            if (Cache::clearCache(true))
            {
                $output->writeln('<info>All Bitrix cache was deleted</info>');
            }
            else
            {
                $output->writeln('<error>Error deleting Bitrix cache</error>');
            }
        }

        if ($dir)
        {
            $cache->CleanDir($dir);
            $output->writeln('<info>Bitrix cache by "/' . BX_ROOT . '/cache/' . $dir . '" dir was deleted</info>');
        }

        if ($tag)
        {
            Application::getInstance()->getTaggedCache()->ClearByTag($tag);
            $output->writeln('<info>Bitrix cache by tag "' . $tag . '" was deleted</info>');
        }
    }
}