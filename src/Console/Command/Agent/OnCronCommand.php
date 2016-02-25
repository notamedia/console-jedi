<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Console\Command\Agent;

use Bitrix\Main\Config\Option;
use Notamedia\ConsoleJedi\Console\Command\BitrixCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Installation configurations for run Agents on cron.
 * 
 * @author Nik Samokhvalov <nik@samokhvalov.info>
 */
class OnCronCommand extends BitrixCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('agent:on-cron')
            ->setDescription('Installation configurations for run Agents on cron');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Option::set('main', 'agents_use_crontab', 'N');
        Option::set('main', 'check_agents', 'N');
    }
}
