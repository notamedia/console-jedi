<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Agent;

class AgentQueue
{
    /**
     * @param string $className
     * @param array $args
     * @param array $callChain
     * @param string $module
     * @param string $isPeriod
     * @param int $interval
     * @param null $checkTime
     * @param string $active
     * @param null $execTime
     * @param null $sort
     * @param bool $userId
     * @param bool $existError
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
    
    public static function delete($className, $args, $extraCall)
    {
        
    }
}