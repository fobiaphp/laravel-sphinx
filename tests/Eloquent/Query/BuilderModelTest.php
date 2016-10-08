<?php
/**
 * ATest.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection\Test\Eloquent\Query;

use Fobia\Database\SphinxConnection\Test\ModelRt;
use Illuminate\Database\Eloquent\Collection;


/**
 * Class ATest
 *
 * @package    Fobia\Database\SphinxConnection\Test\Eloquent\Query
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */
class BuilderModelTest extends BuilderTest
{
    public function makeQ()
    {
        return new ModelRt();
    }

    /**
     * @todo   Implement testReplace().
     */
    public function testReplace()
    {
        $this->markTestIncomplete("asd");
    }


    public function testSave()
    {
        $this->markTestIncomplete("testSave");
        return ;
        $this->q->insert([
            'id' => 1,
            'gid' => 1,
            'tags' => $this->q->getConnection()->raw('(1)'),
            'name' => 'new name',
        ]);

        $model = ModelRt::find(1);
        $model->gid = 10;
        $model->save();

        $model = ModelRt::find(1);
        $this->assertEquals(10, (int) $model->gid);

        $model->tags = [ 10 ];
        $model->save();

        $model = ModelRt::find(1);
        $this->assertEquals([10], $model->tags);
    }

    public function testFind()
    {
        $this->q->insert([
            'id' => 1,
            'name' => 'new name',
        ]);

        $model = ModelRt::find(1);
        $this->assertInstanceOf(ModelRt::class, $model);
        $this->assertEquals('new name', $model->name);

        $this->expectException(\Exception::class);
        ModelRt::findOrFail(100);
    }

    public function testWhere()
    {
        $this->seedRtTable();

        $result = $this->q->where('id', '>', 0)->get();
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertInstanceOf(ModelRt::class, $result->first());
    }

}
