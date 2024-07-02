<?php

namespace JuanchoSL\Orm\Tests\Functional;

use JuanchoSL\Orm\Collection;
use JuanchoSL\Orm\Datamodel\Model;
use JuanchoSL\Orm\engine\Responses\AlterResponse;
use JuanchoSL\Orm\engine\Structures\FieldDescription;
use JuanchoSL\Orm\querybuilder\QueryBuilder;
use JuanchoSL\Orm\querybuilder\Types\CreateQueryBuilder;
use JuanchoSL\Orm\Tests\ConnectionTrait;
use JuanchoSL\Orm\Tests\Other;
use JuanchoSL\Orm\Tests\TestDb;
use PHPUnit\Framework\TestCase;

class RelationsTest extends TestCase
{

    use ConnectionTrait;

    private $loops = 5;

    /**
     * @dataProvider providerData
     */
    public function testCreate($db)
    {
        $this->markTestSkipped();
        /*
        */
        $query_table = [
            (new FieldDescription)->setName('id')->setType('integer')->setLength(6)->setNullable(false)->setKey(true),
            (new FieldDescription)->setName('valor')->setType('varchar')->setLength(16)->setNullable(false),
            (new FieldDescription)->setName('test_id')->setType('varchar')->setLength(6)->setNullable(false),
        ];
        $b = QueryBuilder::getInstance()->create(...$query_table)->table(Other::getInstance()->getTableName());
        $db->execute($b);
        $this->assertTrue(true, "Create table");
    }


    /**
     * @dataProvider providerData
     */
    public function testInsert($db)
    {
        Model::setConnection($db);
        for ($i = 1; $i <= $this->loops; $i++) {
            $test = TestDb::make(array('test' => 'valor', 'dato' => $i));
            $id = $test->save();
            $this->assertTrue(!empty($id), "Recuperación del id de un insert");
        }
        for ($i = 1; $i <= $this->loops; $i++) {
            $fk = ($i % 2 == 0) ? 2 : 1;

            $id = Other::make(array('valor' => "valor_{$i}", 'test_id' => $fk))->save();
            $this->assertTrue(!empty($id), "Recuperación del id de un insert");
        }
    }

    /**
     * @dataProvider providerData
     */
    public function testChilds($db)
    {
        Model::setConnection($db);
        $obj = TestDb::findByPk(1);
        $this->assertInstanceOf(TestDb::class, $obj);

        $others = $obj->other;
        $this->assertInstanceOf(Collection::class, $others);
        $this->assertTrue($others->hasElements());
        $this->assertContainsOnlyInstancesOf(Other::class, $others);


    }

    /**
     * @dataProvider providerData
     */
    public function testChildsLimited($db)
    {
        Model::setConnection($db);
        $obj = TestDb::findByPk(1);
        $this->assertInstanceOf(TestDb::class, $obj);
        for ($i = 1; $i <= 3; $i++) {
            $others = $obj->other()->limit($i);
            $new_count = $others->count();
            $this->assertEquals($i, $new_count);

            $others = $others->get();
            $new_count = $others->count();
            $this->assertEquals($i, $new_count);
            $this->assertInstanceOf(Collection::class, $others);
            $this->assertTrue($others->hasElements());
            $this->assertContainsOnlyInstancesOf(Other::class, $others);
        }

    }

    /**
     * @dataProvider providerData
     */
    public function testChildsUpdate($db)
    {
        Model::setConnection($db);
        $obj = TestDb::findByPk(1);
        $this->assertInstanceOf(TestDb::class, $obj);
        $new_count = $obj->other->count();
        $updated = $obj->other()->update(['valor' => 'texto']);
        $this->assertInstanceOf(AlterResponse::class, $updated);
        $this->assertEquals($new_count, $updated->count());

        $counter = Other::where(['valor', 'texto'])->count();
        $this->assertEquals($counter, $updated->count());
        $this->assertLessThan($this->loops, $counter);

        $counter = Other::where(['valor', 'texto'], ['test_id', 1])->count();
        $this->assertEquals($counter, $new_count);
    }

    /**
     * @dataProvider providerData
     */
    public function testsParent($db)
    {
        Model::setConnection($db);
        $obj = TestDb::findByPk(1);
        $this->assertInstanceOf(TestDb::class, $obj);

        $others = $obj->other;
        $this->assertInstanceOf(Collection::class, $others);
        $this->assertTrue($others->hasElements());
        $this->assertContainsOnlyInstancesOf(Other::class, $others);

        $parent = $others->first()->test;
        $this->assertInstanceOf(TestDb::class, $parent);
        $this->assertEquals($obj->id, $parent->id);
    }

    /**
     * @dataProvider providerData
     */
    public function testDeleteChild($db)
    {
        Model::setConnection($db);
        $obj = TestDb::findByPk(1);
        $this->assertInstanceOf(TestDb::class, $obj);

        $others = $obj->other;
        $count = $others->count();
        $other = $others->first();
        $this->assertEquals(1, $other->delete());

        $others = $obj->other;
        $new_count = $others->count();
        $this->assertLessThan($count, $new_count);
    }

    /**
     * @dataProvider providerData
     */
    public function testDeleteChilds($db)
    {
        Model::setConnection($db);
        $obj = TestDb::findByPk(1);
        $this->assertInstanceOf(TestDb::class, $obj);

        $others = $obj->other;
        $this->assertInstanceOf(Collection::class, $others);
        $this->assertTrue($others->hasElements());
        $old_count = $others->count();

        $deleted = $obj->other()->delete();
        $this->assertInstanceOf(AlterResponse::class, $deleted);
        $this->assertEquals($old_count, $deleted->count());

        $others = $obj->other;
        $this->assertInstanceOf(Collection::class, $others);
        $this->assertFalse($others->hasElements());
        $new_count = $others->count();
        $this->assertLessThan($old_count, $new_count);
    }

    /**
     * @dataProvider providerData
     */
    public function testTruncate($db)
    {
        Model::setConnection($db);
        $success = Other::truncate();
        $this->assertEquals(1, $success->count(), "Truncate table");
        $success = TestDb::truncate();
        $this->assertEquals(1, $success->count(), "Truncate table");
    }

    /**
     * @dataProvider providerData
     */
    public function testDrop($db)
    {
        $this->markTestSkipped();
        $builder = QueryBuilder::getInstance()->drop()->table(Other::getInstance()->getTableName());
        $success = $db->execute($builder);
        $this->assertEquals(1, $success->count(), "Drop table");
    }

    /**
     * @dataProvider providerData
     */
    public function testDisconnect($db)
    {
        $result = $db->disconnect();
        $this->assertTrue($result, "Test disconnect");
    }


}

