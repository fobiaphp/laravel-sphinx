<?php
/**
 * ATest.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection\Test\Eloquent\Query;

use Fobia\Database\SphinxConnection\Test\ModelRt;


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

}
