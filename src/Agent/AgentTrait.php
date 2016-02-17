<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Agent;

/**
 * Trait helps make an Agent from any class.
 *
 * Algorithm of Agent execution:
 * 1. Bitrix launches static method `Agent::agent()`. Your Agents should be registered in the same format:
 * `\Vendor\Packeage\ClassName::agent();`. All arguments from this method will be duplicated to the object constructor:
 * `agent($arg1, …, $arg2)` → `__construct($arg1, …, $arg2)`.
 * 2. Create an object of Agent class.
 * 3. Call `run()` method.
 */
trait AgentTrait
{
    /**
     * @var bool Agent is recurring.
     */
    protected $recurring = true;
    
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
     * Running Agent by Bitrix.
     *
     * Bitrix calls this method to run Agent. Your Agents should be registered in the same format:
     * `\Vendor\Packeage\ClassName::agent();`. All arguments from this method should be duplicated in the object
     * constructor:
     *
     * `agent($arg1, …, $arg2)` → `__construct($arg1, …, $arg2)`.
     *
     * @return string
     */
    public static function agent()
    {
        $reflection = new \ReflectionClass(get_called_class());

        /**
         * @var static $agent
         */
        $agent = $reflection->newInstanceArgs(func_get_args());
        $agent->run();

        if ($agent->isRecurring())
        {
            return static::getAgentName(func_get_args());
        }
    }

    /**
     * Gets agent name for queue of Bitrix.
     * 
     * @param array $constructorArgs Arguments for class `__constructor()`.
     * 
     * @return string
     */
    public static function getAgentName($constructorArgs)
    {
        return '\\' . get_called_class() . '::agent(' . implode(', ', $constructorArgs). ');';
    }

    /**
     * Runs the Agent.
     *
     * Notice, that overriding agent's initialisation and body, should be done though `init` and `execute` methods, not
     * here.
     */
    abstract public function run();

    /**
     * Checks if Agent is the a recurring.
     *
     * @return bool
     */
    public function isRecurring()
    {
        return $this->recurring;
    }
}
