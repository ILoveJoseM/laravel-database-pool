<?php
/**
 * Created by PhpStorm.
 * User: chenyu
 * Date: 2022-03-05
 * Time: 14:44
 */

namespace JoseChan\Laravel\Database\Pool\Database;


use Illuminate\Database\Connectors\ConnectionFactory;

/**
 * 连接工厂
 * Class PdoPoolConnectionFactory
 * @package JoseChan\Laravel\Database\Pool\Database
 */
class PdoPoolConnectionFactory extends ConnectionFactory
{
    /**
     * 创建连接，把一个凭证存到连接的配置项
     * @param array $config
     * @param null $name
     * @return \Illuminate\Database\Connection
     */
    public function make(array $config, $name = null)
    {
        $config['token'] = $this->token();
        return parent::make($config, $name); // TODO: Change the autogenerated stub
    }

    /**
     * 生成凭证
     * @return string
     */
    private function token()
    {
        return md5(microtime() . rand(0, 100));
    }
}
