<?php
/**
 * @link https://github.com/notamedia/console-jedi
 * @copyright Copyright © 2016 Notamedia Ltd.
 * @license MIT
 */

namespace Notamedia\ConsoleJedi\Command\Agents;

use Notamedia\ConsoleJedi\Command\BitrixCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends BitrixCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('agents:run')
            ->setDescription('Runs execution of Agents');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        @set_time_limit(0);
        @ignore_user_abort(true);
        define('CHK_EVENT', true);

        \CAgent::CheckAgents();
        define('BX_CRONTAB_SUPPORT', true);
        define('BX_CRONTAB', true);
        \CEvent::CheckEvents();
    }
}
