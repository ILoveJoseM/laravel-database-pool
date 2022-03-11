<?php
/**
 * Created by PhpStorm.
 * User: chenyu
 * Date: 2022-03-10
 * Time: 11:18
 */

namespace JoseChan\Laravel\Database\Pool\Utils;


use Illuminate\Database\Connection;
use JoseChan\Laravel\Database\Pool\Database\PdoPoolManager;

/**
 * 连接资源类
 * 用来在协程结束时销毁并归还借来的连接
 * Class ConnectionResource
 * @package JoseChan\Laravel\Database\Pool\Utils
 */
class ConnectionResource
{
    /** @var Connection $connection */
    private $connection;
    /** @var PdoPoolManager $manager */
    private $manager;
    /** @var string $name */
    private $name;

    /**
     * ConnectionResource constructor.
     * @param Connection $connection 连接
     * @param string $name 连接
     * @param PdoPoolManager $manager 连接
     */
    public function __construct($name, $connection, $manager)
    {
        $this->name = $name;
        $this->connection = $connection;
        $this->manager = $manager;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 析构函数，归还连接
     */
    public function __destruct()
    {
        $this->manager->revert($this->name, $this->connection);
    }
}
