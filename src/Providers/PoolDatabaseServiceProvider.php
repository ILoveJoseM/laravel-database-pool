<?php

namespace JoseChan\Laravel\Database\Pool\Providers;

use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Queue\EntityResolver;
use Illuminate\Database\Eloquent\QueueEntityResolver;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use JoseChan\Laravel\Database\Pool\Database\PdoPoolConnectionFactory;
use JoseChan\Laravel\Database\Pool\Database\PdoPoolManager;

/**
 * Class PoolDatabaseServiceProvider
 * @package JoseChan\Laravel\Database\Pool\Provider
 */
class PoolDatabaseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        Model::setConnectionResolver($this->app['db']);

        Model::setEventDispatcher($this->app['events']);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        Model::clearBootedModels();

        $this->registerConnectionServices();

        $this->registerEloquentFactory();

        $this->registerQueueableEntityResolver();
    }

    /**
     * Register the primary database bindings.
     *
     * @return void
     */
    protected function registerConnectionServices()
    {
        // The connection factory is used to create the actual connection instances on
        // the database. We will inject the factory into the manager so that it may
        // make the connections while they are actually needed and not of before.
        $this->app->singleton('pool_db.factory', function ($app) {
            return new PdoPoolConnectionFactory($app);
        });

        // The database manager is used to resolve various connections, since multiple
        // connections might be managed. It also implements the connection resolver
        // interface which may be used by other components requiring connections.
        $this->app->singleton('db', function ($app) {
            $max = !empty($app['config']['database']['pool']['max']) ? $app['config']['database']['pool']['max'] : 10;
            return new PdoPoolManager($app, $app['pool_db.factory'], $max);
        });

        $this->app->bind('db.connection', function ($app) {
            return $app['db']->connection();
        });
    }

    /**
     * Register the Eloquent factory instance in the container.
     *
     * @return void
     */
    protected function registerEloquentFactory()
    {
        $this->app->singleton(FakerGenerator::class, function ($app) {
            return FakerFactory::create($app['config']->get('app.faker_locale', 'en_US'));
        });

        $this->app->singleton(EloquentFactory::class, function ($app) {
            return EloquentFactory::construct(
                $app->make(FakerGenerator::class), $this->app->databasePath('factories')
            );
        });
    }

    /**
     * Register the queueable entity resolver implementation.
     *
     * @return void
     */
    protected function registerQueueableEntityResolver()
    {
        $this->app->singleton(EntityResolver::class, function () {
            return new QueueEntityResolver;
        });
    }
}
