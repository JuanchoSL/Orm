<?php

namespace JuanchoSL\Orm\Tests\Unit;

use JuanchoSL\Orm\engine\Drivers\DbInterface;
use JuanchoSL\Orm\querybuilder\QueryBuilder;
use JuanchoSL\Orm\engine\Engines;
use JuanchoSL\Orm\Tests\ConnectionTrait;
use PHPUnit\Framework\TestCase;

abstract class AbstractUnitTest extends TestCase
{
    use ConnectionTrait;

    protected DbInterface $db;

    protected $db_type;

    private $loops = 3;

    private $table = 'test';

    public function setUp(): void
    {
        $this->db = self::getConnection($this->db_type);
    }

    public function tearDown(): void
    {
        //$this->db->disconnect();
    }

    public function testCreate()
    {
        $this->markTestSkipped();
        $query_table = $this->queryCreateTable();
        if (is_string($query_table)) {
            $this->db->execute($query_table);
        } else {
            call_user_func_array([$this->db, 'createTable'], array_merge([$this->table], $query_table));
        }

        $this->assertTrue(true, "Create table");
    }

    public function testInsert()
    {
        $this->db->setTable($this->table);
        for ($i = 1; $i <= $this->loops; $i++) {
            $id = $this->db->insert(array('test' => 'valor', 'dato' => $i));
            $this->assertTrue(!empty($id), "Recuperación del id de un insert");
            $this->assertIsNumeric($id, "ID is numeric");
            $this->assertEquals($i, $id, "ID equals than loop");
        }
    }

    public function testConditions()
    {
        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test', 'valor'], ['dato', 2]);
        $cursor = $this->db->execute($builder);
        $this->assertEquals(1, $cursor->count(), "Check 1");

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test', 'valor'])->where(['dato', 2]);
        $cursor = $this->db->execute($builder);
        $this->assertEquals(1, $cursor->count(), "Check 2");

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test', 'valor'])->orWhere(['dato', 2]);
        $cursor = $this->db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Check 3");

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test', ['valor', 'valore']]);
        $cursor = $this->db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Check 4");

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test="valor"'], ['dato=2']);
        $cursor = $this->db->execute($builder);
        $this->assertEquals(1, $cursor->count(), "Check 5");

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test="valor"'])->where(['dato=2']);
        $cursor = $this->db->execute($builder);
        $this->assertEquals(1, $cursor->count(), "Check 6");

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test="valor"'])->orWhere(['dato=2']);
        $cursor = $this->db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Check 7");

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test="valor"'], ['test<>"otro"']);
        $cursor = $this->db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Check 8");

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test="valor"'])->orWhere(['test="valore"']);
        $cursor = $this->db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Check 9");

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', [2, 3], true]);
        $cursor = $this->db->execute($builder);
        $this->assertEquals($this->loops - 1, $cursor->count(), "Check 10");

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', [1], false]);
        $cursor = $this->db->execute($builder);
        $this->assertEquals($this->loops - 1, $cursor->count(), "Check 11");

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', 1, '>']);
        $cursor = $this->db->execute($builder);
        $this->assertEquals($this->loops - 1, $cursor->count(), "Check 12");

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', 2, '>=']);
        $cursor = $this->db->execute($builder);
        $this->assertEquals($this->loops - 1, $cursor->count(), "Check 13");

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', null, 'IS NOT NULL']);
        $cursor = $this->db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Check 14");

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', null, 'IS NULL']);
        $cursor = $this->db->execute($builder);
        $this->assertEquals(0, $cursor->count(), "Check 15");

    }

    public function testSelect()
    {
        for ($i = 1; $i <= $this->loops; $i++) {
            $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(array('test', 'valor'))->limit($i);
            $cursor = $this->db->execute($builder);
            $values = $cursor->get();
            $this->assertTrue(is_array($values));
            $this->assertEquals($i, count($values));
            $this->assertTrue(isset($values[$i - 1]));
            $this->assertTrue($values[$i - 1] instanceof \stdClass);
            $id = (in_array($this->db_type, [Engines::TYPE_MONGOCLIENT, Engines::TYPE_MONGO])) ? (string) $values[$i - 1]->_id : $values[$i - 1]->{current($this->db->getKeys())};
            $this->assertTrue(!empty($id), "Recuperación del id de un select");
        }
    }

    public function testSelectPaginated()
    {
        $i = $this->loops - 1;
        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(array('test', 'valor'))->limit($i);
        $cursor = $this->db->execute($builder);
        $this->assertEquals($i, $cursor->count(), "Count counter");
        $values = $cursor->get();
        //print_r($cursor);
        $this->assertTrue(is_array($values));
        $this->assertEquals($i, count($values), "Count function");
        $builder = QueryBuilder::getInstance()->clear()->select()->from($this->table)->where(array('test', 'valor'));
        $cursor = $this->db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Count counter 2");
        $this->assertTrue(isset($values[$i - 1]));
        $this->assertTrue($values[$i - 1] instanceof \stdClass);
        $id = (in_array($this->db_type, [Engines::TYPE_MONGOCLIENT, Engines::TYPE_MONGO])) ? (string) $values[$i - 1]->_id : $values[$i - 1]->{current($this->db->getKeys())};
        $this->assertTrue(!empty($id), "Recuperación del id de un select");
    }
    /*
        public function testRestart()
        {
            $remover = TestDb::find(array());
            foreach ($remover as $remo) {
                $remo->remove();
            }
            $this->testTruncate();
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
            $objs = TestDb::find();
            foreach ($objs as $obj) {
                $this->assertEquals('valores', $obj->test, "Comprobación del valor original");
                $obj->test = 'valor';
                $n = $obj->save();
                $this->assertEquals(1, $n, "Recuperación del nÃºmero de elementos modificados con update");
                $key = $obj->keys()[0];
                $id = $obj->$key;
                $obj2 = TestDb::findOne([$key => $id]);
                $this->assertEquals('valor', $obj2->test, "Comprobación del valor original");
            }
        }

        public function testSelectByPk()
        {
            $elements = TestDb::find();
            $this->assertTrue($elements->hasElements(), "Find return elements");
            foreach ($elements as $element) {
                $obj = TestDb::findByPk($element->getPrimaryKeyValue());
                $this->assertInstanceOf(TestDb::class, $obj);
                $this->assertTrue($obj instanceof TestDb);
                //            $this->assertTrue($obj instanceof Database);
                $this->assertEquals($element, $obj);
            }
        }

        public function testSelectFindPaginated()
        {
            for ($i = 1; $i <= $this->loops; $i++) {
                $objs = TestDb::find(array('test' => 'valor'), $this->db->keys()[0], $i, 0);
                $this->assertTrue($objs instanceof Collection);
                $this->assertTrue($objs->hasElements());
                $this->assertContainsOnlyInstancesOf(TestDb::class, $objs);
                $this->assertEquals($i, $objs->size());
                foreach ($objs as $obj) {
                    $this->assertInstanceOf(TestDb::class, $obj);
                    //                $this->assertTrue($obj instanceof Database);
                    $this->assertTrue($obj->loaded);
                    if (!in_array($this->db_type, [Engines::TYPE_MONGOCLIENT, Engines::TYPE_MONGO])) {
                        foreach ($obj->columns() as $column) {
                            $this->assertObjectHasProperty($column, $obj); //PHPUnit 6
                        }
                    }
                }
            }
        }

        public function testUpdate()
        {
            $modificateds = $this->db->update(array('test' => 'value'), array('test' => 'valor'));
            $this->assertEquals($this->loops, $modificateds, "Update elements");
        }

        public function testSerialize()
        {
            $var = TestDb::find();
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
            $this->assertEquals(TestDb::findByPk($unserialized->getPrimaryKeyValue()), $unserialized);
            $unserialized->test = 'value';
            $unserialized->save();
        }

        public function testDelete()
        {
            $remover = TestDb::find(array('test' => 'value'));
            $this->assertEquals($this->loops, $remover->size(), "delete {$remover->size()} results");
            foreach ($remover as $remo) {
                $remo->remove();
            }
            $remover = TestDb::find(array('test' => 'value'));
            $this->assertEquals(0, $remover->size(), "No elements for delete after remove");
            $this->assertFalse($remover->hasElements(), "No elements for delete after remove");
            //        $removeds = $this->db->delete(array('test' => 'value'));
    //        $this->assertEquals($this->loops, $removeds, "delete {$removeds} results");
        }
    */
    public function testTruncate()
    {
        $builder = QueryBuilder::getInstance()->select()->from($this->table);
        $cursor = $this->db->execute($builder);
        $number = $cursor->count();
        $this->db->setTable($this->table);
        $success = $this->db->truncate();
        $this->assertEquals($number, $success, "Trucate table");
    }

    public function testDrop()
    {
        $this->markTestSkipped();
        $this->db->setTable($this->table);
        $result = $this->db->drop();
        $this->assertTrue(true, "Drop table");
    }

    public function testDisconnect()
    {
        $result = $this->db->disconnect();
        $this->assertTrue($result, "Test disconnect");
    }

}

