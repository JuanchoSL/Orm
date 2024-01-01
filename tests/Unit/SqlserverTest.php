<?php

namespace JuanchoSL\Orm\Tests\Unit;

use JuanchoSL\Orm\engine\Engines;
use JuanchoSL\Orm\engine\Structures\FieldDescription;

class SqlserverTest extends AbstractUnitTest
{
    protected $db_type = Engines::TYPE_SQLSRV;

    public function queryCreateTable(): array
    {
        return [
            (new FieldDescription)->setName('id')->setType('integer')->setLength(6)->setNullable(false)->setKey(true),
            (new FieldDescription)->setName('test')->setType('varchar')->setLength(16)->setNullable(false),
            (new FieldDescription)->setName('dato')->setType('varchar')->setLength(16)->setNullable(false),
        ];
        return "CREATE TABLE test (
            id int IDENTITY(1,1) PRIMARY KEY,
            test varchar(16) NOT NULL,
            dato varchar(16) NOT NULL
        );";
    }
}