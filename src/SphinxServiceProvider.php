<?php
/**
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection;

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

        // Add database driver.
        $this->app->resolving('db', function ($db) {
            $db->extend('sphinx', function ($config, $name) {
                $config['name'] = $name;
                return new SphinxConnection($config);
            });
        });
    }
}
