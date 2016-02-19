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
     * @param string $extraCall
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
    public static function add($className, array $args = [], $extraCall = null, $module = null, $isPeriod = 'N', 
        $interval = 86400, $checkTime = null, $active = 'Y', $execTime = null, $sort = null, $userId = false, 
        $existError = true)
    {
        $agent = new \CAgent;
        
        return $agent->AddAgent(
            static::getAgentName($className, $args, $extraCall),
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
    
    public static function getAgentName($className, array $args = [], $extraCall = null)
    {
        if (is_string($extraCall) && substr($extraCall, -1) !== ';')
        {
            $extraCall .= ';';
        }
        elseif (!is_string($extraCall))
        {
            $extraCall = ';';
        }

        $args = json_encode($args, JSON_UNESCAPED_SLASHES);
        $args = str_replace(',', ', ', $args);
        $args = substr($args, 1);
        $args = substr($args, 0, -1);

        return '\\' . $className . '::agent(' . $args. ')' . $extraCall;
    }
}