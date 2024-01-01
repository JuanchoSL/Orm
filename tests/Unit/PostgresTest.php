<?php

namespace JuanchoSL\Orm\Tests\Unit;

use JuanchoSL\Orm\engine\Structures\FieldDescription;
use JuanchoSL\Orm\engine\Engines;

class PostgresTest extends AbstractUnitTest
{
    protected $db_type = Engines::TYPE_POSTGRE;

    public function queryCreateTable(): array
    {
        return [
            (new FieldDescription)->setName('id')->setType('integer')->setLength(6)->setNullable(false)->setKey(true),
            (new FieldDescription)->setName('test')->setType('varchar')->setLength(16)->setNullable(false),
            (new FieldDescription)->setName('dato')->setType('varchar')->setLength(16)->setNullable(false),
        ];
        return "CREATE TABLE test(  
            id int NOT NULL PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
            test VARCHAR(16),
            dato VARCHAR(16)
        );";
    }
}