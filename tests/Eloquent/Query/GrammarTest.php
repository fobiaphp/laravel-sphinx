<?php
namespace Fobia\Database\SphinxConnection\Test\Eloquent\Query;

use Fobia\Database\SphinxConnection\Eloquent\Query\Grammar;
use Fobia\Database\SphinxConnection\Test\TestCase;

class GrammarTest extends TestCase
{
    /**
     * @var Grammar
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
        $this->object = new Grammar();
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Grammar::wrap
     * @todo   Implement testWrap().
     */
    public function testWrap()
    {
        $m = new \ReflectionMethod(Grammar::class, 'wrap');
        $m->setAccessible(true);
        $this->assertEquals('column', $m->invoke($this->object, 'column'));

        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Grammar::wrapValue
     */
    public function testWrapValue()
    {
        $m = new \ReflectionMethod(Grammar::class, 'wrapValue');
        $m->setAccessible(true);
        $this->assertEquals('column', $m->invoke($this->object, 'column'));
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Grammar::wrapValue2
     */
    public function testWrapValue2()
    {
        $m = new \ReflectionMethod(Grammar::class, 'wrapValue2');
        $m->setAccessible(true);
        $this->assertEquals("'column'", $m->invoke($this->object, 'column'));
        $this->assertEquals("'column\''", $m->invoke($this->object, 'column\''));
        $this->assertEquals("'column\'\\\\'", $m->invoke($this->object, 'column\'\\'));
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Grammar::parameter
     */
    public function testParameter()
    {
        $m = new \ReflectionMethod(Grammar::class, 'parameter');
        $m->setAccessible(true);

        $this->assertEquals("'column'", $m->invoke($this->object, 'column'));
        $this->assertEquals(1, $m->invoke($this->object, 1));
        $this->assertEquals(1.1, $m->invoke($this->object, 1.1));
        $this->assertEquals('(1, 2, 3)', $m->invoke($this->object, [1, 2, 3]));
    }
}
