<?php
/**
 * SphinxConnectionTest.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2020 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection\Test;

use Fobia\Database\SphinxConnection\SphinxConnection;
use Fobia\Database\SphinxConnection\SphinxQLDriverConnection;
use Foolz\SphinxQL\Helper;

class SphinxConnectionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    /**
     * Singleton конектор к Sphinx различными способами
     */
    public function test_Connection()
    {
        $this->assertInstanceOf(SphinxConnection::class, $this->db);

        $db = \DB::connection('sphinx');
        $this->assertInstanceOf(SphinxConnection::class, $db);
    }

    public function test_getSphinxQLDriversConnection()
    {
        $sphinxQl = $this->db->getSphinxQLDriversConnection();
        $this->assertInstanceOf(SphinxQLDriverConnection::class, $sphinxQl);
    }

    public function test_getSphinxQLDriversConnection_singleton()
    {
        $sphinxQl1 = $this->db->getSphinxQLDriversConnection();
        $sphinxQl2 = $this->db->getSphinxQLDriversConnection();

        $this->assertEquals($sphinxQl1, $sphinxQl2);
        $this->assertTrue($sphinxQl1 === $sphinxQl2);
    }

    public function test_getSphinxQLHelper()
    {
        $helper = $this->db->getSphinxQLHelper();
        $this->assertInstanceOf(Helper::class, $helper);
    }
}
