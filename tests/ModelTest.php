<?php
/**
 * ModelTest.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection\Test;


use Foolz\SphinxQL\Match;

class ModelTest extends TestCase
{
    public function test_match()
    {
        $q = Model::match(['id'], 'art');
        $q = $q->match(['id'], 'art');
        
        $q->matchQl(function(Match $m) {
            $m->not('a');
            $m->phrase('phr');
        });
        dump($q->toSql());
    }
}
