<?php
/**
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2020 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection;

use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Builder;

/**
 * Class SphinxServiceProvider
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2020 Dmitriy Tyurin
 */
class SphinxServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (class_exists(EngineManager::class)) {
            resolve(EngineManager::class)->extend('sphinx', function () {
                $db = app('db')->connection('sphinx');
                return new SphinxSearchEngine($db);
            });

            Builder::macro('whereValue', function ($a = null, $b = null) {
                return 11;
                return $this->engine->getTotalCount(
                    $this->engine()->search($this)
                );
            });
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind("db.connection.sphinx", SphinxConnection::class);
        $this->app->bind("db.connector.sphinx", SphinxConnector::class);

        if (class_exists(Connection::class) && method_exists(Connection::class, 'resolverFor')) {
            Connection::resolverFor('sphinx', static function ($connection, $database, $prefix, $config) {
                return new SphinxConnection($connection, $database, $prefix, $config);
            });
        }
    }
}
