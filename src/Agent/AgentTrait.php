<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Agent;

use Bitrix\Main\Type\DateTime;

/**
 * Trait helps make an agent from any class.
 *
 * Algorithm of agent execution:
 * 1. Bitrix launches static method `ClassName::agent()->%method%()`. Your agents should be registered through
 * `\Notamedia\ConsoleJedi\Agent\AgentTask` in the same format: `\Vendor\Package\ClassName::agent()->%method%();`.
 * All arguments from this method will be duplicated to the object constructor:
 * `agent($arg1, …, $arg2)` → `__construct($arg1, …, $arg2)`.
 * 2. Create an object of agent class.
 * 3. Call execution method in agent class.
 *
 * @author Nik Samokhvalov <nik@samokhvalov.info>
 */
trait AgentTrait
{
    /**
     * @var array Arguments for `__constructor`.
     */
    protected static $constructorArgs;
    /**
     * @var bool
     */
    protected static $agentMode = false;

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
     * Bitrix calls this method to run agent. Your agents should be registered through
     * `\Notamedia\ConsoleJedi\Agent\AgentTask`. All arguments from this method should
     * be duplicated in the object constructor:
     *
     * `agent($arg1, …, $arg2)` → `__construct($arg1, …, $arg2)`.
     *
     * @return static
     *
     * @see AgentTask
     */
    public static function agent()
    {
        static::$constructorArgs = func_get_args();
        static::$agentMode = true;

        $reflection = new \ReflectionClass(get_called_class());

        return $reflection->newInstanceArgs(static::$constructorArgs);
    }

    /**
     * Ping from the agent to inform that it still works correctly. Use this method if your agent
     * works more 10 minutes, otherwise Bitrix will be consider your agent as non-working.
     *
     * Usage:
     * ```php
     * public function executeAgent($param1, $param2)
     * {
     *      // start a heavy (big) cycle
     *
     *          $this->pingAgent(20, ['executeAgent' => [$param1, $param2]]);
     *
     *      // end of cycle
     * }
     * ```
     *
     * @param int $interval The time in minutes after which the agent will be considered non-working.
     * @param array $callChain Array with the call any methods from Agent class.
     */
    protected function pingAgent($interval, array $callChain)
    {
        if (!$this->isAgentMode()) {
            return;
        }

        $name = $this->getAgentName($callChain);
        $model = new \CAgent();

        $rsAgent = $model->GetList([], ['NAME' => $name]);

        if ($agent = $rsAgent->Fetch()) {
            $dateCheck = DateTime::createFromTimestamp(time() + $interval * 60);

            $pingResult = $model->Update($agent['ID'], ['DATE_CHECK' => $dateCheck->toString()]);

            if (!$pingResult) {
                // @todo warning
            }
        } else {
            // @todo warning
        }
    }

    /**
     * Gets agent name. Use to return this name from the executed method of agent.
     *
     * Usage:
     * ```php
     * public function executeAgent($param1, $param2)
     * {
     *      // main logic
     *
     *      return $this->getAgentName(['executeAgent' => [$param1, $param2]]);
     * }
     * ```
     *
     * @param array $callChain Array with the call any methods from Agent class.
     *
     * @return string
     */
    public function getAgentName(array $callChain)
    {
        return AgentHelper::createName(get_called_class(), static::$constructorArgs, $callChain);
    }

    /**
     * Checks that object running as agent. Object is considered an agent
     * if it is created using the static method `agent()`.
     *
     * @return bool
     */
    public function isAgentMode()
    {
        return static::$agentMode;
    }
}
