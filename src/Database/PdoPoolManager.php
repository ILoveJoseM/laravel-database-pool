<?php
/**
 * Created by PhpStorm.
 * User: chenyu
 * Date: 2022-03-04
 * Time: 09:49
 */

namespace JoseChan\Laravel\Database\Pool\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use JoseChan\Laravel\Database\Pool\Utils\ConnectionResource;
use Swoole\Coroutine;
use Swoole\Coroutine\Context as CoContext;

/**
 * Pdo连接池，继承自DatabaseManager
 * 主要改造了$connections存放的数组结构
 * 以及重写了获取连接的方式
 * Class PdoPoolManager
 * @package JoseChan\Laravel\Database\Pool\Database
 */
class PdoPoolManager extends DatabaseManager
{

    /**
     * The active connection instances.
     *
     * @var array
     */
    protected $connections = [];

    /**
     * @var int $max 最大连接数
     */
    protected $max = 10;

    /**
     * @var array
     */
    protected $borrows = [];

    /** @var array $yieldCo 因为借不到连接被中断的协程 */
    private $yieldCo = [];

    public function __construct(\Illuminate\Foundation\Application $app, ConnectionFactory $factory, $max = 10)
    {
        $this->max = $max;
        parent::__construct($app, $factory);
    }


    /**
     * Get a database connection instance.
     * @param null $name
     * @return Connection
     * @throws \Exception
     */
    public function connection($name = null)
    {
        [$database, $type] = $this->parseConnectionName($name);

        $name = $name ?: $database;

        // 获取当前协程的连接，有连接的时候直接返回
        $connection = $this->getCurrentCoConnection($name);
        if (!empty($connection)) {
            return $connection;
        }

        /**
         * 改成：
         * 1、没有的时候创建
         * 2、当前连接都借出去了，且连接数没达到做大连接数创建
         * 3、如果达到最大连接数且全都借出去了，抛出异常
         */

        $borrows = isset($this->borrows[$name]) ? count($this->borrows[$name]) : 0;
        !isset($this->connections[$name]) && $this->connections[$name] = [];
        if (empty($this->connections[$name]) && $borrows < $this->max) {
            $connection = $this->configure(
                $this->makeConnection($database), $type
            );

            array_push($this->connections[$name], $connection);
        }

        return $this->borrow($name);
    }

    /**
     * 借一个连接
     * @param $name
     * @return Connection|\stdClass
     * @throws \Exception
     */
    private function borrow($name)
    {
        if (!$this->isCoroutine()) {
            // 非协程环境，没必要借用，因为是同步执行，用同一个连接就可以
            /** @var \Illuminate\Database\Connection|\stdClass $connection */
            $connection = $this->connections[$name][0];
        } else {
            while (empty($this->connections[$name])) {
//                throw new \Exception("The number of MySQL connections in the Pool has reached the limit");
                // 当前连接已借用完，因此记录协程ID，并中断协程等待连接释放
                $cid = Coroutine::getuid();
                if (!in_array($cid, $this->yieldCo)) {
                    array_push($this->yieldCo, $cid);
                }
                Coroutine::suspend();
            }
            /** @var \Illuminate\Database\Connection|\stdClass $connection */
            $connection = array_pop($this->connections[$name]);
            $this->coContext($name, $connection);
        }

        $token = $connection->getConfig("token");

        $this->borrows[$name][$token] = $connection;

        return $connection;
    }

    /**
     * 归还连接
     * @param $name
     * @param $connection
     */
    public function revert($name, $connection)
    {
        $token = $connection->getConfig("token");

        if (isset($this->borrows[$name][$token])) {
            unset($this->borrows[$name][$token]);
        }

        array_push($this->connections[$name], $connection);

        // 归还完毕后，看有没有中断的协程，有的话唤醒
        if (!empty($this->yieldCo)) {
            $cid = array_pop($this->yieldCo);
            Coroutine::resume($cid);
        }
    }

    /**
     * 在当前协程注册借到的连接，将会在协程结束时自动归还
     * @param $name
     * @param $connection
     */
    private function coContext($name, $connection)
    {
        $context = Coroutine::getContext();
        if ($context instanceof CoContext) {
            $context['connectionResource'] = new ConnectionResource($name, $connection, $this);
        }
    }

    /**
     * @param $name
     * @return Connection|null
     */
    private function getCurrentCoConnection($name)
    {
        $connection = null;
        if ($this->isCoroutine()) {
            $context = Coroutine::getContext();
            if ($context instanceof CoContext) {
                if (!empty($context['connectionResource']) && $context['connectionResource']->getName() == $name) {
                    $connection = $context['connectionResource']->getConnection();
                }
            }
        }

        return $connection;
    }

    /**
     * 判断当前环境是否在协程环境
     * @return bool
     */
    private function isCoroutine()
    {
        return class_exists(Coroutine::class) && Coroutine::getuid() != -1;
    }
}
