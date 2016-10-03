<?php

namespace Fobia\Database\SphinxConnection\Test;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Symfony\Component\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    
    public function setUp()
    {
        parent::setUp();
        $this->setUpDatabase($this->app);
    }
    
    //public function tearDown()
    //{
    //    $db = \DB::connection('sphinx');
    //    $db->flushQueryLog();
    //    $db->disconnect();
    //
    //    parent::tearDown();
    //}
    
    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \Fobia\Database\SphinxConnection\SphinxServiceProvider::class
        ];
    }
    
    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $this->initializeDirectory($this->getTempDirectory());
        
        $app['config']->set('database.default', 'sphinx');
        $app['config']->set('database.connections.sphinx', [
            'driver' => 'sphinx',
            'host' => '127.0.0.1',
            'port' => 9306,
            'database' => null, // 'SphinxRT',
            'username' => '',
            // 'password' => '',
            'charset' => 'utf8',
            'prefix' => '',
            'collation' => null,
        ]);
        
        $app->bind('path.public', function () {
            return $this->getTempDirectory();
        });
        
        $app['config']->set('app.key', '6rE9Nz59bGRbeMATftriyQjrpF7DcOQm');
    }
    
    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setUpDatabase($app)
    {

    }
    
    protected function initializeDirectory($directory)
    {
        $fs = new Filesystem();
        if ($fs->exists($directory)) {
            $fs->remove($directory);
        }
        $fs->mkdir($directory);
    }
    
    public function getTempDirectory($suffix = '')
    {
        return dirname(__FILE__) . '/temp' . ($suffix == '' ? '' : '/' . $suffix);
    }
}
