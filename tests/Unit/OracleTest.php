<?php

namespace JuanchoSL\Orm\Tests\Unit;

use JuanchoSL\Orm\engine\Engines;
use JuanchoSL\Orm\engine\Structures\FieldDescription;

class OracleTest extends AbstractUnitTest
{
    protected $db_type = Engines::TYPE_ORACLE;

    public function queryCreateTable(): array
    {
        $c = $this->db->execute("CREATE SEQUENCE test_id_seq START WITH 1 INCREMENT BY 1");
        $c->free();
        return [
            (new FieldDescription)->setName('id')->setType('number')->setLength(6)->setNullable(false)->setKey(true)->setDefault('test_id_seq.nextval'),
            (new FieldDescription)->setName('test')->setType('varchar')->setLength(16)->setNullable(false),
            (new FieldDescription)->setName('dato')->setType('varchar')->setLength(16)->setNullable(false),
        ];
        return "CREATE TABLE TEST (
            id NUMBER DEFAULT test_id_seq.nextval PRIMARY KEY,
            test VARCHAR2(16) NOT NULL,
            dato VARCHAR2(16) NOT NULL
        )";
        //TABLESPACE USERS;";
    }
}