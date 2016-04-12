<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Search\Command;

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Notamedia\ConsoleJedi\Application\Command\BitrixCommand;
use Notamedia\ConsoleJedi\Application\Exception\BitrixException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for search module reindex
 *
 * @author Marat Shamshutdinov <m.shamshutdinov@gmail.com>
 */
class ReIndexCommand extends BitrixCommand
{
    const UPDATE_TIME = 5;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('search:reindex')
            ->setDescription('Rebuild search index')
            ->addOption('full', 'f', InputOption::VALUE_NONE,
                'Clears existing index (otherwise only changed entries would be indexed)');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!Loader::includeModule('search'))
        {
            throw new BitrixException('Search module is not installed');
        }

        $searchResult = array();

        $bar = new ProgressBar($output, 0);
        do
        {
            $bar->display();

            $searchResult = \CSearch::ReIndexAll($input->getOption('full'), static::UPDATE_TIME, $searchResult);

            $bar->advance();
            $bar->clear();

            if (is_array($searchResult) && $searchResult['MODULE'] == 'main')
            {
                list(, $path) = explode("|", $searchResult["ID"], 2);
                $output->writeln("\r       " . $path, OutputInterface::VERBOSITY_VERBOSE);
            }
        } while (is_array($searchResult));

        $bar->finish();
        $bar->clear();
        $output->write("\r");

        if (ModuleManager::isModuleInstalled('socialnetwork'))
        {
            $output->writeln('<info>The Social Network module needs to be reindexed using the Social Network component in the public section of site.</info>');
        }

        $output->writeln(sprintf('<info>Reindexed</info> %d element%s.', $searchResult, $searchResult > 1 ? 's' : ''));

        return 0;
    }
}