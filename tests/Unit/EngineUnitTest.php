<?php

namespace JuanchoSL\Orm\Tests\Unit;

use JuanchoSL\Orm\engine\Drivers\DbInterface;
use JuanchoSL\Orm\engine\Responses\InsertResponse;
use JuanchoSL\Orm\engine\Structures\FieldDescription;
use JuanchoSL\Orm\querybuilder\QueryBuilder;
use JuanchoSL\Orm\engine\Engines;
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
        $this->assertEquals(1, $cursor->count(), "Check grouped ands with sepa`rated paramenters");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test', 'valor'])->where(['dato', 2]);
        $cursor = $db->execute($builder);
        $this->assertEquals(1, $cursor->count(), "Check separated ands with parameters");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test', 'valor'])->orWhere(['dato', 2]);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Check separated ors with parmeters");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test', ['valor', 'valore']]);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Check in with boolean");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test="valor"'], ['dato=2']);
        $cursor = $db->execute($builder);
        $this->assertEquals(1, $cursor->count(), "Check grouped ands with strings");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test="valor"'])->where(['dato=2']);
        $cursor = $db->execute($builder);
        $this->assertEquals(1, $cursor->count(), "Check separated ands with strings");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test="valor"'])->orWhere(['dato=2']);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Check separated ors with strings");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test="valor"'], ['test<>"otro"']);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Check separated ands with strings and non equals comparation");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test="valor"'])->orWhere(['test="valore"']);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Check separated ors with strings");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test', "valor%", 'like']);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Check like with parameters");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', [2, 3], true]);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops - 1, $cursor->count(), "Check in with boolean");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', [1], false]);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops - 1, $cursor->count(), "Check not in with boolean");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', [2, 3], 'IN']);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops - 1, $cursor->count(), "Check in with string");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', [1], 'NOT IN']);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops - 1, $cursor->count(), "Check not in with boolean");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', 1, '>']);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops - 1, $cursor->count(), "Check greather than");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', 2, '>=']);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops - 1, $cursor->count(), "Check greather than or equals");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', null, 'IS NOT NULL']);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Check is not null string");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', null, 'IS NULL']);
        $cursor = $db->execute($builder);
        $this->assertEquals(0, $cursor->count(), "Check is null string");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', null, false]);
        $cursor = $db->execute($builder);
        $this->assertEquals($this->loops, $cursor->count(), "Check is not null boolean");
        $cursor->free();

        $builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['dato', null, true]);
        $cursor = $db->execute($builder);
        $this->assertEquals(0, $cursor->count(), "Check is null boolean");
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
    public function testTruncate($db)
    {
        $builder = QueryBuilder::getInstance()->select()->from($this->table);
        $cursor = $db->execute($builder);
        $number = $cursor->count();
        $cursor->free();
        $builder = QueryBuilder::getInstance()->truncate()->table($this->table);
        $success = $db->execute($builder);
        $this->assertEquals(1, $success->count(), "Truncate table");
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

