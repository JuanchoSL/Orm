<?php

namespace JuanchoSL\Orm\Tests\Unit;

use JuanchoSL\Orm\engine\Engines;
use JuanchoSL\Orm\engine\Structures\FieldDescription;

class SqliteTest extends AbstractUnit
{
    protected $db_type = Engines::TYPE_SQLITE;

    public function queryCreateTable(): array
    {
        return [
            (new FieldDescription)->setName('id')->setType('integer')->setLength(6)->setNullable(false)->setKey(true),
            (new FieldDescription)->setName('test')->setType('varchar')->setLength(16)->setNullable(false),
            (new FieldDescription)->setName('dato')->setType('varchar')->setLength(16)->setNullable(false),
        ];
        return "CREATE TABLE test (
            id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
            test TEXT NOT NULL,
            dato TEXT NOT NULL
        );";
    }
}
