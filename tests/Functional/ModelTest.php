<?php

namespace JuanchoSL\Orm\Tests\Functional;

use JuanchoSL\Orm\Collection;
use JuanchoSL\Orm\datamodel\Model;
use JuanchoSL\Orm\engine\Drivers\DbInterface;
use JuanchoSL\Orm\engine\Engines;
use JuanchoSL\Orm\Tests\ConnectionTrait;
use JuanchoSL\Orm\Tests\TestDb;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{

    use ConnectionTrait;

    private $loops = 3;


    /**
     * @dataProvider providerData
     */
    public function testInsert($db)
    {
        Model::setConnection($db);
        for ($i = 1; $i <= $this->loops; $i++) {
            $obj = TestDb::make(array('test' => 'valor', 'dato' => $i));
            $this->assertInstanceOf(TestDb::class, $obj);
            $saved = $obj->save();
            $this->assertTrue($saved, "Recuperación del id de un insert");
            $this->assertEquals($i, $obj->getPrimaryKeyValue(), "Recuperación del id de un insert");
        }
    }
    
    /**
     * @dataProvider providerData
     */
    public function testConditions($db)
    {
        Model::setConnection($db);
        $cursor = TestDb::where(['test', 'valor'], ['dato', 2]);
        $this->assertEquals(1, $cursor->count(), "Check 1");
        
        $cursor = TestDb::where(['test', 'valor'])->where(['dato', 2]);
        $this->assertEquals(1, $cursor->count(), "Check 2");

        $cursor = TestDb::where(['test', 'valor'])->orWhere(['dato', 2]);
        $this->assertEquals($this->loops, $cursor->count(), "Check 3");
        
        $cursor = TestDb::where(['test', ['valor', 'valore']]);
        $this->assertEquals($this->loops, $cursor->count(), "Check 4");

        $cursor = TestDb::where(['test="valor"'], ['dato=2']);
        $this->assertEquals(1, $cursor->count(), "Check 5");
        
        $cursor = TestDb::where(['test="valor"'])->where(['dato=2']);
        $this->assertEquals(1, $cursor->count(), "Check 6");

        $cursor = TestDb::where(['test="valor"'])->orWhere(['dato=2']);
        $this->assertEquals($this->loops, $cursor->count(), "Check 7");

        $cursor = TestDb::where(['test="valor"'], ['test<>"otro"']);
        $this->assertEquals($this->loops, $cursor->count(), "Check 8");

        $cursor = TestDb::where(['test="valor"'])->orWhere(['test="valore"']);
        $this->assertEquals($this->loops, $cursor->count(), "Check 9");

        $cursor = TestDb::where(['dato', [2, 3], true]);
        $this->assertEquals($this->loops - 1, $cursor->count(), "Check 10");

        $cursor = TestDb::where(['dato', [1], false]);
        $this->assertEquals($this->loops - 1, $cursor->count(), "Check 11");

        $cursor = TestDb::where(['dato', 1, '>']);
        $this->assertEquals($this->loops - 1, $cursor->count(), "Check 12");

        $cursor = TestDb::where(['dato', 2, '>=']);
        $this->assertEquals($this->loops - 1, $cursor->count(), "Check 13");

        $cursor = TestDb::where(['dato', null, 'IS NOT NULL']);
        $this->assertEquals($this->loops, $cursor->count(), "Check 14");

        $cursor = TestDb::where(['dato', null, 'IS NULL']);
        $this->assertEquals(0, $cursor->count(), "Check 15");
        
    }
    
    /**
     * @dataProvider providerData
     */
    public function testSelect($db)
    {
        Model::setConnection($db);
        for ($i = 1; $i <= $this->loops; $i++) {
            $cursor = TestDb::where(array('test', 'valor'))->limit($i);
            $values = $cursor->get();
            $this->assertInstanceOf(Collection::class, $values);
            $this->assertEquals($i, $values->count());
        }
    }
    
    /**
     * @dataProvider providerData
     */
    public function testSelectPaginated($db)
    {
        Model::setConnection($db);
        $query = TestDb::where(array('test', 'valor'));
        $this->assertEquals($this->loops, $query->count());
        
        $i = $this->loops - 1;
        $query = $query->limit($i); //->cursor();
        $this->assertEquals($i, $query->count());

        $values = $query->get();
        $this->assertInstanceOf(Collection::class, $values);
        $this->assertEquals($i, $values->count());
    }
    
    /**
     * @dataProvider providerData
     */
    public function testRestart($db)
    {
        Model::setConnection($db);
        $deleted = TestDb::where()->delete();
        $this->assertEquals($this->loops, $deleted->count());
        /*
        $remover = TestDb::find(array());
        foreach ($remover as $remo) {
            $remo->remove();
        }
        */
        // $this->testTruncate();
    }

    /**
     * @dataProvider providerData
     */
    public function testSaveInsert($db)
    {
        Model::setConnection($db);
        for ($i = 1; $i <= $this->loops; $i++) {
            $obj = new TestDb();
            $obj->test = 'valores';
            $obj->dato = $i;
            $id = $obj->save();
            $this->assertTrue($id, "Recuperación del id de un insert");
        }
    }

    /**
     * @dataProvider providerData
     */
    public function testSaveUpdate($db)
    {
        Model::setConnection($db);
        $objs = TestDb::get();
        $this->assertInstanceOf(Collection::class, $objs);
        $this->assertTrue($objs->hasElements(), "Find return elements");
        foreach ($objs as $obj) {
            $this->assertEquals('valores', $obj->test, "Comprobación del valor original");
            $obj->test = 'valor';
            $n = $obj->save();
            $this->assertTrue($n, "Recuperación del número de elementos modificados con update");
            $key = $obj->getPrimaryKeyName();
            $id = $obj->$key;
            $obj2 = TestDb::where([$key, $id])->limit(1)->first();
            $this->assertEquals('valor', $obj2->test, "Comprobación del valor original");
        }
    }

    /**
     * @dataProvider providerData
     */
    public function testSelectByPk($db)
    {
        Model::setConnection($db);
        $elements = TestDb::get();
        $this->assertInstanceOf(Collection::class, $elements);
        $this->assertTrue($elements->hasElements(), "Find return elements");
        foreach ($elements as $element) {
            //$obj = TestDb::findByPk($element->id);
            //$this->assertInstanceOf(TestDb::class, $obj);
            
            $this->assertInstanceOf(TestDb::class, $element);
            //$this->assertEquals($element, $obj);
        }
    }

    /**
     * @dataProvider providerData
     */
    public function testSelectFindPaginated($db)
    {
        Model::setConnection($db);
        for ($i = 1; $i <= $this->loops; $i++) {
            $objs = TestDb::where(array('test', 'valor'))->limit($i, 0)->get();
            $this->assertInstanceOf(Collection::class, $objs);
            $this->assertTrue($objs->hasElements());
            $this->assertContainsOnlyInstancesOf(TestDb::class, $objs);
            $this->assertEquals($i, $objs->count());
            foreach ($objs as $obj) {
                $this->assertInstanceOf(TestDb::class, $obj);
                //$this->assertTrue($obj->loaded);
                /*
                if (!in_array($db->typeDB, [DatabaseFactory::TYPE_MONGOCLIENT, DatabaseFactory::TYPE_MONGO])) {
                    foreach ($obj->columns() as $column) {
                        $this->assertObjectHasProperty($column, $obj); //PHPUnit 6
                    }
                }
                */
            }
        }
    }

    /**
     * @dataProvider providerData
     */
    public function testUpdate($db)
    {
        Model::setConnection($db);
        $modificateds = TestDb::where(array('test', 'valor'))->update(array('test' => 'value')); //->count();
        $this->assertEquals($this->loops, $modificateds->count(), "Update elements");
    }

    /**
     * @dataProvider providerData
     */
    public function testSerialize($db)
    {
        Model::setConnection($db);
        $var = TestDb::get();
        $this->assertTrue($var->hasElements(), "Collection have elements");
        $this->assertContainsOnlyInstancesOf(TestDb::class, $var, "Collection are a few of Test class");
        $var->rewind();
        $current = $var->current();
        $this->assertInstanceOf(TestDb::class, $current, "Class are an instance os Test");
        $current->id;
        $serialized = serialize($current);
        $unserialized = unserialize($serialized);
        $this->assertEquals($current, $unserialized);
        $unserialized->test = 'nuevo';
        $unserialized->save();
        //$this->assertEquals(TestDb::findByPk($unserialized->getPrimaryKeyValue()), $unserialized);
        $unserialized->test = 'value';
        $unserialized->save();
    }

    /**
     * @dataProvider providerData
     */
    public function testDelete($db)
    {
        Model::setConnection($db);
        $remover = TestDb::where(array('test', 'value'))->get();
        $this->assertEquals($this->loops, $remover->count(), "delete {$remover->count()} results");
        foreach ($remover as $remo) {
            $remo->delete();
        }
        $remover = TestDb::where(array('test', 'value'))->get();
        $this->assertEquals(0, $remover->count(), "No elements for delete after remove");
        $this->assertFalse($remover->hasElements(), "No elements for delete after remove");
        //        $removeds = $db->delete(array('test' => 'value'));
//        $this->assertEquals($this->loops, $removeds, "delete {$removeds} results");
    }
    
    /**
     * @dataProvider providerData
     */
    public function testTruncate($db)
    {
        Model::setConnection($db);
        $this->testSaveInsert($db);
        $success = TestDb::truncate();
        $this->assertEquals(1, $success->count(), "Truncate table");
    }
    
    /**
     * @dataProvider providerData
     */
    public function testDisconnect($db)
    {
        Model::setConnection($db);
        $result = $db->disconnect();
        $this->assertTrue($result, "Test disconnect");
    }

}

