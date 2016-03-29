<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Agent\Command;

use Notamedia\ConsoleJedi\Application\Command\BitrixCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Execution of tasks from agents queue.
 * 
 * @author Nik Samokhvalov <nik@samokhvalov.info>
 */
class ExecuteCommand extends BitrixCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('agent:execute')
            ->setDescription('Execution of tasks from agents queue');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        @set_time_limit(0);
        @ignore_user_abort(true);
        define('CHK_EVENT', true);

        $agentManager = new \CAgent();
        $agentManager->CheckAgents();

        define('BX_CRONTAB_SUPPORT', true);
        define('BX_CRONTAB', true);

        $eventManager = new \CEvent();
        $eventManager->CheckEvents();
    }
}
