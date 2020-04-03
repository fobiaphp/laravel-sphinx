<?php

namespace Fobia\Database\SphinxConnection\Test\Eloquent\Query;

use Fobia\Database\SphinxConnection\Eloquent\Query\Builder;
use Fobia\Database\SphinxConnection\Test\TestCase;
use Foolz\SphinxQL\Facet;
use Foolz\SphinxQL\Match;

class BuilderTest extends TestCase
{
    /**
     * @var Builder
     */
    public $q;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();

        $this->q = $this->makeQ();
    }

    /**
     * @return Builder
     */
    protected function makeQ()
    {
        return $this->db->table('rt');
    }

    protected function seedRtTable()
    {
        $factors = [
            'id' => 1,
            'title' => 'some title',
            'arr' => [1, 2, 3],
            'keys' => [
                'tag1',
                'tag2',
                'tag3',
            ],
            'tags' => [
                'tag1',
                'tag2',
                'tag3' => [
                    'one' => 'two',
                    'three' => [4, 5],
                ],
            ],
        ];
        $inserts = [];
        for ($i = 0; $i < 10; $i++) {
            $factors['id'] = 1 + $i;
            $factors['title'] = 'some title ' . (1 + $i);

            $inserts[] = [
                'id' => 1 + $i,
                'name' => 'name ' . $i,
                'tags' => $this->db->raw('(1, 2, 3)'),
                'gid' => 1 + $i,
                'greal' => 1.5 + $i,
                'gbool' => true,
                'factors' => json_encode($factors),
            ];
        }
        $this->db->table('rt')->insert($inserts);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown(): void
    {
        if ($this->db) {
            $this->db->statement('TRUNCATE RTINDEX rt');
        }
        parent::tearDown();
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Builder::toSql
     */
    public function testToSql()
    {
        $s = $this->q->select()->toSql();
        $this->assertQuery('select * FROM rt', $s);
    }

    public function test_intType()
    {
        $q = $this->q->where('id', 1);
        $this->assertQuery('select * FROM rt where id = 1', $q);
    }

    public function test_stringType()
    {
        $q = $this->q->where('id', '1');
        $this->assertQuery("select * FROM rt where id = '1'", $q);
    }

    public function test_floatType()
    {
        $q = $this->q->where('id', 1.1);
        $this->assertQuery('select * FROM rt where id = 1.100000', $q);
    }

    public function test_mvaType()
    {
        $q = $this->q->where('id', [1, 2, 3]);
        $this->assertQuery('select * FROM rt where id = (1, 2, 3)', $q);
    }

    public function test_boolType()
    {
        $q = $this->q->where('id', true);
        $this->assertQuery('select * FROM rt where id = 1', $q);
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Builder::replace
     */
    public function testInsert()
    {
        $r = $this->q->insert([
            'id' => 1,
            'name' => 'name',
            //'tags' => [1, 2, 3],
            'tags' => $this->db->raw('(1, 2, 3)'),
            'gid' => 1,
            'greal' => 1.5,
            'gbool' => true,
        ]);

        $this->assertEquals(1, $r);
        $this->assertQuery("insert into rt (id, name, tags, gid, greal, gbool) values (1, 'name', (1, 2, 3), 1, 1.500000, 1)");
    }

    public function testSelect()
    {
        $q = $this->makeQ()->select('id');
        $this->assertQuery('select id FROM rt', $q);

        $q = $this->makeQ()->select('id', 'name');
        $this->assertQuery('select id, name FROM rt', $q);

        $q = $this->makeQ()->select(['*', 'id']);
        $this->assertQuery('select *, id FROM rt', $q);
    }

    public function testSelectJson()
    {
        $q = $this->makeQ()->select('rt.factors.id');
        $this->assertQuery('select factors.id FROM rt', $q);

        $q = $this->makeQ()->select('rt.factors.keys[0]');
        $this->assertQuery('select factors.keys[0] FROM rt', $q);
    }

    public function testWhere()
    {
        $q = $this->makeQ()->where('id', 1);
        $this->assertQuery('select * FROM rt WHERE id = 1', $q);

        $q = $this->makeQ()->where('rt.factors.id', 1);
        $this->assertQuery('select * FROM rt WHERE factors.id = 1', $q);

        $q = $this->makeQ()->where('rt.factors.keys[0]', 1);
        $this->assertQuery('select * FROM rt WHERE factors.keys[0] = 1', $q);
    }

    public function testDelete()
    {
        $this->makeQ()->replace([
            'id' => 1,
        ]);
        $q = $this->q->where('id', 1)->delete();
        $this->assertIsInt($q);

        $this->makeQ()->insert([
            'id' => 1,
        ]);

        //$this->expectException(\Exception::class);
        //$q = $this->makeQ()->where('id', '1')->delete();
        //dump($q, $this->getQuery());
    }

    /**
     * @todo error
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Builder::replace
     */
    public function testReplace()
    {
        $r = $this->q->replace([
            'id' => 1,
            'name' => 'name',
            'tags' => [4, 5, 6],
            'gid' => 2,
            'greal' => 2.5,
            'gbool' => false,
        ]);

        $this->assertTrue((bool) $r);
        $this->assertQuery("replace into rt (id, name, tags, gid, greal, gbool) values (1, 'name', (4, 5, 6), 2, 2.500000, 0)");

        $m = $this->makeQ()->find(1);
        $this->assertEquals(2, (int) $m->gid);
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Builder::update
     */
    public function testUpdate()
    {
        $this->makeQ()->replace([
            'id' => 1,
        ]);
        $m = $this->makeQ()->find(1);
        $this->assertEmpty($m->tags);

        $r = $this->q->where('id', 1)->update([
            'gid' => 2,
            'greal' => 2.5,
            'tags' => [1, 2, 3, 4, 5],
            'gbool' => true,
        ]);
        $this->assertEquals(1, $r);

        $m = $this->makeQ()->find(1);
        $this->assertEquals(2, (int) $m->gid);
        $this->assertNotEmpty($m->tags);
    }

    // public function testUpdateExeption()
    // {
    //     // $this->expectException(\Illuminate\Database\QueryException::class);
    //     $this->makeQ()->replace([
    //         'id' => 1,
    //     ]);
    //     $q = $this->q->where('id', 1)->update([
    //         'gid' => '2',
    //     ]);
    //
    //     $a = 1;;
    // }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Builder::whereMulti
     * @todo   Implement testWhereMulti().
     */
    public function testWhereMulti()
    {
        $q = $this->q->whereMulti('tags', '=', 1, 2, 3, '', [5, 6, 7], null);
        $this->assertQuery(
            'SELECT * FROM rt WHERE tags = 1 AND tags = 2 AND tags = 3 AND tags = 5 AND tags = 6 AND tags = 7',
            $q
        );
    }

    public function testWhereMultiEq()
    {
        $q = $this->makeQ()->whereMulti('id', '=', 1);
        $this->assertQuery('SELECT * FROM rt WHERE id = 1', $q);

        $q = $this->makeQ()->whereMulti('id', '=', '', null, 1);
        $this->assertQuery('SELECT * FROM rt WHERE id = 1', $q);

        $q = $this->makeQ()->whereMulti('id', '=', []);
        $this->assertQuery('SELECT * FROM rt', $q);

        $q = $this->makeQ()->whereMulti('id', '=', '');
        $this->assertQuery('SELECT * FROM rt', $q);
    }

    public function testWhereMultiIn()
    {
        $q = $this->q->whereMulti('tags', 'in', [1, 2, 3, [5, 6, 7]], [10, 11, 12]);
        $this->assertQuery(
            'SELECT * FROM rt WHERE tags in (1) AND tags in (2) AND tags in (3) AND tags in (5, 6, 7) AND tags in (10, 11, 12)',
            $q
        );
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Builder::option
     */
    public function testOption()
    {
        $q = $this->q->option('ranker', 'bm25');
        $this->assertQuery('select * from rt OPTION ranker = bm25', $q);

        $q->option('max_matches', '3000');
        $this->assertQuery('select * from rt OPTION ranker = bm25,max_matches=3000', $q);

        $q->option('field_weights', '(title=10, body=3)');
        $this->assertQuery(
            'select * from rt OPTION ranker = bm25,max_matches=3000, field_weights=(title=10, body=3)',
            $q
        );

        $q->option('agent_query_timeout', '10000');
        $this->assertQuery(
            'select * from rt OPTION ranker = bm25,max_matches=3000, field_weights=(title=10, body=3) , agent_query_timeout=10000',
            $q
        );
        $q->get();
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Builder::option
     */
    public function testOption2()
    {
        $q = $this->q->option('field_weights', ['title' => 10, 'body' => 3]);
        $this->assertQuery('select * from rt OPTION field_weights=(title=10, body=3)', $q);

        $q->option('comment', 'my comment');
        $this->assertQuery('select * from rt OPTION field_weights=(title=10, body=3), comment=\'my comment\'', $q);
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Builder::withinGroupOrderBy
     */
    public function testWithinGroupOrderBy()
    {
        $q = $this->q->select('id');
        $q = $q->withinGroupOrderBy('name');
        $this->assertQuery('SELECT id FROM rt WITHIN GROUP ORDER BY name ASC', $q);

        $q = $q->withinGroupOrderBy('id', 'desc');
        $this->assertQuery('SELECT id FROM rt WITHIN GROUP ORDER BY name ASC, id DESC', $q);
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Builder::withinGroupOrderBy
     */
    public function testWithinGroupOrderByException()
    {
        $this->expectException(\RuntimeException::class);
        $q = $this->q->select('id');
        $q = $q->withinGroupOrderBy('name', 'a');
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Builder::match
     */
    public function testMatch()
    {
        $q = $this->q->match(null);
        $this->assertQuery("select * FROM rt WHERE MATCH('')", $q);

        $q = $this->q->match('');
        $this->assertQuery("select * FROM rt WHERE MATCH('')", $q);

        $q = $this->q->match('text match');
        $this->assertQuery("select * FROM rt WHERE MATCH('(@text match)')", $q);

        $q = $this->makeQ()->match(['name'], 'match');
        $this->assertQuery("select * FROM rt WHERE MATCH('(@(name) match)')", $q);

        $q = $this->makeQ()->match(['name', 'content'], 'match');
        $this->assertQuery("select * FROM rt WHERE MATCH('(@(name,content) match)')", $q);

        $q = $this->makeQ()->match(function (Match $m) {
            $m->match('match');
        });
        $this->assertQuery("select * FROM rt WHERE MATCH('(match)')", $q);

        $q = $this->makeQ()->match(function (Match $m) {
            $m->field('name');
            $m->match('match');
        });
        $this->assertQuery("select * FROM rt WHERE MATCH('(@name match)')", $q);
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Builder::facet
     */
    public function testFacet()
    {
        $mock = new \stdClass();

        $q = $this->q->facet(function (Facet $f) use ($mock) {
            $mock->facet = $f;
            $f->facet('id');
        });
        $this->assertInstanceOf(Facet::class, $mock->facet);

        $this->assertQuery('select * FROM rt FACET id', $q->toSql());

        $q->facet(function ($f) {
            $f->facet('name');
        });
        $this->assertQuery('select * FROM rt FACET id FACET name', $q->toSql());
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Builder::filterParamsUint
     */
    public function testFilterParamsUint()
    {
        $method = new \ReflectionMethod($this->q, 'filterParamsUint');
        $method->setAccessible(true);

        $result = $method->invoke($this->q, [1, 2, null, 4]);
        $this->assertEquals([1, 2, 4], $result);

        $result = $method->invoke($this->q, [1, [2, [null, 4]]]);
        $this->assertEquals([1, 2, 4], $result);

        $result = $method->invoke($this->q, [null, 1, [2, [null, 4]]]);
        $this->assertEquals([1, 2, 4], $result);

        $result = $method->invoke($this->q, [[null, ''], 1, [2, [null, 4]]]);
        $this->assertEquals([1, 2, 4], $result);

        $result = $method->invoke($this->q, [null, null, [null, ''], 1, 2, 4]);
        $this->assertEquals([1, 2, 4], $result);
    }
}
