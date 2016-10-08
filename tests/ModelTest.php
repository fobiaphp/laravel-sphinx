<?php
/**
 * ModelTest.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection\Test;

use Foolz\SphinxQL\Facet;
use Foolz\SphinxQL\Match;

class ModelTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->setUpDatabase($this->app);
    }
    // =============================================


    public function test_select()
    {
        $q = ProductModel::select('id');
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
        $q = ProductModel::select('id');
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
        $q = ProductModel::select('id');
        $q = $q->withinGroupOrderBy('name', 'a');
    }

    public function test_delete()
    {
        $q = ProductModel::where('id', 999999)->delete();
        $this->assertInternalType('int', $q);
    }

    public function test_insert()
    {
        $id = 999999;
        ProductModel::where('id', $id)->delete();
        $q = ProductModel::insert([
            'id' => '999999',
            'name' => 'new \\\\\\\'name',
        ]);
        $this->assertTrue($q);

        $q = ProductModel::where('id', $id)->delete();
        $this->assertEquals(1, $q);
    }

    public function test_update()
    {
        $id = 999999;
        ProductModel::where('id', $id)->delete();
        $q = ProductModel::insert([
            'id' => '999999',
            'name' => '\\\\\\\'name',
            'menu_id' => 1,
        ]);
        $this->assertTrue($q);

        $this->assertEquals(1, $q);

        $q = ProductModel::where('id', $id)->update([
            'menu_id' => 3,
        ]);
        $this->assertEquals(1, $q);

        ProductModel::where('id', $id)->delete();
    }

    /**
     * @expectedException \Illuminate\Database\QueryException
     */
    public function test_update_exeption()
    {
        $id = 999999;
        ProductModel::where('id', $id)->delete();
        $q = ProductModel::where('id', $id)->update([
            'menu_id' => '2',
        ]);
    }

    public function test_scopeOptions()
    {
        $q = ProductModel::options('ranker', 'bm25');
        $this->assertQuery('select * from products OPTION ranker = bm25', $q->toSql());

        $q->options('max_matches', '3000');
        $this->assertQuery('select * from products OPTION ranker = bm25,max_matches=3000', $q->toSql());

        $q->options('field_weights', '(title=10, body=3)');
        $this->assertQuery('select * from products OPTION ranker = bm25,max_matches=3000,
            field_weights=(title=10, body=3)', $q->toSql());

        $q->options('agent_query_timeout', '10000');
        $this->assertQuery('select * from products OPTION ranker = bm25,max_matches=3000,
            field_weights=(title=10, body=3) , agent_query_timeout=10000', $q->toSql());
        $q->get();
    }

    public function test_scopeOptions2()
    {
        $q = ProductModel::options('field_weights', ['title' => 10, 'body' => 3]);
        $this->assertQuery('select * from products OPTION field_weights=(title=10, body=3)', $q->toSql());

        $q->options('comment', 'my comment');
        $this->assertQuery('select * from products OPTION field_weights=(title=10, body=3), comment=\'my comment\'',
            $q->toSql());
    }

    public function test_where()
    {
        $q = ProductModel::where('id', 999999);
        $this->assertQuery("select * FROM products WHERE id = 999999", $q);
    }


    public function test_match()
    {
        $q = ProductModel::where('id', 999999)->match(function ($m) {
            $m->field('name');
            $m->phrase('phrase');
        });
        $this->assertQuery("select * FROM products WHERE MATCH('(@name \\\"phrase\\\")') AND id = 999999", $q);
    }

    public function test_match_column()
    {
        $q = ProductModel::where('id', 999999)->match(['id'], 'art');
        $this->assertQuery("select * FROM products WHERE MATCH('(@(id) art)') AND id = 999999", $q);

        $q = $q->match(['name'], 'sName');
        $this->assertQuery("select * FROM products WHERE MATCH('(@(id) art) (@(name) sname)') AND id = 999999", $q);
    }

    public function test_matchQl_0()
    {
        $q = ProductModel::where('id', 999999)->match(function ($m) {
            $m->field('name');
            $m->phrase('phrase');
        });

        $this->assertQuery("select * FROM products WHERE MATCH('(@name \\\"phrase\\\")') AND  id = 999999", $q);
    }

    public function test_matchQl_1()
    {
        $q = ProductModel::where('id', 999999)->match(function (Match $m) {
            $m->field('name');
            $m->phrase('phrase');
        });

        $this->assertQuery("select * FROM products WHERE MATCH('(@name \\\"phrase\\\" )') AND  id = 999999", $q);
    }

    public function test_cast_model()
    {
        ProductModel::insert([
            'id' => '999999',
            'name' => 'new name',
            'tags' => $this->db->raw('(1,2,3,4)'),
        ]);

        $model = ProductModel::whereMulti('tags', 'in', [1, 2, 3])->first();
        if (!$model) {
            $this->markTestSkipped('not found test row');
            return;
        }

        $this->assertArrayHasKey(0, $model->tags);

        ProductModel::where('id', '999999')->delete();
    }

    public function test_cast()
    {
        $model = new ProductModel();
        $model->tags = [1, 2, 3];
        $this->assertEquals([1, 2, 3], $model->tags);

        $model->tags = '1, 2, 3';
        $this->assertEquals([1, 2, 3], $model->tags);

        $model->tags = '(1,2,3)';
        $this->assertEquals([1, 2, 3], $model->tags);
    }

    public function test_mvaType()
    {
        ProductModel::where('id', 999999)->delete();
        ProductModel::insert([
            'id' => '999999',
            'name' => 'new name',
            'tags' => $this->db->raw('(1,2,3,4)'),
        ]);

        $q = ProductModel::where('id', 999999);
        $r = $q->update(['tags' => [3]]);
        $this->assertEquals(1, $r);
    }
}
