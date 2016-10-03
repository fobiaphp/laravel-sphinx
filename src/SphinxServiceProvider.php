<?php
/**
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection;

use Illuminate\Support\Facades\Event;
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

        //$this->app->singleton('Sphinx', function ($app) {
        //    return new SphinxQLConnection();
        //});
    }

    public function boot()
    {
        $app = $this->app;

        // Роуты для тестов
        if ($app['config']->get('app.debug') && !\App::runningInConsole()) {
            // TODO: [sphinx] устанавливаем имя ддля дебагера
            // Sphinx::getConnection()->setDatabaseName('Sphinx');
            
            $file = dirname(__FILE__) . '/routes.php';
            if (file_exists($file)) {
                \Route::group(['prefix' => 'tests-sphinx'], function () {
                    include dirname(__FILE__) . '/routes.php';
                });
            } 
        }

        return parent::boot();
    }
}
