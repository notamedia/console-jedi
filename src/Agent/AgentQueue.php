<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Agent;

/**
 * Queue Bitrix agents.
 */
class AgentQueue
{
    /**
     * Adds new agent to the queue.
     * 
     * Example:
     * ```php
     * use Notamedia\ConsoleJedi\Agent\AgentQueue;
     * use Project\Module\DemoAgent;
     * 
     * AgentQueue::add(
     *      DemoAgent::class,
     *      ['arg1', true],
     *      [
     *          ['export' => [100500]]
     *      ],
     *      'project.module'
     * );
     * ```
     * The result: will be the registered agent `\Project\Module\DemoAgent::agent('arg1', true)->export(100500);`.
     * 
     * @param string $className Agent class name.
     * @param array $args Arguments for `__constructor` of agent class.
     * @param array $callChain Chain methods with arguments for add them to agent name for execution.
     * @param string $module Module name agent.
     * @param string $isPeriod `Y` if periodic or 'N'.
     * @param int $interval Time interval between execution.
     * @param null $checkTime First check for execution time.
     * @param string $active `Y` if agent active or 'N'.
     * @param null $execTime First execution time.
     * @param null $sort Sorting.
     * @param bool $userId User ID.
     * @param bool $existError Return error, if agent already exist.
     * 
     * @return bool
     */
    public static function add($className, array $args = [], array $callChain = [], $module = null, $isPeriod = 'N', 
        $interval = 86400, $checkTime = null, $active = 'Y', $execTime = null, $sort = null, $userId = false, 
        $existError = true)
    {
        $agent = new \CAgent;
        
        return $agent->AddAgent(
            AgentHelper::getAgentName($className, $args, $callChain),
            $module, 
            $isPeriod,
            $interval,
            $checkTime,
            $active, 
            $execTime,
            $sort,
            $userId,
            $existError
        );
    }
}