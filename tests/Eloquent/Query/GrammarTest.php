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
    public function setUp(): void
    {
        parent::setUp();
        $this->object = new Grammar();
    }

    /**
     * @covers \Fobia\Database\SphinxConnection\Eloquent\Query\Grammar::wrap
     */
    public function testWrap()
    {
        $m = new \ReflectionMethod(Grammar::class, 'wrap');
        $m->setAccessible(true);
        $wrap = function ($value, $prefixAlias = false) use ($m) {
            return $m->invokeArgs($this->object, [$value, $prefixAlias]);
        };

        $this->assertEquals('col', $wrap('col'));
        // TODO: please fixme
        $this->assertEquals('col2', $wrap('col1.col2'));
        $this->assertEquals('col2.col3', $wrap('col1.col2.col3'));

        // TODO: please fixme
        $this->assertEquals('col2[0]', $wrap('col1.col2[0]'));
        $this->assertEquals('col2[0].col3', $wrap('col1.col2[0].col3'));
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
