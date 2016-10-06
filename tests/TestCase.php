<?php

namespace Fobia\Database\SphinxConnection\Test;

use Fobia\Database\SphinxConnection\SphinxConnection;
use Symfony\Component\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @var SphinxConnection
     */
    protected $db;

    // Logger
    protected $traceLog = null;

    public function toggleTraceLog($toggle = null)
    {
        $d = $this->traceLog;
        if ($toggle !== null) {
            $this->traceLog = (bool) $toggle;
        } else {
            $this->traceLog = !$this->traceLog;
        }
        return $d;
    }

    public function traceLog($log)
    {
        if ($this->traceLog) {
            echo ">> DB Query:: " . $log . PHP_EOL;
        }
    }


    protected function getQuery()
    {
        /** @var \Illuminate\Database\Connection $db */
        $db = $this->app['db']->connection('sphinx');
        $log = $db->getQueryLog();
        $log = array_shift($log);
        return $log['query'];
    }

    protected function assertQuery($expectedQuery, $actualQuery = null)
    {
        $expectedQuery = mb_strtolower($expectedQuery);

        if ($actualQuery instanceof  \Illuminate\Database\Eloquent\Builder
            || $actualQuery instanceof  \Illuminate\Database\Query\Builder) {
            $actualQuery = $actualQuery->toSql();
        }

        $actualQuery = ($actualQuery !== null) ? (string) $actualQuery : $this->getQuery();

        $this->traceLog($actualQuery);

        $actualQuery = mb_strtolower($actualQuery);
        // $expectedQuery = preg_replace('/\s+/', ' ', $expectedQuery);
        // $actualQuery = preg_replace('/\s+/', ' ', $actualQuery);

        $expectedQuery = preg_replace(['/\n/', '/\s*,\s*/', '/\s+/', '/\s*=\s*/', '/(?<=\()\s+|\s+(?=\))/'],
            [' ', ', ', ' ', ' = ', ''], $expectedQuery);
        $actualQuery = preg_replace(['/\n/', '/\s*,\s*/', '/\s+/', '/\s*=\s*/', '/(?<=\()\s+|\s+(?=\))/'],
            [' ', ', ', ' ', ' = ', ''], $actualQuery);

        $this->assertEquals($expectedQuery, $actualQuery);
    }

    // =============================================

    public function setUp()
    {
        if ($this->traceLog === null && isset($_ENV['TRACE_QUERY_LOG'])) {
            $this->traceLog = (bool) $_ENV['TRACE_QUERY_LOG'];
        }
        parent::setUp();
    }

    public function tearDown()
    {
        if (!empty($this->db)) {
            $this->db->flushQueryLog();
            $this->db->disconnect();
        }

        parent::tearDown();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \Fobia\Database\SphinxConnection\SphinxServiceProvider::class,
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
            'port' => (!empty($_ENV['SPHINX_PORT']))
                ? $_ENV['SPHINX_PORT']
                : (!empty($_SERVER['SPHINX_PORT']) ? $_SERVER['SPHINX_PORT'] : 9306),
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
        if ($this->db === null) {
            $this->db = $app['db']->connection('sphinx');
            //$this->db = \DB::connection('sphinx');
        }

        try {
            $this->db->reconnect();
        } catch (\Exception $e) {
            $this->db =  $app['db']->connection('sphinx');
        }

        $this->db->enableQueryLog();
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
