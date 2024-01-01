<?php

namespace JuanchoSL\Orm\Tests\Relations;

use JuanchoSL\Orm\Collection;
use JuanchoSL\Orm\engine\Structures\FieldDescription;
use JuanchoSL\Orm\Tests\Other;
use JuanchoSL\Orm\Tests\TestDb;
use PHPUnit\Framework\TestCase;

abstract class AbstractRelationsTest extends TestCase
{

    protected $db;

    private $loops = 3;

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
    
    public function testsParent(){
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
    }
}

