<?php
/**
 * @link https://github.com/notamedia/console-jedi
 * @copyright Copyright © 2016 Notamedia Ltd.
 * @license MIT
 */

namespace Notamedia\ConsoleJedi\Command\Agents;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Installation configurations for run Agents on cron.
 */
class OnCronCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('agents:on-cron')
            ->setDescription('Installation configurations for run Agents on cron');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        \COption::SetOptionString('main', 'agents_use_crontab', 'N');
        \COption::SetOptionString('main', 'check_agents', 'N');
    }
}
