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
 * 3. Call execution method in Agent class.
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
     * Running Agent by Bitrix.
     *
     * Bitrix calls this method to run Agent. Your Agents should be registered in the same format:
     * `\Vendor\Packeage\ClassName::agent();`. All arguments from this method should be duplicated in the object
     * constructor:
     *
     * `agent($arg1, …, $arg2)` → `__construct($arg1, …, $arg2)`.
     *
     * @return static
     */
    public static function agent()
    {
        static::$constructorArgs = func_get_args();
        
        $reflection = new \ReflectionClass(get_called_class());

        return $reflection->newInstanceArgs(static::$constructorArgs);
    }

    /**
     * Gets agent name for queue of Bitrix.
     * 
     * @param string $extraCall String with the call any method from Agent class.
     * 
     * @return string
     */
    public function getAgentName($extraCall = null)
    {
        return AgentQueue::getAgentName(get_called_class(), static::$constructorArgs, $extraCall);
    }
}
