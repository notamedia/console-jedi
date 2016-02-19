<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Agent;

/**
 * Trait helps make an agent from any class.
 *
 * Algorithm of agent execution:
 * 1. Bitrix launches static method `Agent::agent()->%method%()`. Your agents should be registered in the same format:
 * `\Vendor\Packeage\ClassName::agent()->%agent%();`. All arguments from this method will be duplicated to the object 
 * constructor:
 * `agent($arg1, …, $arg2)` → `__construct($arg1, …, $arg2)`.
 * 2. Create an object of agent class.
 * 3. Call execution method in agent class.
 */
trait AgentTrait
{
    /**
     * @var array Arguments for `__constructor`.
     */
    protected static $constructorArgs;
    
    /**
     * Agent constructor.
     *
     * All arguments from `agent()` method should be duplicated in the constructor, for example:
     * ```
     * agent($arg1, …, $arg2)` → `__construct($arg1, …, $arg2)
     * ```
     */
    public function __construct()
    {
    }

    /**
     * Factory method for create object of agent class.
     *
     * Bitrix calls this method to run agent. Your agents should be registered  through 
     * `\Notamedia\ConsoleJedi\Agent\AgentQueue`. All arguments from this method should 
     * be duplicated in the object constructor:
     *
     * `agent($arg1, …, $arg2)` → `__construct($arg1, …, $arg2)`.
     *
     * @return static
     * 
     * @see AgentQueue
     */
    public static function agent()
    {
        static::$constructorArgs = func_get_args();
        
        $reflection = new \ReflectionClass(get_called_class());

        return $reflection->newInstanceArgs(static::$constructorArgs);
    }

    /**
     * Gets agent name. Use to return this name from the executed method of agent.
     * 
     * @param array $callChain Array with the call any methods from Agent class.
     * 
     * @return string
     */
    public function getAgentName(array $callChain = [])
    {
        return AgentHelper::getAgentName(get_called_class(), static::$constructorArgs, $callChain);
    }
}
