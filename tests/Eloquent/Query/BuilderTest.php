<?php
namespace Fobia\Database\SphinxConnection\Test\Eloquent\Query;

use Fobia\Database\SphinxConnection\Eloquent\Query\Builder;
use Fobia\Database\SphinxConnection\Test\TestCase;

class BuilderTest extends TestCase
{
    /**
     * @var Builder
     */
    protected $q;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
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

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        $this->db->statement("TRUNCATE RTINDEX rt");
        parent::tearDown();
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Builder::toSql
     * @todo   Implement testToSql().
     */
    public function testToSql()
    {
        $s = $this->q->select()->toSql();
        $this->assertQuery('select * FROM rt', $s);
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Builder::replace
     */
    public function testInsert()
    {
        $r = $this->q->insert([
            'id' => 1,
            'name' => 'name',
            'tags' => [1, 2, 3],
            'gid' => 1,
            'greal' => 1.5,
            'gbool' => true
        ]);

        $this->assertEquals(1, $r);
        $this->assertQuery("insert into rt (id, name, tags, gid, greal, gbool) values (1, 'name', (1, 2, 3), 1, 1.5, 1))");
    }
    /**
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
            'gbool' => true
        ]);

        $this->assertEquals(1, $r);
        $this->assertQuery("replace into rt (id, name, tags, gid, greal, gbool) values (1, 'name', (1, 2, 3), 1, 1.5, 1))");
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Builder::update
     * @todo   Implement testUpdate().
     */
    public function testUpdate()
    {
        $this->makeQ()->replace([
            'id' => 1,
        ]);
        $r = $this->q->where('id', 1)->update([
            'gid' => 7,
            'greal' => 8.6,
            'tags' => [1, 2, 3, 4, 5],
            'gbool' => true
        ]);
        $this->assertEquals(1, $r);
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Builder::whereMulti
     * @todo   Implement testWhereMulti().
     */
    public function testWhereMulti()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Builder::options
     * @todo   Implement testOptions().
     */
    public function testOptions()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Builder::withinGroupOrderBy
     * @todo   Implement testWithinGroupOrderBy().
     */
    public function testWithinGroupOrderBy()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Builder::match
     * @todo   Implement testMatch().
     */
    public function testMatch()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Builder::facet
     * @todo   Implement testFacet().
     */
    public function testFacet()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Builder::filterParamsUint
     * @todo   Implement testFilterParamsUint().
     */
    public function testFilterParamsUint()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

}
