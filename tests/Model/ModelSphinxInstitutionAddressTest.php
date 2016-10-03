<?php
/**
 * ModelSphinxInstitutionAddressTest.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection\Test\Model;

use Fobia\Database\SphinxConnection\Test\TestCase;

class ModelSphinxInstitutionAddressTest extends TestCase
{
    public function test_model()
    {
        $q = ModelSphinxInstitutionAddress::withinGroupOrderBy('id');
        $q = $q->options('a', 't');
        $q = $q->options('a', 't1');
        dump($q->toSql());
        //$this->assertQuery('select * from institution_address WITHIN GROUP ORDER BY id ASC', $q->toSql());
    }
}
