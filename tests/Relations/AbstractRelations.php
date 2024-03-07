<?php

namespace JuanchoSL\Orm\Tests\Relations;

use JuanchoSL\Orm\Collection;
use JuanchoSL\Orm\datamodel\Model;
use JuanchoSL\Orm\engine\Drivers\DbInterface;
use JuanchoSL\Orm\engine\Engines;
use JuanchoSL\Orm\engine\Structures\FieldDescription;
use JuanchoSL\Orm\Tests\ConnectionTrait;
use JuanchoSL\Orm\Tests\Other;
use JuanchoSL\Orm\Tests\TestDb;
use PHPUnit\Framework\TestCase;

abstract class AbstractRelations extends TestCase
{

    use ConnectionTrait;

    protected DbInterface $db;

    protected Engines $db_type;


    private $loops = 3;
    public function setUp(): void
    {
        $this->db = self::getConnection($this->db_type);
        Model::setConnection($this->db);
    }
    public function testCreate()
    {
        $this->markTestSkipped();
        $query_table = [
            (new FieldDescription)->setName('id')->setType('integer')->setLength(6)->setNullable(false)->setKey(true),
            (new FieldDescription)->setName('valor')->setType('varchar')->setLength(16)->setNullable(false),
            (new FieldDescription)->setName('test_id')->setType('varchar')->setLength(16)->setNullable(false),
        ];
        call_user_func_array([$this->db, 'createTable'], array_merge(['other'], $query_table));

        $this->assertTrue(true, "Create table");
    }

    public function testInsert()
    {
        for ($i = 1; $i <= $this->loops; $i++) {
            $id = TestDb::make(array('test' => 'valor', 'dato' => $i))->save();
            $this->assertTrue(!empty($id), "Recuperación del id de un insert");
        }
        for ($i = 1; $i <= $this->loops; $i++) {
            $fk = ($i % 2 == 0) ? 2 : 1;
            $id = Other::insert(array('valor' => "valor_{$i}", 'test_id' => $fk))->save();
            $this->assertTrue(!empty($id), "Recuperación del id de un insert");
        }
    }

    public function testChilds()
    {
        $obj = TestDb::findByPk(1);
        $this->assertInstanceOf(TestDb::class, $obj);

        $others = $obj->other;
        $this->assertInstanceOf(Collection::class, $others);
        $this->assertTrue($others->hasElements());
        $this->assertContainsOnlyInstancesOf(Other::class, $others);

        $count = $others->count();
        $other = $others->first();
        $this->assertEquals(1, $other->delete());

        $others = $obj->other;
        $new_count = $others->count();
        $this->assertLessThan($count, $new_count);
    }

    public function testsParent()
    {
        $obj = TestDb::findByPk(1);
        $this->assertInstanceOf(TestDb::class, $obj);

        $others = $obj->other;
        $this->assertInstanceOf(Collection::class, $others);
        $this->assertTrue($others->hasElements());
        $this->assertContainsOnlyInstancesOf(Other::class, $others);

        /*
        print_r($others);
        $parent = $others->first();
        print_r($parent->test);
        $parent = $others->first()->test;
        $this->assertInstanceOf(TestDb::class, $parent);
        $this->assertEquals($obj, $parent);
        */
    }

    public function testTruncate()
    {
        $success = Other::truncate();
        $this->assertEquals(true, $success, "Trucate table");
        $success = TestDb::truncate();
        $this->assertEquals(true, $success, "Trucate table");
    }
}

