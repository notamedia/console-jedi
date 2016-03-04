<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Agent;

/**
 * Abstract class agent for development simple workers.
 *
 * Algorithm of agent execution:
 * 1. Bitrix launches method `Agent::agent()->run()`. Your agents should be registered in the same format:
 * `\Vendor\Packeage\ClassName::agent()->run();`. All arguments from this method will be duplicated to the 
 * object constructor:
 * `agent($arg1, …, $arg2)` → `__construct($arg1, …, $arg2)`.
 * 2. Create an object of agent class.
 * 3. Call `init()` method. It is needed for some initial operations, for example: loading required modules.
 * 4. Call `execute()` method. This will execute main agent's logic.
 * 
 * @author Nik Samokhvalov <nik@samokhvalov.info>
 */
abstract class Agent
{
    use AgentTrait;
    
    /**
     * Runs the Agent.
     *
     * Notice, that overriding agent's initialisation and body, should be done though `init` and `execute` methods, 
     * not here.
     *
     * @see Agent::init()
     * @see Agent::execute()
     */
    public function run()
    {
        $this->init();
        
        return $this->execute();
    }

    /**
     * Initialization of the agent.
     */
    protected function init()
    {
    }

    /**
     * Agent execution.
     * 
     * @return string Agent name if need again add his to queue. Use `$this->getAgentName()` for get name of agent.
     */
    protected function execute()
    {
    }
}
