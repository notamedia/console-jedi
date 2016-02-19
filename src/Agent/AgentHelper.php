<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Agent;

class AgentHelper
{
    public static function getAgentName($className, array $args = [], array $callChain = [])
    {
        $chain = '';

        if (!empty($callChain))
        {
            foreach ($callChain as $method => $methodArgs)
            {
                $chain .= '->' . $method . '(' . static::convertArgsToString($methodArgs) . ')';
            }
        }

        return '\\' . $className . '::agent(' . static::convertArgsToString($args). ')' . $chain . ';';
    }

    protected static function convertArgsToString(array $args)
    {
        $args = json_encode($args, JSON_UNESCAPED_SLASHES);
        $args = str_replace(',', ', ', $args);
        $args = substr($args, 1);
        $args = substr($args, 0, -1);

        return $args;
    }
}