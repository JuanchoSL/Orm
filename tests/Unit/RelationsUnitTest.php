<?php

namespace JuanchoSL\Orm\Tests\Unit;

use JuanchoSL\Orm\engine\Drivers\DbInterface;
use JuanchoSL\Orm\engine\Responses\InsertResponse;
use JuanchoSL\Orm\querybuilder\QueryBuilder;
use JuanchoSL\Orm\Tests\ConnectionTrait;
use PHPUnit\Framework\TestCase;

class RelationsUnitTest extends TestCase
{
    use ConnectionTrait;

    protected DbInterface $db;

    protected $db_type;

    private $loops = 3;

    private $table_parent = 'test';
    private $table_child = 'other';

    /**
     * @dataProvider providerData
     */
    public function testInsert($db)
    {
        for ($i = 1; $i <= $this->loops; $i++) {
            $builder = QueryBuilder::getInstance()->insert(array('test' => 'valor', 'dato' => $i))->into($this->table_parent);
            $id = $db->execute($builder);
            $this->assertTrue(!empty($id), "Recuperación del id de un insert");
            $this->assertInstanceOf(InsertResponse::class, $id);
            $id = $id->__toString();
            $this->assertIsNumeric($id, "ID is numeric");
            $this->assertEquals($i, $id, "ID equals than loop");
        }
        for ($i = 1; $i <= $this->loops; $i++) {
            $fk = ($i % 2 == 0) ? 2 : 1;
            $builder = QueryBuilder::getInstance()->insert(array('valor' => "valor_{$i}", 'test_id' => $fk))->into($this->table_child);
            $id = $db->execute($builder);
            $id = $id->__toString();
            $this->assertTrue(!empty($id), "Recuperación del id de un insert");
        }
    }

    /**
     * @dataProvider providerData
     */
    public function testJoin($db)
    {
        //$this->markTestSkipped();
        $builder = QueryBuilder::getInstance()->select(['test.*'])->from('test')->join(['inner join other on test_id=test.id'])->where(['test.id', 1]);
        $results = $db->execute($builder);
        $this->assertEquals(2, $results->count());
        $key = current($db->keys('test'));
        while ($res = $results->next()) {
            $this->assertEquals(1, $res->{$key});
        }
        $results->free();
    }

    /**
     * @dataProvider providerData
     */
    public function testConditionSubquery($db)
    {
        //$this->markTestSkipped();
        $builder = QueryBuilder::getInstance()->select()->from($this->table_parent)->where(['id', QueryBuilder::getInstance()->select(['test_id'])->from($this->table_child)->where(['test_id', 1]), 'IN']);
        $results = $db->execute($builder);
        $this->assertEquals(1, $results->count());
        $key = current($db->keys('test'));
        while ($res = $results->next()) {
            $this->assertEquals(1, $res->{$key});
        }
        $results->free();
    }

    /**
     * @dataProvider providerData
     */
    public function testTruncate($db)
    {
        foreach ([$this->table_child, $this->table_parent] as $table) {
            $builder = QueryBuilder::getInstance()->select()->from($table);
            $cursor = $db->execute($builder);
            $number = $cursor->count();
            $cursor->free();
            $builder = QueryBuilder::getInstance()->truncate()->table($table);
            $success = $db->execute($builder);
            $this->assertEquals(1, $success->count(), "Truncate table");
        }
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

