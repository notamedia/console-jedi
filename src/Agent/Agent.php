<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Agent;

/**
 * Abstract class Agent for development simple workers.
 *
 * Algorithm of Agent execution:
 * 1. Bitrix launches static method `Agent::agent()`. Your Agents should be registered in the same format:
 * `\Vendor\Packeage\ClassName::agent();`. All arguments from this method will be duplicated to the object constructor:
 * `agent($arg1, …, $arg2)` → `__construct($arg1, …, $arg2)`.
 * 2. Create an object of Agent class.
 * 3. Call `init()` method. It is needed for some initial operations, for example: loading required modules.
 * 4. Call `execute()` method. This will execute main agent's logic.
 */
abstract class Agent
{
    use AgentTrait;
    
    /**
     * Runs the Agent.
     *
     * Notice, that overriding agent's initialisation and body, should be done though `init` and `execute` methods, not
     * here.
     *
     * @see Agent::init()
     * @see Agent::execute()
     */
    public function run()
    {
        $this->init();
        $this->execute();
    }

    /**
     * Initializes the Agent.
     */
    protected function init()
    {
    }

    /**
     * Executes the Agent.
     */
    protected function execute()
    {
    }
}
