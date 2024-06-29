<?php

namespace JuanchoSL\Orm\Tests\Unit;

use JuanchoSL\Orm\Datamodel\Model;
use JuanchoSL\Orm\engine\Drivers\DbInterface;
use JuanchoSL\Orm\engine\Responses\InsertResponse;
use JuanchoSL\Orm\engine\Structures\FieldDescription;
use JuanchoSL\Orm\querybuilder\QueryBuilder;
use JuanchoSL\Orm\engine\Engines;
use JuanchoSL\Orm\querybuilder\Types\CreateQueryBuilder;
use JuanchoSL\Orm\querybuilder\Types\InsertQueryBuilder;
use JuanchoSL\Orm\Tests\ConnectionTrait;
use PHPUnit\Framework\TestCase;

class EngineUnitTest extends TestCase
{
    use ConnectionTrait;

    protected DbInterface $db;

    protected $db_type;

    private $loops = 3;

    private $table = 'test';


    /**
     * @dataProvider providerData
     */
    public function testCreate($db)
    {
        $this->markTestSkipped();
        $query_table = [
            (new FieldDescription)->setName('id')->setType('integer')->setLength(6)->setNullable(false)->setKey(true),
            (new FieldDescription)->setName('test')->setType('varchar')->setLength(16)->setNullable(false),
            (new FieldDescription)->setName('dato')->setType('varchar')->setLength(16)->setNullable(false),
        ];
        $b = QueryBuilder::getInstance()->create(...$query_table)->table($this->table);
        $db->execute($b);
        $this->assertTrue(true, "Create table");
    }

    /**
     * @dataProvider providerData
     */
    public function testTableExists($db)
    {
        $tables = $db->getTables();
        $this->assertIsIterable($tables, "Tables is an iterable element");
        $this->assertContains($this->table, $tables, "Table exists into list");
    }

    /**
     * @dataProvider providerData
     */
    public function testInsert($db)
    {
        //$db->setTable($this->table);
        for ($i = 1; $i <= $this->loops; $i++) {
            $builder = QueryBuilder::getInstance()->insert(array('test' => 'valor', 'dato' => $i))->into($this->table);
            //$builder = InsertQueryBuilder::getInstance()->into($this->table)->values(array('test' => 'valor', 'dato' => $i));
            $id = $db->execute($builder);
            $this->assertTrue(!empty($id), "Recuperación del id de un insert");
            $this->assertInstanceOf(InsertResponse::class, $id);
            $id = $id->__toString();
            $this->assertIsNumeric($id, "ID is numeric");
            $this->assertEquals($i, $id, "ID equals than loop");
        }
    }

    /**
     * @dataProvider providerData
     */
    public function testSqlInjection($db)
    {
        //$this->markTestSkipped();
        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test', "valor'"]);
        $cursor = $db->execute($builder);
        $this->assertEquals(0, $cursor->count(), "Check SQL Injection");
        $cursor->free();
    }

    /**
     * @dataProvider providerData
     */
    public function testConditions($db)
    {
        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test', 'valor'], ['dato', 2]);
        $cursor = $db->execute($builder);
        $this->assertEquals(1, $cursor->count(), "Check 1");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test', 'valor'])->where(['dato', 2]);
        $cursor = $db->execute($builder);
        $this->assertEquals(1, $cursor->count(), "Check 2");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test', 'valor'])->orWhere(['dato', 2]);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Check 3");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test', ['valor', 'valore']]);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Check 4");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test="valor"'], ['dato=2']);
        $cursor = $db->execute($builder);
        $this->assertEquals(1, $cursor->count(), "Check 5");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test="valor"'])->where(['dato=2']);
        $cursor = $db->execute($builder);
        $this->assertEquals(1, $cursor->count(), "Check 6");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test="valor"'])->orWhere(['dato=2']);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Check 7");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test="valor"'], ['test<>"otro"']);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Check 8");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test="valor"'])->orWhere(['test="valore"']);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Check 9");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', [2, 3], true]);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops - 1, $cursor->count(), "Check 10");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', [1], false]);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops - 1, $cursor->count(), "Check 11");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', [2, 3], 'IN']);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops - 1, $cursor->count(), "Check 10");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', [1], 'NOT IN']);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops - 1, $cursor->count(), "Check 11");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', 1, '>']);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops - 1, $cursor->count(), "Check 12");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', 2, '>=']);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops - 1, $cursor->count(), "Check 13");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', null, 'IS NOT NULL']);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Check 14");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', null, 'IS NULL']);
        $cursor = $db->execute($builder);
        $this->assertEquals(0, $cursor->count(), "Check 15");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', null, false]);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Check 14");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', null, true]);
        $cursor = $db->execute($builder);
        $this->assertEquals(0, $cursor->count(), "Check 15");
        $cursor->free();
    }

    /**
     * @dataProvider providerData
     */
    public function testSelect($db)
    {
        for ($i = 1; $i <= $this->loops; $i++) {
            $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(array('test', 'valor'))->limit($i);
            $cursor = $db->execute($builder);
            $values = $cursor->get();
            $cursor->free();
            $this->assertTrue(is_array($values));
            $this->assertEquals($i, count($values));
            $this->assertTrue(isset($values[$i - 1]));
            $this->assertTrue($values[$i - 1] instanceof \stdClass);
            $id = (in_array($this->db_type, [Engines::TYPE_MONGOCLIENT, Engines::TYPE_MONGO])) ? (string) $values[$i - 1]->_id : $values[$i - 1]->{current($db->keys($this->table))};
            $this->assertTrue(!empty($id), "Recuperación del id de un select");
        }
    }

    /**
     * @dataProvider providerData
     */
    public function testSelectPaginated($db)
    {
        $i = $this->loops - 1;
        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(array('test', 'valor'))->limit($i);
        $cursor = $db->execute($builder);
        $this->assertEquals($i, $cursor->count(), "Count counter");
        $values = $cursor->get();
        $cursor->free();
        $this->assertTrue(is_array($values));
        $this->assertEquals($i, count($values), "Count function");
        $builder = QueryBuilder::getInstance()->clear()->select()->from($this->table)->where(array('test', 'valor'));
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Count counter 2");
        $cursor->free();
        $this->assertTrue(isset($values[$i - 1]));
        $this->assertTrue($values[$i - 1] instanceof \stdClass);
        $id = (in_array($this->db_type, [Engines::TYPE_MONGOCLIENT, Engines::TYPE_MONGO])) ? (string) $values[$i - 1]->_id : $values[$i - 1]->{current($db->keys($this->table))};
        $this->assertTrue(!empty($id), "Recuperación del id de un select");
    }

    /**
     * @dataProvider providerData
     */
    public function testJoin($db)
    {
        $this->markTestSkipped();
        $builder = QueryBuilder::getInstance()->select(['test.*'])->from('test')->join(['inner join other on test_id=test.id'])->where(['test.id', 1]);
        $results = $db->execute($builder);
        $this->assertEquals(2, $results->count());
        while ($res = $results->next()) {
            $this->assertEquals(1, $res->id);
        }
        $results->free();
    }

    /**
     * @dataProvider providerData
     */
    public function testConditionSubquery($db)
    {
        $this->markTestSkipped();
        $builder = QueryBuilder::getInstance()->select()->from('test')->where(['id', QueryBuilder::getInstance()->select(['id'])->from('other')->where(['test_id', 1]), 'IN']);
        $results = $db->execute($builder);
        $this->assertEquals(2, $results->count());
        while ($res = $results->next()) {
            $this->assertEquals(1, $res->id);
        }
        $results->free();

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

    /**
     * @dataProvider providerData
     */
    public function testTruncate($db)
    {
        $builder = QueryBuilder::getInstance()->select()->from($this->table);
        $cursor = $db->execute($builder);
        $number = $cursor->count();
        $cursor->free();
        $builder = QueryBuilder::getInstance()->truncate()->table($this->table);
        $success = $db->execute($builder);
        $this->assertEquals(1, $success->count(), "Truncate table");
        //$db->setTable($this->table);
        //$success = $db->truncate();
        //$this->assertEquals($number, $success, "Truncate table");
    }

    /**
     * @dataProvider providerData
     */
    public function testDrop($db)
    {
        $this->markTestSkipped();
        $builder = QueryBuilder::getInstance()->drop()->table($this->table);
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

