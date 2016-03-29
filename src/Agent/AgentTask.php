<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Agent;

use Bitrix\Main\Type\DateTime;

/**
 * Builder for create new agents in the Bitrix queue.
 * 
 * Usage:
 * ```php
 * use Notamedia\ConsoleJedi\Agent\AgentTask;
 * use Vendor\Module\TestAgent;
 *
 * AgentTask::builder()
 *      ->setClass(TestAgent::class)
 *      ->setConstructorArgs(['arg1', true])
 *      ->setCallChain([
 *          ['execute' => [100500]]
 *      ]),
 *      ->setModule('vendor.module')
 *      ->create();
 * ```
 * The result: will be the registered agent `\Vendor\Module\TestAgent::agent('arg1', true)->execute(100500);`.
 * 
 * @author Nik Samokhvalov <nik@samokhvalov.info>
 */
class AgentTask
{
    protected $class;
    protected $constructorArgs;
    protected $callChain;
    protected $module;
    protected $interval;
    protected $periodically = false;
    protected $active = true;
    protected $executionTime;
    protected $sort;
    protected $userId;

    /**
     * Builder for create new task to the queue of agents.
     *
     * @return static
     */
    public static function build()
    {
        return new static;
    }

    /**
     * Sets agent class name.
     * 
     * @param string $className
     *
     * @return $this
     */
    public function setClass($className)
    {
        $this->class = $className;
        
        return $this;
    }

    /**
     * Sets the arguments for `__constructor` of agent class.
     * 
     * @param array $args
     *
     * @return $this
     */
    public function setConstructorArgs(array $args)
    {
        $this->constructorArgs = $args;

        return $this;
    }

    /**
     * Sets the chain methods with arguments for add them to agent name for execution.
     * 
     * @param array $callChain
     *
     * @return $this
     */
    public function setCallChain(array $callChain)
    {
        $this->callChain = $callChain;

        return $this;
    }

    /**
     * Sets the name of the module to which the agent belongs.
     * 
     * @param string $moduleName
     *
     * @return $this
     */
    public function setModule($moduleName)
    {
        $this->module = $moduleName;

        return $this;
    }

    /**
     * Sets the time interval between execution.
     * 
     * @param int $seconds
     *
     * @return $this
     */
    public function setInterval($seconds)
    {
        $this->interval = (int) $seconds;

        return $this;
    }

    /**
     * Sets the periodically mode of agent.
     * 
     * @param bool $periodically
     *
     * @return $this
     */
    public function setPeriodically($periodically)
    {
        $this->periodically = (bool) $periodically;

        return $this;
    }

    /**
     * Sets the activity of agent.
     * 
     * @param bool $active
     *
     * @return $this
     */
    public function setActive($active)
    {
        $this->active = (bool) $active;

        return $this;
    }

    /**
     * Sets first execution time.
     * 
     * @param DateTime $time
     *
     * @return $this
     */
    public function setExecutionTime(DateTime $time)
    {
        $this->executionTime = $time;

        return $this;
    }

    /**
     * Sets sorting.
     * 
     * @param int $sort
     *
     * @return $this
     */
    public function setSort($sort)
    {
        $this->sort = (int) $sort;

        return $this;
    }

    /**
     * Sets user ID on whose behalf the agent is executed.
     * 
     * @param int $userId User ID.
     *
     * @return $this
     */
    public function setUserId($userId)
    {
        $this->userId = (int) $userId;

        return $this;
    }

    /**
     * Convertation property for creation agent in queue through the old and dirty Bitrix API.
     */
    protected function convertation()
    {
        if ($this->executionTime instanceof DateTime)
        {
            $this->executionTime = $this->executionTime->toString();
        }
        elseif ($this->executionTime === null)
        {
            $time = new DateTime();
            $this->executionTime = $time->toString();
        }
        
        foreach (['periodically', 'active'] as $property)
        {
            if ($this->$property === true)
            {
                $this->$property = 'Y';
            }
            else
            {
                $this->$property = 'N';
            }
        }
    }

    /**
     * Create agent in Bitrix queue.
     * 
     * @param bool $checkExist Return false and set `CAdminException`, if agent already exist.
     *
     * @return bool|int ID of agent or false if `$checkExist` is true and agent already exist.
     */
    public function create($checkExist = false)
    {
        $this->convertation();

        $model = new \CAgent;

        return $model->AddAgent(
            AgentHelper::createName($this->class, $this->constructorArgs, $this->callChain),
            $this->module,
            $this->periodically,
            $this->interval,
            null,
            $this->active,
            $this->executionTime,
            $this->sort,
            $this->userId,
            $checkExist
        );
    }
}