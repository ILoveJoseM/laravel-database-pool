## 为了实现laravel+swoole协程实现的连接池

>! 暂时不建议在pgsql、sqlite、sqlsrv环境中使用，因为仅测试过mysql连接

#### 安装

>! composer require jose-chan/laravel-database-pool

#### 使用

找到`config/app.php`文件中的`providers`配置，将`Illuminate\Database\DatabaseServiceProvider::class`替换为`JoseChan\Laravel\Database\Pool\Provider\PoolDatabaseServiceProvider::class即可`

````php
<?php 
[
    // …… 省略
    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        //自己实现的数据库相关
        JoseChan\Laravel\Database\Pool\Provider\PoolDatabaseServiceProvider::class,
        // laravel自带的数据库相关注入
//        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,
    ]
    // …… 省略
];

````

#### 代码使用

````php
<?php
class TestController extends \App\Http\Controllers\Controller
{
    public function fetch(\Illuminate\Http\Request $request)
    {

        $coroutine1 = \Swoole\Coroutine::create(function () {
            $c = microtime(true);

            echo "开始查询1\n";
            $orders = \Illuminate\Database\Eloquent\Model::query()->get();
            $d = microtime(true);
            echo "查询1结束", ($d - $c), "\n";
            return $orders;
        });

        $coroutine = \Swoole\Coroutine::create(function () {
            $e = microtime(true);
            echo "开始查询2\n";
            $orders = \Illuminate\Database\Eloquent\Model::query()->get();
            $f = microtime(true);
            echo "查询2结束", ($f - $e), "\n";
            return $orders;
        });
    }
}
````

#### 其他说明

该组建适用于laravel框架+swoole协程框架中，协程并发多个sql异步请求mysql，提高整体的执行效率，在非协程环境下，该组建将不会创建多个mysql连接
