<?php
/**
 * Created by PhpStorm.
 * User: chenyu
 * Date: 2022-03-10
 * Time: 11:18
 */

namespace JoseChan\Laravel\Database\Pool\Utils;


use JoseChan\Laravel\Database\Pool\Database\PdoPoolManager;

class ConnectionResource
{
    private $connection;
    /** @var PdoPoolManager $manager */
    private $manager;
    /** @var string $name */
    private $name;

    /**
     * ConnectionResource constructor.
     * @param $connection
     */
    public function __construct($name, $connection, $manager)
    {
        $this->name = $name;
        $this->connection = $connection;
        $this->manager = $manager;
    }

    public function __destruct()
    {
        $this->manager->revert($this->name, $this->connection);
    }
}
