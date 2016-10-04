<?php
/**
 * ModelTest.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection\Test;


use Fobia\Database\SphinxConnection\SphinxConnection;
use Foolz\SphinxQL\Facet;
use Foolz\SphinxQL\Match;

class ModelTest extends TestCase
{
    /**
     * @var SphinxConnection
     */
    protected $db;

    // Logger
    protected $traceLog = true;

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
        $db = \DB::connection('sphinx');
        $log = $db->getQueryLog();
        $log = array_shift($log);
        return $log['query'];
    }

    protected function assertQuery($expectedQuery, $actualQuery = null)
    {
        $expectedQuery = mb_strtolower($expectedQuery);

        if ($actualQuery instanceof \Illuminate\Database\Eloquent\Builder) {
            $actualQuery = $actualQuery->toSql();
        }

        $actualQuery = ($actualQuery !== null) ? (string) $actualQuery : $this->getQuery();

        $this->traceLog($actualQuery);

        $actualQuery = mb_strtolower($actualQuery);
        // $expectedQuery = preg_replace('/\s+/', ' ', $expectedQuery);
        // $actualQuery = preg_replace('/\s+/', ' ', $actualQuery);

        $expectedQuery = preg_replace(['/\n/', '/\s*,\s*/', '/\s+/', '/\s*=\s*/', '/\(\s+|\s+\)/'],
            [' ', ', ', ' ', ' = '], $expectedQuery);
        $actualQuery = preg_replace(['/\n/', '/\s*,\s*/', '/\s+/', '/\s*=\s*/', '/\(\s+|\s+\)/'],
            [' ', ', ', ' ', ' = '], $actualQuery);

        $this->assertEquals($expectedQuery, $actualQuery);
    }

    // =============================================

    public function setUp()
    {
        parent::setUp();

        if ($this->db === null) {
            $this->db = \DB::connection('sphinx');
            //\Event::listen('*', function($event) {
            ////\Event::listen('*', function($event) {
            //    dump([func_num_args(), func_get_args()]);
            //    //$this->traceLog($event->sql);
            //});
        }

        try {
            $this->db->reconnect();
        } catch (\Exception $e) {
            $this->db = \DB::connection('sphinx');
        }

        $this->db->enableQueryLog();
    }

    public function tearDown()
    {
        $this->db->flushQueryLog();
        $this->db->disconnect();

        parent::tearDown();
    }

    // =============================================


    public function test_select()
    {
        $q = Model::select('id');
        $this->assertQuery('select id FROM products', $q);

        $q->select('name');
        $this->assertQuery('select name FROM products', $q);

        $q->addSelect('id');
        $this->assertQuery('select name, id FROM products', $q);

        $q->select(['*', 'id']);
        $this->assertQuery('select *, id FROM products', $q);
    }


    public function test_withinGroupOrderBy()
    {
        $q = Model::select('id');
        $q = $q->withinGroupOrderBy('name');
        $this->assertQuery('SELECT id FROM products WITHIN GROUP ORDER BY name ASC', $q);

        $q = $q->withinGroupOrderBy('id', 'desc');
        $this->assertQuery('SELECT id FROM products WITHIN GROUP ORDER BY name ASC, id DESC', $q);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_withinGroupOrderBy_ex()
    {
        $q = Model::select('id');
        $q = $q->withinGroupOrderBy('name', 'a');
    }

    public function test_delete()
    {
        $q = Model::where('id', 999999)->delete();
        $this->assertInternalType('int', $q);
    }

    public function test_insert()
    {
        $id = 999999;
        Model::where('id', $id)->delete();
        $q = Model::insert([
            'id' => '999999',
            'name' => 'new \\\\\\\'name',
        ]);
        $this->assertTrue($q);

        $q = Model::where('id', $id)->delete();
        $this->assertEquals(1, $q);
    }

    public function test_update()
    {
        $id = 999999;
        Model::where('id', $id)->delete();
        $q = Model::insert([
            'id' => '999999',
            'name' => '\\\\\\\'name',
            'itype' => 1,
        ]);
        $this->assertTrue($q);

        $q = Model::where('id', $id)->update([
            'itype' => '2',
        ]);
        $this->assertEquals(1, $q);

        $q = Model::where('id', $id)->update([
            'itype' => 3,
        ]);
        $this->assertEquals(1, $q);

        Model::where('id', $id)->delete();
    }

    public function test_where()
    {
        $q = Model::where('id', 999999);
        dump($q->toSql());
    }


    public function test_match()
    {
        $q = Model::where('id', 999999)->match(function ($m) {
            $m->field('name');
            $m->phrase('phrase');
        });
        $this->assertQuery("select * FROM products WHERE MATCH('(@name \\\"phrase\\\")') AND id = 999999", $q);
    }

    public function test_match_column()
    {
        $q = Model::where('id', 999999)->match(['id'], 'art');
        $this->assertQuery("select * FROM products WHERE MATCH('(@(id) art)') AND id = 999999", $q);

        $q = $q->match(['name'], 'sName');
        $this->assertQuery("select * FROM products WHERE MATCH('(@(id) art) (@(name) sname)') AND id = 999999", $q);
    }

    public function test_matchQl_0()
    {
        $q = Model::where('id', 999999)->match(function ($m) {
            $m->field('name');
            $m->phrase('phrase');
        });

        $this->assertQuery("select * FROM products WHERE MATCH('(@name \\\"phrase\\\")') AND  id = 999999", $q);
    }

    public function test_matchQl_1()
    {
        $q = Model::where('id', 999999)->matchQl(function (Match $m) {
            $m->field('name');
            $m->phrase('phrase');
        });

        $this->assertQuery("select * FROM products WHERE MATCH('(@@name \\\"phrase\\\" )') AND  id = 999999", $q);
    }

    public function test_facet()
    {
        $q = Model::where('id', '>', 1);
        $q->facet(function (Facet $f) {
            $f->facet('name');
        });
        $q->facet(function (Facet $f) {
            $f->facet('id');
        });

        $this->assertQuery("select * FROM products where id  > 1  FACET name FACET id", $q);
    }


    public function test_whereMulti_eq()
    {
        $q = Model::whereMulti('tags', '=', 1, 2, 3, '', [5, 6, 7], null);
        $this->assertQuery("SELECT * FROM products WHERE tags = 1 AND tags = 2 AND tags = 3 AND tags = 5 AND tags = 6 AND tags = 7", $q);
    }

    public function test_whereMulti_eq2()
    {
        $q = Model::whereMulti('id', '=', 1);
        $this->assertQuery("SELECT * FROM products WHERE id = 1", $q);

        $q = Model::whereMulti('id', '=', '', null, 1);
        $this->assertQuery("SELECT * FROM products WHERE id = 1", $q);

        $q = Model::whereMulti('id', '=', []);
        $this->assertQuery("SELECT * FROM products", $q);

        $q = Model::whereMulti('id', '=', '');
        $this->assertQuery("SELECT * FROM products", $q);
    }

    public function test_whereMulti_in()
    {
        $q = Model::whereMulti('tags', 'in', [1, 2, 3, [5, 6, 7]], [10, 11, 12]);
        $this->assertQuery("SELECT * FROM products WHERE tags in (1) AND tags in (2) AND tags in (3) AND tags in (5, 6, 7) AND tags in (10, 11, 12)", $q);
    }
}
