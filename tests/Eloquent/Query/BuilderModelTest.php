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
        $this->markTestIncomplete("asd");
        $this->q->insert([
            'id' => 1,
            'name' => 'new name',
        ]);

        //$model = ModelRt::find(1);
        //dump($model->name);
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

    /**
     * @return array
     */
    public function testWhere()
    {
        $this->q->insert([
            [ 'id' => 1, 'name' => 'name 1',],
            [ 'id' => 2, 'name' => 'name 2',],
            [ 'id' => 3, 'name' => 'name 3',],
            [ 'id' => 4, 'name' => 'name 4',],
            ]
        );
        $result = $this->q->where('id', '>', 0)->get();

        $this->assertInstanceOf(Collection::class, $result);

        $this->assertInstanceOf(ModelRt::class, $result->first());
    }

}
