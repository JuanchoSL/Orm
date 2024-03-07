<?php

namespace JuanchoSL\Orm\Tests\Integration;

use JuanchoSL\Orm\Collection;
use JuanchoSL\Orm\datamodel\CachedModel;
use JuanchoSL\Orm\datamodel\Model;
use JuanchoSL\Orm\engine\Drivers\DbInterface;
use JuanchoSL\Orm\engine\Engines;
use JuanchoSL\Orm\Tests\ConnectionTrait;
use JuanchoSL\Orm\Tests\TestDb;
use JuanchoSL\SimpleCache\Adapters\SimpleCacheAdapter;
use JuanchoSL\SimpleCache\Repositories\ProcessCache;
use PHPUnit\Framework\TestCase;

abstract class AbstractFunctional extends TestCase
{

    use ConnectionTrait;

    protected DbInterface $db;

    protected Engines $db_type;

    private $loops = 3;

    public function setUp(): void
    {
        $this->db = self::getConnection($this->db_type);
        Model::setConnection($this->db);
        //CachedModel::setConnection($this->db);
        //CachedModel::setCache(new ProcessCache('Orm' . (string) $this->db_type->string()));
    }
    public function testInsert()
    {
        for ($i = 1; $i <= $this->loops; $i++) {
            $id = TestDb::make(array('test' => 'valor', 'dato' => $i))->save();
            $this->assertTrue(!empty($id), "Recuperación del id de un insert");
        }
    }

    public function testConditions()
    {
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

    public function testSelect()
    {
        for ($i = 1; $i <= $this->loops; $i++) {
            $cursor = TestDb::where(array('test', 'valor'))->limit($i);
            $values = $cursor->get();
            $this->assertInstanceOf(Collection::class, $values);
            $this->assertEquals($i, $values->count());
        }
    }

    public function testSelectPaginated()
    {
        $query = TestDb::where(array('test', 'valor'));
        $this->assertEquals($this->loops, $query->count());

        $i = $this->loops - 1;
        $query = $query->limit($i); //->cursor();
        $this->assertEquals($i, $query->count());

        $values = $query->get();
        $this->assertInstanceOf(Collection::class, $values);
        $this->assertEquals($i, $values->count());
    }

    public function testRestart()
    {
        $deleted = TestDb::where()->delete();
        $this->assertEquals($this->loops, $deleted);
        /*
        $remover = TestDb::find(array());
        foreach ($remover as $remo) {
            $remo->remove();
        }
        */
        // $this->testTruncate();
    }

    public function testSaveInsert()
    {
        for ($i = 1; $i <= $this->loops; $i++) {
            $obj = new TestDb();
            $obj->test = 'valores';
            $obj->dato = $i;
            $id = $obj->save();
            $this->assertTrue(!empty($id), "Recuperación del id de un insert");
        }
    }

    public function testSaveUpdate()
    {
        $objs = TestDb::all();
        $this->assertInstanceOf(Collection::class, $objs);
        $this->assertTrue($objs->hasElements(), "Find return elements");
        foreach ($objs as $obj) {
            $this->assertEquals('valores', $obj->test, "Comprobación del valor original");
            $obj->test = 'valor';
            $n = $obj->save();
            $this->assertEquals(1, $n, "Recuperación del número de elementos modificados con update");
            $key = $obj->getPrimaryKeyName();
            $id = $obj->$key;
            $obj2 = TestDb::where([$key, $id])->limit(1)->first();
            $this->assertEquals('valor', $obj2->test, "Comprobación del valor original");
        }
    }

    public function testSelectByPk()
    {
        $elements = TestDb::all();
        $this->assertInstanceOf(Collection::class, $elements);
        $this->assertTrue($elements->hasElements(), "Find return elements");
        foreach ($elements as $element) {
            $obj = TestDb::findByPk($element->id);
            $this->assertInstanceOf(TestDb::class, $obj);
            //$this->assertEquals($element, $obj);
        }
    }

    public function testSelectFindPaginated()
    {
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
                if (!in_array($this->db->typeDB, [DatabaseFactory::TYPE_MONGOCLIENT, DatabaseFactory::TYPE_MONGO])) {
                    foreach ($obj->columns() as $column) {
                        $this->assertObjectHasProperty($column, $obj); //PHPUnit 6
                    }
                }
                */
            }
        }
    }

    public function testUpdate()
    {
        $modificateds = TestDb::where(array('test', 'valor'))->update(array('test' => 'value')); //->count();
        $this->assertEquals($this->loops, $modificateds, "Update elements");
    }

    public function testSerialize()
    {
        $var = TestDb::all();
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

    public function testDelete()
    {
        $remover = TestDb::where(array('test', 'value'))->get();
        $this->assertEquals($this->loops, $remover->count(), "delete {$remover->count()} results");
        foreach ($remover as $remo) {
            $remo->delete();
        }
        $remover = TestDb::where(array('test', 'value'))->get();
        $this->assertEquals(0, $remover->count(), "No elements for delete after remove");
        $this->assertFalse($remover->hasElements(), "No elements for delete after remove");
        //        $removeds = $this->db->delete(array('test' => 'value'));
//        $this->assertEquals($this->loops, $removeds, "delete {$removeds} results");
    }

    public function testTruncate()
    {
        $success = TestDb::truncate();
        $this->assertEquals(true, $success, "Trucate table");
    }

    public function testDisconnect()
    {
        $result = $this->db->disconnect();
        $this->assertTrue($result, "Test disconnect");
    }

}

