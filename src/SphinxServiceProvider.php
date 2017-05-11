<?php
/**
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection;

use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;

/**
 * Class SphinxServiceProvider
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */
class SphinxServiceProvider extends ServiceProvider
{

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
            Connection::resolverFor('sphinx', function ($connection, $database, $prefix, $config) {
                return new SphinxConnection($connection, $database, $prefix, $config);
            });
        }
    }
}
